<?PHP

  require_once ('qcREST/Controller.php');
  require_once ('qcREST/Request.php');
  
  class qcREST_Controller_Native extends qcREST_Controller {
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
      $Parameters = array ();
      
      if (($p = strpos ($URI, '?')) !== false) {
        foreach (explode ('&', substr ($URI, $p + 1)) as $Parameter)
          if (($pv = strpos ($Parameter, '=')) !== false)
            $Parameters [urldecode (substr ($Parameter, 0, $pv))] = urldecode (substr ($Parameter, $pv + 1));
          else
            $Parameters [urldecode ($Parameter)] = true;
        
        $URI = substr ($URI, 0, $p);
      }
      
      // Check if there is a request-body
      global $HTTP_RAW_POST_DATA;
      
      if (isset ($_SERVER ['CONTENT_LENGTH']) && ($_SERVER ['CONTENT_LENGTH'] > 0)) {
        $Content = (isset ($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents ('php://input'));
        $ContentType = $_SERVER ['CONTENT_TYPE'];
        
        if (($p = strpos ($ContentType, ';')) !== false)
          $ContentType = substr ($ContentType, 0, $p);
      } else
        $Content = $ContentType = null;
      
      // Extract accepted types
      $Types = array ();
      
      if (isset ($_SERVER ['HTTP_ACCEPT'])) {
        $Preferences = array ();
        
        foreach (explode (',', $_SERVER ['HTTP_ACCEPT']) as $Pref) {
          $Data = explode (';', $Pref);
          $Mime = array_shift ($Data);
          $Preference = 1.0;  
          
          foreach ($Data as $Param)
            if (substr ($Param, 0, 2) == 'q=')
              $Preference = floatval (substr ($Param, 2));
          
          $Preference = floor ($Preference * 100);
          
          if (isset ($Preferences [$Preference]))
            $Preferences [$Preference][] = $Mime;
          else
            $Preferences [$Preference] = array ($Mime);
        }
        
        krsort ($Preferences);
        
        foreach ($Preferences as $Mimes)
          foreach ($Mimes as $Mime)
            $Types [] = $Mime;
      } else
        $Types [] = '*/*';
      
      // Prepare meta-data
      $Meta = apache_request_headers ();
      
      if (!isset ($Meta ['Authorization']) && isset ($_SERVER ['PHP_AUTH_USER']))
        $Meta ['Authorization'] = 'Basic ' . base64_encode ($_SERVER ['PHP_AUTH_USER'] . ':' . (isset ($_SERVER ['PHP_AUTH_PW']) ? $_SERVER ['PHP_AUTH_PW'] : ''));
      
      return ($Request = new qcREST_Request ($URI, $Method, $Parameters, $Meta, $Content, $ContentType, $Types));
    }
    // }}}
    
    // {{{ setResponse
    /**
     * Write out a response for a previous request
     * 
     * @param qcREST_Interface_Response $Response
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
      $sMap = array (
        qcREST_Interface_Response::STATUS_OK => 'Okay',
        qcREST_Interface_Response::STATUS_CREATED => 'Resource was created',
        qcREST_Interface_Response::STATUS_NOT_FOUND => 'Resource could not be found',
        qcREST_Interface_Response::STATUS_NOT_ALLOWED => 'Operation is not allowed',
        qcREST_Interface_Response::STATUS_CLIENT_ERROR => 'There was an error with your request',
        qcREST_Interface_Response::STATUS_FORMAT_UNSUPPORTED => 'Unsupported Input-Format',
        qcREST_Interface_Response::STATUS_FORMAT_REJECTED => 'Input-Format was rejected by the resource',
        qcREST_Interface_Response::STATUS_NO_FORMAT => 'No processor for the requested output-format was found',
        # qcREST_Interface_Response::STATUS_UNNAMED_CHILD_ERROR => '',
        qcREST_Interface_Response::STATUS_CLIENT_UNAUTHENTICATED => 'You need to authenticate',
        qcREST_Interface_Response::STATUS_CLIENT_UNAUTHORIZED => 'You are not authorized to access this resource',
        qcREST_Interface_Response::STATUS_UNSUPPORTED => 'Operation is not supported',
        qcREST_Interface_Response::STATUS_ERROR => 'An internal error happened',
      );
      
      foreach ($Response->getMeta ()  as $Key=>$Value)
        if (is_array ($Value))
          foreach ($Value as $Val)
            header ($Key . ': ' . $Val, false);
        else
          header ($Key. ': ' . $Value);
      
      header ('HTTP/1.1 ' . ($Code = $Response->getStatus ()) . (isset ($sMap [$Code]) ? ' ' . $sMap [$Code] : ''));
      header ('Status: ' . $Code . (isset ($sMap [$Code]) ? ' ' . $sMap [$Code] : ''));
      header ('X-Powered-By: qcREST/0.2 for CGI');
      header ('Content-Type: ' . $Response->getContentType ());
      
      $Content = $Response->getContent ();
      
      header ('Content-Length: ' . strlen ($Content));
      
      echo $Content;
      
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
      ob_start ();
      
      return parent::handle ($Callback, $Private, $Request);
    }
    // }}}
  }

?>