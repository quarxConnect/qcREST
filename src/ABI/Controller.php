<?php

  /**
   * qcREST - Controller Interface
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
  
  interface Controller {
    // {{{ getURI
    /**
     * Retrive the URI of this controller or a resource/collection related to this one
     * 
     * @param Entity $forEntity (optional)
     * 
     * @access public
     * @return string
     **/
    public function getURI (Entity $forEntity = null) : string;
    // }}}
    
    // {{{ setRootElement
    /**
     * Set the root resource for this controller
     * 
     * @param Entity $rootEntity
     * 
     * @access public
     * @return void
     **/
    public function setRootElement (Entity $rootEntity) : void;
    // }}}
    
    // {{{ addProcessor
    /**
     * Register a new input/output-processor on this controller
     * 
     * @param Processor $newProcessor
     * @param array $mimeTypes (optional) Restrict the processor for these  types
     * 
     * @access public
     * @return void
     **/
    public function addProcessor (Processor $newProcessor, array $mimeTypes = null) : void;
    // }}}
    
    // {{{ addAuthenticator
    /**
     * Register a new authenticator on this controller
     * 
     * @param Authenticator $newAuthenticator
     * 
     * @access public
     * @return void
     **/
    public function addAuthenticator (Authenticator $newAuthenticator) : void;
    // }}}
    
    
    // {{{ getRequest
    /**
     * Generate a Request-Object for a pending request
     * 
     * @access public
     * @return Request
     **/
    public function getRequest () : ?Request;
    // }}}
    
    // {{{ setResponse
    /** 
     * Write out a response for a previous request
     * 
     * @param Response $newResponse The response
     * 
     * @access public
     * @return Events\Promise
     **/
    public function setResponse (Response $newResponse) : Events\Promise;
    // }}}
    
    // {{{ handle
    /**
     * Try to process a request, if no request is given explicitly try to fetch one from SAPI
     * 
     * @param Request $theRequest (optional)
     * 
     * @access public
     * @return Events\Promise
     **/
    public function handle (Request $theRequest = null) : Events\Promise;
    // }}}
  }
