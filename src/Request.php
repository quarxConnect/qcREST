<?php

  /**
   * qcREST - Request
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
  
  namespace quarxConnect\REST;
  use \quarxConnect\Entity;
  use \quarxConnect\Events;
  
  class Request implements ABI\Request {
    private $Controller = null;
    private $requestURI = '';
    private $requestMethod = 0;
    private $requestParameters = [ ];
    private $requestContent = '';
    private $requestContentType = '';
    private $acceptedContentTypes = [ ];
    private $authenticatedUser = null;
    private $Meta = [ ];
    private $IP = '';
    private $TLS = false;
    private $Session = null;
    
    // {{{ __construct
    /**
     * Create a new Request
     * 
     * @param ABI\Controller $Controller Instance of the controller that received this request
     * @param string $URI The requested URI
     * @param enum $Method The used request-method
     * @param array $Parameters Additional Parameters for this request
     * @param array $Meta Meta-Data for this request
     * @param string $requestBody The payload from the request (optional)
     * @param string $contentType Type of payload (optional)
     * @param array $acceptedContentTypes List of accepted content-types (optional)
     * @param string $requestIP IP-Address the request was issued from (optional)
     * @param bool $TLS The request was made using TLS-Encryption (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (ABI\Controller $Controller, string $URI, int $Method, array $Parameters, array $Meta, string $requestBody = null, string $contentType = null, array $acceptedContentTypes = null, string $requestIP = null, bool $TLS = false) {
      $this->Controller = $Controller;
      $this->requestURI = $URI;
      $this->requestMethod = $Method;
      $this->requestParameters = $Parameters;
      $this->requestContent = $requestBody;
      $this->requestContentType = $contentType;
      $this->acceptedContentTypes = $acceptedContentTypes ?? [ ];
      $this->Meta = $Meta;
      $this->IP = $requestIP ?? '0.0.0.0';
      $this->TLS = !!$TLS;
    }
    // }}}
    
    // {{{ getController
    /**
     * Retrive the controller for this request
     * 
     * @access public
     * @return ABI\Controller
     **/
    public function getController () : ABI\Controller {
      return $this->Controller;
    }
    // }}}
    
    // {{{ getMethod
    /**
     * Retrive the Method of this request
     * 
     * @access public
     * @return enum
     **/
    public function getMethod () : int {
      return $this->requestMethod;
    }
    // }}}
    
    // {{{ getURI
    /**
     * Retrive the URI of the request, this should be the local URI without any prefix of the implementation
     * 
     * @access public
     * @return string
     **/
    public function getURI () : string {
      return $this->requestURI;
    }
    // }}}
    
    // {{{ getFullURI
    /**
     * Retrive the full URI of the request including prefix of the implementation
     * 
     * @access public
     * @return string
     **/
    public function getFullURI () : string {
      $baseURI = $this->Controller->getURI ();
      
      if (($this->requestURI [0] == '/') && (substr ($baseURI, -1, 1) == '/'))
        $baseURI = substr ($baseURI, 0, -1);
      
      return $baseURI . $this->requestURI;
    }
    // }}}
    
    // {{{ getParameters
    /**
     * Retrive additional parameters for this request
     * 
     * @access public
     * @return array
     **/
    public function getParameters () : array {
      return $this->requestParameters;
    }
    // }}}
    
    // {{{ getParameter
    /**
     * Retrive a named parameter of this request
     * 
     * @param string $parameterName
     * 
     * @access public
     * @return mixed
     **/
    public function getParameter (string $parameterName) {
      return $this->requestParameters [$parameterName] ?? null;
    }
    // }}}
    
    // {{{ getMeta
    /**
     * Retrive given or all meta-data from this request
     * 
     * @param string $metaKey (optional)
     * 
     * @access public
     * @return mixed
     **/
    public function getMeta (string $metaKey = null) {
      if ($metaKey === null)
        return $this->Meta;
      
      return $this->Meta [$metaKey] ?? null;
    }
    // }}}
    
    // {{{ getIP
    /**
     * Retrive the IP-Address this request was issued from
     * 
     * @access public
     * @return string
     **/
    public function getIP () : string {
      return $this->IP;
    }
    // }}}
    
    // {{{ isTLS
    /**
     * Check if the request was made using TLS-encryption
     * 
     * @access public
     * @return bool
     **/
    public function isTLS () : bool {
      return $this->TLS;
    }
    // }}}
    
    // {{{ getUser
    /**
     * Retrive the user that was authenticated with this request
     * 
     * Authentication means any kind of identification but NOT authorized. Resources have to check on their own
     * if a user is authorized to access the resource!
     * 
     * @access public 
     * @return Entity\Card
     **/
    public function getUser () : ?Entity\Card {
      return $this->authenticatedUser;
    }
    // }}}
    
    // {{{ setUser
    /**
     * Store a user-entity on this request that is belived to be authenticated from the request
     * 
     * @param Entity\Card $authenticatedUser
     * 
     * @access public
     * @return void
     **/
    public function setUser (Entity\Card $authenticatedUser) : void {
      $this->authenticatedUser = $authenticatedUser;
    }
    // }}}
    
    // {{{ getContentType
    /**
     * Retrive the content-type of the request-body
     * 
     * @access public
     * @return string May be NULL if no request-body is present
     **/
    public function getContentType () : ?string {
      return $this->requestContentType;
    }
    // }}}
    
    // {{{ getContent
    /**
     * Retrive the request-body
     * 
     * @access public
     * @return string May be NULL if no request-body is present
     **/
    public function getContent () : ?string {
      return $this->requestContent;
    }
    // }}}
    
    // {{{ setContent
    /**
     * Store a new request-body
     * 
     * @param string $requestContent
     * 
     * @access public
     * @return void
     **/
    public function setContent (string $requestContent) : void {
      $this->requestContent = $requestContent;
    }
    // }}}
    
    // {{{ getAcceptedContentTypes
    /**
     * Retrive the accepted mime-types for a response
     *  
     * @access public
     * @return array
     **/
    public function getAcceptedContentTypes () : array {
      return $this->acceptedContentTypes;
    }
    // }}}
    
    // {{{ hasSession
    /**
     * Check if this request contains a session
     * 
     * @access public
     * @return bool  
     **/
    public function hasSession () : bool {
      return (is_object ($this->Session) || Session::hasSession ($this));
    }
    // }}}
    
    // {{{ getSession
    /**
     * Retrive a session for this request
     * If no session exists a new one will be created
     * 
     * @access public
     * @return Events\Promise
     **/
    public function getSession () : Events\Promise {
      // Check if we have a session available
      if ($this->Session)
        return Events\Promise::resolve ($this->Session);
      
      // Create a new session
      $this->Session = new Session ($this);
      
      // Instruct the session to load
      return $this->Session->load ()->then (
        function () {
          return $this->Session;
        }
      );
    }
    // }}}
  }
