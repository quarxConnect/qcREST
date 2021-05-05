<?php

  /**
   * qcREST - Authorizer Interface
   * Copyright (C) 2017-2021 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  interface Authorizer {
    // {{{ authorizeRequest
    /**
     * Try to authorize a given request
     * 
     * @param Request $requestToAuthorize A request-object to authorize
     * @param Entity $finalEntity (optional) Resource or collection matching the request
     * @param Resource $parentResource (optional) Parent resource (if entity is a collection)
     * 
     * @access private
     * @return Events\Promise
     **/
    public function authorizeRequest (Request $requestToAuthorize, Entity $finalEntity = null, Resource $parentResource = null) : Events\Promise;
    // }}}
    
    // {{{ getAuthorizedMethods
    /**
     * Request the authorized methods for a given resource and/or collection
     * 
     * @param Entity $forEntity A resource or collection this request is for
     * @param Resource $parentResource (optional) Parent resource(if entity is a collection)
     * @param Request $theRequest (optional) A REST-Request assigned with this one
     * 
     * @access public
     * @return Events\Promise A promise that resolves into an array of Methods once fullfulled
     **/
    public function getAuthorizedMethods (Entity $forEntity, Resource $parentResource = null, Request $theRequest = null) : Events\Promise;
    // }}}
  }
