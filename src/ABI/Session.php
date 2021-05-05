<?php

  /**
   * qcREST - Session Interface
   * Copyright (C) 2018-2021 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  interface Session extends \ArrayAccess, \Traversable {
    // {{{ hasSession
    /**
     * Check if a given request contains a session
     * 
     * @param Request $theRequest
     * 
     * @access public
     * @return bool
     **/
    public static function hasSession (Request $theRequest) : bool;
    // }}}
    
    // {{{ __construct
    /**
     * Open/Create a session for a given request
     * 
     * @param Request $forRequest
     * 
     * @access friendly
     * @return void
     **/
    function __construct (Request $forRequest);
    // }}}
    
    // {{{ getID
    /**
     * Retrive the ID of this session
     * 
     * @access public
     * @return string
     **/
    public function getID () : string;
    // }}}
    
    // {{{ addToResponse
    /**
     * Register this session on a REST-Response
     * 
     * @param Response $theResponse
     * 
     * @access public
     * @return void
     **/
    public function addToResponse (Response $theResponse) : void;
    // }}}
    
    // {{{ load
    /**
     * Load this session from its storage
     * 
     * @access public
     * @return Events\Promise
     **/
    public function load () : Events\Promise;
    // }}}
    
    // {{{ store
    /**
     * Store this session anywhere
     * 
     * @access public
     * @return Events\Promise
     **/
    public function store () : Events\Promise;
    // }}}
  }
