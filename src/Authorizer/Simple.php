<?php

  /**
   * qcREST - Simple/Dummy Authorizer
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

  namespace quarxConnect\REST\Authorizer;
  use \quarxConnect\Events;
  use \quarxConnect\REST;
  
  class Simple implements REST\ABI\Authorizer {
    // {{{ authorizeRequest
    /**
     * Try to authorize a given request
     * 
     * @param REST\ABI\Request $requestToAuthorize A request-object to authorize
     * @param REST\ABI\Entity $finalEntity (optional) Resource or collection matching the request
     * @param REST\ABI\Resource $parentResource (optional) Parent resource (if entity is a collection)
     * 
     * @access private
     * @return Events\Promise
     **/
    public function authorizeRequest (REST\ABI\Request $requestToAuthorize, REST\ABI\Entity $finalEntity = null, REST\ABI\Resource $parentResource = null) : Events\Promise {
      if ($requestToAuthorize->getUser ())
        return Events\Promise::resolve ();
      
      return Events\Promise::reject ('No authenticated user');
    }
    // }}}
    
    // {{{ getAuthorizedMethods
    /**
     * Request the authorized methods for a given resource and/or collection
     * 
     * @param REST\ABI\Entity $forEntity A resource or collection this request is for
     * @param REST\ABI\Resource $parentResource (optional) Parent resource(if entity is a collection)
     * @param REST\ABI\Request $theRequest (optional) A REST-Request assigned with this one
     * 
     * @access public
     * @return Events\Promise A promise that resolves into an array of Methods once fullfulled
     **/
    public function getAuthorizedMethods (REST\ABI\Entity $forEntity, REST\ABI\Resource $parentResource = null, REST\ABI\Request $theRequest = null) : Events\Promise {
      if ($theRequest && $theRequest->getUser ())
        return Events\Promise::resolve ([
          REST\ABI\Request::METHOD_GET,
          REST\ABI\Request::METHOD_POST,
          REST\ABI\Request::METHOD_PUT,
          REST\ABI\Request::METHOD_PATCH,
          REST\ABI\Request::METHOD_DELETE,
          REST\ABI\Request::METHOD_OPTIONS,
          REST\ABI\Request::METHOD_HEAD,
        ]);
      
      return Events\Promise::resolve ([ ]);
    }
    // }}}
  }
