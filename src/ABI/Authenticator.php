<?php

  /**
   * qcREST - Authenticator Interface
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
  
  interface Authenticator {
    // {{{ authenticateRequest
    /**
     * Try to authenticate a given request
     * 
     * @param Request $requestToAuthenticate
     *    
     * @access public
     * @return Events\Promise A promise that resolves to a Entity\Card-Instance or NULL
     **/
    public function authenticateRequest (Request $requestToAuthenticate) : Events\Promise;
    // }}}
    
    // {{{ getSchemes
    /**
     * Retrive a list of supported authentication-schemes.
     * 
     * The list is represented by an array of associative arrays, each with the following keys:
     * 
     *   scheme: A well known name of the scheme
     *   realm:  A realm for the scheme
     * 
     * @access public
     * @return array
     **/
    public function getSchemes () : array;
    // }}}
  }
