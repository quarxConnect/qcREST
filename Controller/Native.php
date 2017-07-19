<?PHP

  /**
   * qcREST - Native (CGI) Controller
   * Copyright (C) 2016 Bernd Holzmueller <bernd@quarxconnect.de>
   * 
   * This program is free software: you can redistribute it and/or modify 
   * it under the terms of the GNU General Public License as published by
   * the Free Software Foundation, either version 3 of the License, or
   * (at your option) any later version.
   * 
   * This program is distributed in the hope that it will be useful,
   * but WITHOUT ANY WARRANTY; without even the implied warranty of
   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   * GNU General Public License for more details.
   * 
   * You should have received a copy of the GNU General Public License
   * along with this program.  If not, see <http://www.gnu.org/licenses/>.
   **/
  
  require_once ('qcREST/Controller/HTTP.php');
  require_once ('qcREST/Request.php');
  
  class qcREST_Controller_Native extends qcREST_Controller_HTTP {
    /* Virtual Base-URI */
    private $virtualBaseURI = null;
    
    // {{{ setVirtualBaseURI
    /**
     * Set a base-uri to strip from virtual URIs
     * 
     * @param string $URI
     * 
     * @access public
     * @return void
     **/
    public function setVirtualBaseURI ($URI) {
      if (strlen ($URI) > 1) {
        $this->virtualBaseURI = strval ($URI);
        
        if ($this->virtualBaseURI [strlen ($this->virtualBaseURI) - 1] == '/')
          $this->virtualBaseURI = substr ($this->virtualBaseURI, 0, -1);
        
        if ($this->virtualBaseURI [0] != '/')
          $this->virtualBaseURI = '/' . $this->virtualBaseURI;
      } else
        $this->virtualBaseURI = null;
    }
    // }}}
    
    // {{{ getURI
    /**
     * Retrive the URI of this controller
     * 
     * @access public
     * @return string
     **/
    public function getURI () {
      $URI = $_SERVER ['REQUEST_URI'];
      $scriptPath = dirname ($_SERVER ['SCRIPT_NAME']);
      $scriptName = basename ($_SERVER ['SCRIPT_NAME']);
      $lP = strlen ($scriptPath);
      $lN = strlen ($scriptName);
      
      $Prefix = 'http' . (isset ($_SERVER ['HTTPS']) ? 's' : '') . '://' . $_SERVER ['HTTP_HOST'] . ($_SERVER ['SERVER_PORT'] != (isset ($_SERVER ['HTTPS']) ? 443 : 80) ? ':' . $_SERVER ['SERVER_PORT'] : '');
      
      if (($lP > 1) && (substr ($URI, 0, $lP) == $scriptPath)) {
        if (substr ($URI, $lP + 1, $lN) == $scriptName)
          return $Prefix . $_SERVER ['SCRIPT_NAME'] . ($this->virtualBaseURI ? $this->virtualBaseURI : '') . '/';
        else
          return $Prefix . $scriptPath . ($this->virtualBaseURI ? $this->virtualBaseURI : '') . '/';
      }
      
      return $Prefix . $_SERVER ['SCRIPT_NAME'] . ($this->virtualBaseURI ? $this->virtualBaseURI : '') . '/';
    }
    // }}}
    
    // {{{ getRequest
    /**
     * Generate a Request-Object for a pending request
     * 
     * @access public
     * @return qcREST_Interface_Request
     **/
    public function getRequest () {
      static $Request = null;
      
      // Check if the request was already served
      if ($Request)
        return false;
      
      // Check if the request-method is valid
      if (!defined ('qcREST_Interface_Request::METHOD_' . $_SERVER ['REQUEST_METHOD']))
        return false;
      
      $Method = constant ('qcREST_Interface_Request::METHOD_' . $_SERVER ['REQUEST_METHOD']);
      
      // Create relative URI
      $URI = $_SERVER ['REQUEST_URI'];
      $scriptPath = dirname ($_SERVER ['SCRIPT_NAME']);
      $scriptName = basename ($_SERVER ['SCRIPT_NAME']);
      $lP = strlen ($scriptPath);
      $lN = strlen ($scriptName);
      
      if (($lP > 1) && (substr ($URI, 0, $lP) == $scriptPath)) {
        if (substr ($URI, $lP + 1, $lN) == $scriptName)
          $URI = substr ($URI, $lP + $lN + 1);
        else
          $URI = substr ($URI, $lP);
      
      } elseif (($lP == 1) && (substr ($URI, 1, $lN) == $scriptName))
        $URI = substr ($URI, $lN + 1);
      
      // Check wheter to strip virtual base URI
      if (($this->virtualBaseURI !== null) && (substr ($URI, 0, strlen ($this->virtualBaseURI) + 1) == $this->virtualBaseURI . '/'))
        $URI = substr ($URI, strlen ($this->virtualBaseURI));
      
      // Truncate parameters
      $URI = $this->explodeURI ($URI);
      
      // Check if there is a request-body
      global $HTTP_RAW_POST_DATA;
      
      if (isset ($_SERVER ['CONTENT_LENGTH']) && ($_SERVER ['CONTENT_LENGTH'] >= 0)) {
        $Content = (isset ($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents ('php://input'));
        $ContentType = $_SERVER ['CONTENT_TYPE'];
        
        if (($p = strpos ($ContentType, ';')) !== false) {
          $ContentExtra = trim (substr ($ContentType, $p + 1));
          $ContentType = substr ($ContentType, 0, $p);
        } else
          $ContentExtra = null;
        
        // Check wheter to work around multipart/form-data uploads
        if (($ContentType == 'multipart/form-data') && (strlen ($Content) == 0)) {
          // Try to find boundary
          if (!is_array ($cParameters = $this->httpHeaderParameters ($ContentExtra)) ||
              !isset ($cParameters ['boundary'])) {
            trigger_error ('No boundary on multipart-content-type found');
            
            return false;
          }
          
          // Put POST-Variables back to buffer
          foreach ($_POST as $Key=>$Value)
            $Content .=
              '--' . $cParameters ['boundary'] . "\r\n" .
              'Content-Disposition: form-data; name="' . $Key . '"' . "\r\n\r\n" .
              $Value . "\r\n";
          
          // Put uploaded files back to buffer
          foreach ($_FILES as $Key=>$Info) {
            // Put back to buffer
            $Content .=
              '--' . $cParameters ['boundary'] . "\r\n" .
              'Content-Disposition: form-data; name="' . $Key . '"' . (isset ($Info ['name']) ? '; filename="' . $Info ['name'] . '"' : '') . "\r\n" .
              (isset ($Info ['type']) ? 'Content-Type: ' . $Info ['type'] . "\r\n" : '') . "\r\n" .
              file_get_contents ($Info ['tmp_name']) . "\r\n";
            
            // Try to remove the file
            @unlink ($Info ['tmp_name']);
            unset ($_FILES [$Key]);
          }
          
          // Finish MIME-Content
          $Content .= '--' . $cParameters ['boundary'] . '--' . "\r\n";
        }
      } else
        $Content = $ContentType = null;
      
      // Extract accepted types
      $Types = $this->explodeAcceptHeader (isset ($_SERVER ['HTTP_ACCEPT']) ? $_SERVER ['HTTP_ACCEPT'] : '');
      
      // Prepare meta-data
      $Meta = apache_request_headers ();
      
      if (!isset ($Meta ['Authorization']) && isset ($_SERVER ['PHP_AUTH_USER']))
        $Meta ['Authorization'] = 'Basic ' . base64_encode ($_SERVER ['PHP_AUTH_USER'] . ':' . (isset ($_SERVER ['PHP_AUTH_PW']) ? $_SERVER ['PHP_AUTH_PW'] : ''));
      
      // Create the final request
      return new qcREST_Request ($this, $URI [0], $Method, $URI [1], $Meta, $Content, $ContentType, $Types, $_SERVER ['REMOTE_ADDR'], (isset ($_SERVER ['HTTPS']) && ($_SERVER ['HTTPS'] == 'on')));
    }
    // }}}
    
    // {{{ setResponse
    /**
     * Write out a response for a previous request
     * 
     * @param qcREST_Interface_Response $Response The response
     * @param callable $Callback (optional) A callback to raise once the operation was completed
     * @param mixed $Private (optional) Any private data to pass to the callback
     * 
     * The callback will be raised once the operation was finished in the form of
     * 
     *   function (qcREST_Interface_Controller $Self, qcREST_Interface_Response $Response, bool $Status, mixed $Private) { }
     * 
     * @access public
     * @return bool  
     **/
    public function setResponse (qcREST_Interface_Response $Response, callable $Callback = null, $Private = null) {
      // Make sure there is no output buffered
      while (ob_get_level () > 0)
        ob_end_clean ();
      
      // Generate output
      $Status = true;
      $Meta = $Response->getMeta ();
      
      foreach ($Meta as $Key=>$Value)
        if (is_array ($Value))
          foreach ($Value as $Val)
            header ($Key . ': ' . $Val, false);
        else
          header ($Key. ': ' . $Value);
      
      foreach ($this->getMeta ($Response) as $Key=>$Value)
        if (isset ($Meta [$Key]))
          continue;
        elseif (is_array ($Value))
          foreach ($Value as $Val)
            header ($Key . ': ' . $Val, false);
        else
          header ($Key. ': ' . $Value);
      
      $statusCode = $Response->getStatus ();
      $statusText = $this->getStatusCodeDescription ($statusCode);
      
      header ('HTTP/1.1 ' . $statusCode . ($statusText !== null ? ' ' . $statusText : ''));
      header ('Status: ' . $statusCode . ($statusText !== null ? ' ' . $statusText : ''));
      header ('X-Powered-By: qcREST/0.2 for CGI');
      
      if ($ContentType = $Response->getContentType ())
        header ('Content-Type: ' . $ContentType);
      
      if (($Content = $Response->getContent ()) !== null) {
        header ('Content-Length: ' . strlen ($Content));
        
        echo $Content;
      }
      
      // Raise callback if one was given
      if ($Callback)
        call_user_func ($Callback, $this, $Response, $Status, $Private);
      
      // Stop the process here
      exit ();
      return $Status;
    }
    // }}}
    
    // {{{ handle
    /**
     * Try to process a request, if no request is given explicitly try to fetch one from SAPI
     * 
     * @param callable $Callback
     * @param mixed $Private (optional)
     * @param qcREST_Interface_Request $Request (optional)
     *    
     * The callback will be raised in the form of:
     *   
     *   function (qcREST_Interface_Controller $Self, qcREST_Interface_Request $Request = null, qcREST_Interface_Response $Response = null, bool $Status, mixed $Private = null) { }
     * 
     * @access public
     * @return bool
     **/
    public function handle (callable $Callback, $Private = null, qcREST_Interface_Request $Request = null) {
      // Start output-buffering
      ob_start ();
      
      // Switch off error-reporting (ugly, i know!)
      ini_set ('display_errors', 'Off');
      
      // Inherit to our parent
      return parent::handle ($Callback, $Private, $Request);
    }
    // }}}
  }

?>