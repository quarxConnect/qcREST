<?php

  /**
   * qcREST - Controller for qcEvents HTTP-Streams
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
  
  class Events extends HTTP {
    /* Server-Signature for HTTP-Headers */
    protected const SIGNATURE = 'qcREST/0.2 for qcEvents';
    
    /* Virtual Base-URI */
    private $virtualBaseURI = null;
    
    // {{{ __construct
    /**
     * Create a new REST-Controller utilizing qcEvents-HTTP-API
     * 
     * @param \quarxConnect\Events\Socket\Server $Pool (optional) Use this socket-pool exclusively
     * 
     * @access friendly
     * @return void
     **/
    function __construct (\quarxConnect\Events\Socket\Server $Pool = null) {
      // Run parent construtor first
      parent::__construct ();
      
      // Check wheter to setup our pool
      if (!$Pool)
        return;
      
      // Try to set socket-/stream-class
      if (!$Pool->setChildClass (\quarxConnect\Events\Server\HTTP::class, true))
        throw new \Exception ('Could not set child-class on pool');
      
      // Register a hook for the child-class
      $Pool->addChildHook ('httpdRequestReceived', [ $this, 'handleRequest' ]);
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
     * @see Events::getRequestFromHeader()
     * 
     * @access public
     * @return ABI\Request
     **/
    public function getRequest () : ?ABI\Request {
      return null;
    }
    // }}}
    
    // {{{ getRequestFromHeader
    /**
     * Create a REST-Request from a HTTP-Request-Header
     * 
     * @param \quarxConnect\Events\Stream\HTTP\Header $requestHeader
     * @param \quarxConnect\Events\Server\HTTP $Server
     * @param string $Body (optional)
     * 
     * @access public
     * @return ABI\Request
     **/
    public function getRequestFromHeader (\quarxConnect\Events\Stream\HTTP\Header $requestHeader, \quarxConnect\Events\Server\HTTP $Server, string $Body = null) : ABI\Request {
      // Make sure it's a request
      if (!$requestHeader->isRequest ())
        throw new \Error ('Presented header is not a request');
      
      // Check if the request-method is valid
      $requestMethod = strtoupper ($requestHeader->getMethod ());
      
      if (!defined (ABI\Request::class . '::METHOD_' . $requestMethod))
        throw new \Exception ('Unknown method ' . $requestMethod);
      
      // Map the method to local representation
      $requestMethod = constant (ABI\Request::class . '::METHOD_' . $requestMethod);
      
      // Strip parameters from URI
      $URI = $this->explodeURI ($requestHeader->getURI ());
      
      // Check wheter to strip virtual base URI
      if ($this->virtualBaseURI !== null) {
        $vLength = strlen ($this->virtualBaseURI);
        
        if (substr ($URI [0], 0, $vLength + 1) == $this->virtualBaseURI . '/')
          $URI [0] = substr ($URI [0], $vLength);
        else
          trigger_error ('Received request without matching Base-URI-prefix');
      }
      
      // Process headers
      $Meta = $requestHeader->getFields ();
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
      return new REST\Request ($this, $URI [0], $requestMethod, $URI [1], $Meta, $Body, $ContentType, $Types, $Server->getRemoteHost ());
    }
    // }}}
    
    // {{{ getHeaderFromResponse
    /**
     * Create a HTTP-Response-Header from a REST-Response
     * 
     * @param ABI\Response $Response
     * @param \quarxConnect\Events\Stream\HTTP\Header $Request (optional) Initial HTTP-Request-Headers
     * 
     * @access public
     * @return \quarxConnect\Events\Stream\HTTP\Header
     **/
    public function getHeaderFromResponse (ABI\Response $Response, \quarxConnect\Events\Stream\HTTP\Header $Request = null) : \quarxConnect\Events\Stream\HTTP\Header {
      // Extract status-information
      $statusCode = $Response->getStatus ();
      $statusText = $this->getStatusCodeDescription ($statusCode);
      
      // Create HTTP-Response
      $httpResponse = new \quarxConnect\Events\Stream\HTTP\Header ([
        'HTTP/' . ($Request ? $Request->getVersion (true) : '1.1') . ' ' . $statusCode . ($statusText !== null ? ' ' . $statusText : ''),
        'Server: ' . self::SIGNATURE,
      ]);
      
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
     * @param ABI\Response $Response The response
     * 
     * @access public
     * @return \quarxConnect\Events\Promise
     **/
    public function setResponse (ABI\Response $Response) : \quarxConnect\Events\Promise {
      return \quarxConnect\Events\Promise::resolve ($Response);
    }
    // }}}
    
    // {{{ handleRequest
    /**
     * Process a HTTP-Request
     * 
     * @param \quarxConnect\Events\Server\HTTP $httpServer
     * @param \quarxConnect\Events\Stream\HTTP\Header $requestHeader
     * @param string $requestBody (optional)
     * 
     * @access public
     * @return void
     **/
    public function handleRequest (\quarxConnect\Events\Server\HTTP $httpServer, \quarxConnect\Events\Stream\HTTP\Header $requestHeader, string $requestBody = null) : void {
      // Try to create a REST-Request
      try {
        $restRequest = $this->getRequestFromHeader ($requestHeader, $httpServer, $requestBody);
        
        // Forward payload to the request
        $restRequest->setContent ($requestBody);
        unset ($requestBody);
        
        // Forward the request to REST-Handler
        $this->handle ($restRequest)->then (
          function (ABI\Response $restResponse) use ($requestHeader, $httpServer) {
            return $httpServer->httpdSetResponse (
              $requestHeader,
              $this->getHeaderFromResponse ($restResponse),
              $restResponse->getContent ()
            );
          },
          function (\Throwable $error) use ($requestHeader) {
            return $httpServer->httpdSetResponse (
              $requestHeader,
              new \quarxConnect\Events\Stream\HTTP\Header ([
                'HTTP/' . $requestHeader->getVersion (true) . ' 500 Internal Server Error',
                'Server: ' . $this::SIGNATURE,
                'Content-Type: text/plain'   
              ]),
              (string)$error . "\r\n"
            );
          }
        );
      } catch (\Throwable $error) {
        $httpServer->httpdSetResponse (
          $requestHeader,
          new \quarxConnect\Events\Stream\HTTP\Header ([
            'HTTP/' . $requestHeader->getVersion (true) . ' 500 Internal Server Error',
            'Server: ' . $this::SIGNATURE,
            'Content-Type: text/plain'   
          ]),
          (string)$error . "\r\n"
        );
      }
    }
    // }}}
  }
