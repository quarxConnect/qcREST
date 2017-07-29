<?PHP

  /**
   * qcREST - Controller for qcEvents HTTP-Streams
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
  require_once ('qcEvents/Stream/HTTP/Header.php');
  require_once ('qcEvents/Server/HTTP.php');
  
  class qcREST_Controller_qcEvents extends qcREST_Controller_HTTP {
    /* Server-Signature for HTTP-Headers */
    const SIGNATURE = 'qcREST/0.2 for qcEvents';
    
    /* Virtual Base-URI */
    private $virtualBaseURI = null;
    
    // {{{ __construct
    /**
     * Create a new REST-Controller utilizing qcEvents-HTTP-API
     * 
     * @param qcEvents_Socket_Server $Pool (optional) Use this socket-pool exclusively
     * 
     * @access friendly
     * @return void
     **/
    function __construct (qcEvents_Socket_Server $Pool = null) {
      // Check wheter to setup our pool
      if (!$Pool)
        return;
      
      // Try to set socket-/stream-class
      if (!$Pool->setChildClass ('qcEvents_Server_HTTP', true))
        throw new exception ('Could not set child-class on pool');
      
      // Register a hook for the child-class
      $Pool->addChildHook ('httpdRequestReceived', array ($this, 'handleRequest'));
    }
    // }}}
    
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
     * @param qcREST_Interface_Entity $Resource (optional)
     * 
     * @access public
     * @return string
     **/
    public function getURI (qcREST_Interface_Entity $Resource = null) {
      # TODO: Servername? Port? Custom path?
      return ($this->virtualBaseURI !== null ? $this->virtualBaseURI : '') . parent::getEntityURI ($Resource);
    }
    // }}}
    
    // {{{ getRequest
    /**
     * Generate a Request-Object for a pending request
     * 
     * @remark Unimplemented because of structure of qcEvents. There is no global request-object
     * 
     * @see qcREST_Controller_qcEvents::getRequestFromHeader()
     * 
     * @access public
     * @return qcREST_Interface_Request
     **/
    public function getRequest () {
      return null;
    }
    // }}}
    
    // {{{ getRequestFromHeader
    /**
     * Create a REST-Request from a HTTP-Request-Header
     * 
     * @param qcEvents_Stream_HTTP_Header $Header
     * @param qcEvents_Server_HTTP $Server
     * @param string $Body (optional)
     * 
     * @access public
     * @return qcREST_Interface_Request
     **/
    public function getRequestFromHeader (qcEvents_Stream_HTTP_Header $Header, qcEvents_Server_HTTP $Server, $Body = null) {
      // Make sure it's a request
      if (!$Header->isRequest ()) {
        trigger_error ('Presented header is not a request');
        
        return;
      }
      
      // Check if the request-method is valid
      $Method = strtoupper ($Header->getMethod ());
      
      if (!defined ('qcREST_Interface_Request::METHOD_' . $Method)) {
        trigger_error ('Unknown method ' . $Method);
        
        return false;
      }
      
      // Map the method to local representation
      $Method = constant ('qcREST_Interface_Request::METHOD_' . $Method);
      
      // Strip parameters from URI
      $URI = $this->explodeURI ($Header->getURI ());
      
      // Check wheter to strip virtual base URI
      if ($this->virtualBaseURI !== null) {
        $vLength = strlen ($this->virtualBaseURI);
        
        if (substr ($URI [0], 0, $vLength + 1) == $this->virtualBaseURI . '/')
          $URI [0] = substr ($URI [0], $vLength);
        else
          trigger_error ('Received request without matching Base-URI-prefix');
      }
      
      // Process headers
      $Meta = $Header->getFields ();
      $ContentType = null;
      $Types = null;
      
      foreach ($Meta as $Key=>$Value)
        // Check for Accept-Header
        if (strcasecmp ($Key, 'Accept') == 0) {
          // Parse the header
          $Types = $this->explodeAcceptHeader ($Value);
          
          // Strip off from Meta
          unset ($Meta [$Key]);
        
        // Check for Content-Type-Header
        } elseif ((strcasecmp ($Key, 'Content-Type') == 0) && ($Body !== null)) {
          // Remember the value
          $ContentType = $Value;
          
          if (($p = strpos ($ContentType, ';')) !== false) {
            $ContentExtra = trim (substr ($ContentType, $p + 1));
            $ContentType = substr ($ContentType, 0, $p);
          } else
            $ContentExtra = null;
          
          // Strip off from Meta
          unset ($Meta [$Key]);
        }
      
      if (!$Types)
        $Types = $this->explodeAcceptHeader ('');
      
      // Finaly create new request
      # TODO: Determine if TLS was used here
      return new qcREST_Request ($this, $URI [0], $Method, $URI [1], $Meta, $Body, $ContentType, $Types, $Server->getRemoteHost ());
    }
    // }}}
    
    // {{{ getHeaderFromResponse
    /**
     * Create a HTTP-Response-Header from a REST-Response
     * 
     * @param qcREST_Interface_Response $Response
     * @param qcEvents_Stream_HTTP_Header $Request (optional) Initial HTTP-Request-Headers
     * 
     * @access public
     * @return qcEvents_Stream_HTTP_Header
     **/
    public function getHeaderFromResponse (qcREST_Interface_Response $Response, qcEvents_Stream_HTTP_Header $Request = null) {
      // Extract status-information
      $statusCode = $Response->getStatus ();
      $statusText = $this->getStatusCodeDescription ($statusCode);
      
      // Create HTTP-Response
      $httpResponse = new qcEvents_Stream_HTTP_Header (array (
        'HTTP/' . ($Request ? $Request->getVersion (true) : '1.1') . ' ' . $statusCode . ($statusText !== null ? ' ' . $statusText : ''),
        'Server: ' . self::SIGNATURE,
      ));
      
      // Append Meta
      if ($ContentType = $Response->getContentType ())
        $httpResponse->setField ('Content-Type', $ContentType);
      
      foreach ($Response->getMeta () as $Key=>$Value)
        $httpResponse->setField ($Key, $Value);
      
      foreach ($this->getMeta ($Response) as $Key=>$Value)
        if (!$httpResponse->hasField ($Key))
          $httpResponse->setField ($Key, $Value);
      
      // Return the result
      return $httpResponse;
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
      if ($Callback)
        call_user_func ($Callback, $this, $Response, true, $Private);
    }
    // }}}
    
    // {{{ handleRequest
    /**
     * Process a HTTP-Request
     * 
     * @param qcEvents_Server_HTTP $Server
     * @param qcEvents_Stream_HTTP_Header $Request
     * @param string $Body (optional)
     * 
     * @access public
     * @return void
     **/
    public function handleRequest (qcEvents_Server_HTTP $Server, qcEvents_Stream_HTTP_Header $Request, $Body = null) {
      // Try to create a REST-Request
        if (!is_object ($restRequest = $this->getRequestFromHeader ($Request, $Server, $Body))) {
          // Create Response-Header
          $Response = new qcEvents_Stream_HTTP_Header (array (
            'HTTP/' . $Request->getVersion (true) . ' 500 Internal Server Error',
            'Server: ' . self::SIGNATURE,
            'Content-Type: text/plain'   
          ));
             
          return $Server->httpdSetResponse ($Request, $Response, 'Internal Service Error' . "\r\n");
        }
         
        // Forward payload to the request
        $restRequest->setContent ($Body);
        unset ($Body);

        // Forward the request to REST-Handler
        return $this->handle (
          function (qcREST_Interface_Controller $Controller, qcREST_Interface_Request $restRequest, qcREST_Interface_Response $restResponse, $Status) use ($Request, $Server) {
            // Forward the response
            $Server->httpdSetResponse ($Request, $this->getHeaderFromResponse ($restResponse), $restResponse->getContent ());
          }, null,
          $restRequest
        );
    }
    // }}}
  }

?>