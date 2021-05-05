<?php

  /**
   * qcREST - Request Interface
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

  namespace quarxConnect\REST\ABI;
  use \quarxConnect\Events;
  use \quarxConnect\Entity;
  
  interface Request {
    public const METHOD_GET = 0;
    public const METHOD_POST = 1;
    public const METHOD_PUT = 2;
    public const METHOD_PATCH = 3;
    public const METHOD_DELETE = 4;
    public const METHOD_HEAD = 5;
    public const METHOD_OPTIONS = 6;
    
    // {{{ getController
    /**
     * Retrive the controller for this request
     * 
     * @access public
     * @return Controller
     **/
    public function getController () : Controller;
    // }}}
    
    // {{{ getMethod
    /**
     * Retrive the Method of this request
     * 
     * @access public
     * @return enum
     **/
    public function getMethod () : int;
    // }}}
    
    // {{{ getURI
    /**
     * Retrive the URI of the request, this should be the local URI without any prefix of the implementation
     * 
     * @access public
     * @return string
     **/
    public function getURI () : string;
    // }}}
    
    // {{{ getFullURI
    /**
     * Retrive the full URI of the request including base-url
     * 
     * @access public
     * @return string
     **/
    public function getFullURI () : string;
    // }}}
    
    // {{{ getParameters
    /**
     * Retrive additional parameters for this request
     * 
     * @access public
     * @return array
     **/
    public function getParameters () : array;
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
    public function getParameter (string $parameterName);
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
    public function getMeta (string $metaKey = null);
    // }}}
    
    // {{{ getIP
    /**
     * Retrive the IP-Address this request was issued from
     * 
     * @access public
     * @return string
     **/
    public function getIP () : string;
    // }}}
    
    // {{{ isTLS
    /**
     * Check if the request was made using TLS-encryption
     * 
     * @access public
     * @return bool
     **/
    public function isTLS () : bool;
    // }}}
    
    // {{{ getUser
    /**
     * Retrive the user that was authenticated with this request
     * Authentication means any kind of identification but NOT authorized. Resources have to check on their own
     * if a user is authorized to access the resource!
     * 
     * @access public
     * @return Entity\Card
     **/
    public function getUser () : ?Entity\Card;
    // }}}
    
    // {{{ setUser
    /**
     * Store a user-entity on this request that is belived to be authenticated from the request
     * 
     * @param Entity\Card $authenticateUser
     * 
     * @access public
     * @return void
     **/
    public function setUser (Entity\Card $authenticateUser) : void;
    // }}}
    
    // {{{ getContentType
    /**
     * Retrive the content-type of the request-body
     * 
     * @access public
     * @return string May be NULL if no request-body is present
     **/
    public function getContentType () : ?string;
    // }}}
    
    // {{{ getContent
    /**
     * Retrive the request-body
     * 
     * @access public
     * @return string May be NULL if no request-body is present
     **/
    public function getContent () : ?string;
    // }}}
    
    // {{{ getAcceptedContentTypes
    /**
     * Retrive the accepted mime-types for a response
     * 
     * @access public
     * @return array
     **/
    public function getAcceptedContentTypes () : array;
    // }}}
    
    // {{{ hasSession
    /**
     * Check if this request contains a session
     * 
     * @access public
     * @return bool
     **/
    public function hasSession () : bool;
    // }}}
    
    // {{{ getSession
    /**
     * Retrive a session for this request
     * If no session exists a new one will be created
     * 
     * @access public
     * @return Events\Promise
     **/
    public function getSession () : Events\Promise;
    // }}}
  }
