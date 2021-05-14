<?php

  /**
   * qcREST - Native (CGI) Controller
   * Copyright (C) 2016-2021 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  declare (strict_types=1);
  
  namespace quarxConnect\REST\Controller;
  use \quarxConnect\REST\ABI;
  use \quarxConnect\REST;
  use \quarxConnect\Events;
  
  class Native extends HTTP {
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
    public function setVirtualBaseURI (string $URI) : void {
      if (strlen ($URI) > 1) {
        $this->virtualBaseURI = $URI;
        
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
     * @param ABI\Entity $Resource (optional)
     * 
     * @access public
     * @return string
     **/
    public function getURI (ABI\Entity $Resource = null) : string {
      $URI = $_SERVER ['REQUEST_URI'];
      $scriptPath = dirname ($_SERVER ['SCRIPT_NAME']);
      $scriptName = basename ($_SERVER ['SCRIPT_NAME']);
      $lP = strlen ($scriptPath);
      $lN = strlen ($scriptName);
      
      $Prefix = 'http' . (isset ($_SERVER ['HTTPS']) ? 's' : '') . '://' . $_SERVER ['HTTP_HOST'] . ($_SERVER ['SERVER_PORT'] != (isset ($_SERVER ['HTTPS']) ? 443 : 80) ? ':' . $_SERVER ['SERVER_PORT'] : '');
      
      if (($lP > 1) && (substr ($URI, 0, $lP) == $scriptPath)) {
        if (substr ($URI, $lP + 1, $lN) == $scriptName)
          return $Prefix . $_SERVER ['SCRIPT_NAME'] . ($this->virtualBaseURI ? $this->virtualBaseURI : '') . parent::getEntityURI ($Resource);
        else
          return $Prefix . $scriptPath . ($this->virtualBaseURI ? $this->virtualBaseURI : '') . parent::getEntityURI ($Resource);
      }
      
      return $Prefix . $_SERVER ['SCRIPT_NAME'] . ($this->virtualBaseURI ? $this->virtualBaseURI : '') . parent::getEntityURI ($Resource);
    }
    // }}}
    
    // {{{ getRequest
    /**
     * Generate a Request-Object for a pending request
     * 
     * @access public
     * @return ABI\Request
     **/
    public function getRequest () : ?ABI\Request {
      static $Request = null;
      
      // Check if the request was already served
      if ($Request)
        return null;
      
      // Check if the request-method is valid
      if (!defined (ABI\Request::class . '::METHOD_' . $_SERVER ['REQUEST_METHOD']))
        return null;
      
      $Method = constant (ABI\Request::class . '::METHOD_' . $_SERVER ['REQUEST_METHOD']);
      
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
            
            return null;
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
      return new REST\Request ($this, $URI [0], $Method, $URI [1], $Meta, $Content, $ContentType, $Types, $_SERVER ['REMOTE_ADDR'], (isset ($_SERVER ['HTTPS']) && ($_SERVER ['HTTPS'] == 'on')));
    }
    // }}}
    
    // {{{ setResponse
    /**
     * Write out a response for a previous request
     * 
     * @param ABI\Response $Response The response
     * 
     * @access public
     * @return Events\Promise
     **/
    public function setResponse (ABI\Response $Response) : Events\Promise {
      // Make sure there is no output buffered
      while (ob_get_level () > 0)
        ob_end_clean ();
      
      // Generate output
      header_remove ();
      
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
      
      if (($responseBody = $Response->getContent ()) !== null) {
        header ('Content-Type: ' . $Response->getContentType ());
        header ('Content-Length: ' . strlen ($responseBody));
        
        echo $responseBody;
      }
      
      return Events\Promise::resolve ($Response);
    }
    // }}}
    
    // {{{ handle
    /**
     * Try to process a request, if no request is given explicitly try to fetch one from SAPI
     * 
     * @param ABI\Request $Request (optional)
     * 
     * @access public
     * @return Events\Promise
     **/
    public function handle (ABI\Request $Request = null) : Events\Promise {
      // Start output-buffering
      ob_start ();
      
      // Switch off error-reporting (ugly, i know!)
      ini_set ('display_errors', 'Off');
      
      // Inherit to our parent
      return parent::handle ($Request);
    }
    // }}}
  }
