<?php

  /**
   * qcREST - Response Interface
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
  
  interface Response {
    public const STATUS_OK = 200;
    public const STATUS_CREATED = 201;
    public const STATUS_STORED = 204;
    public const STATUS_REMOVED = 204;
    
    public const STATUS_NOT_FOUND = 404;
    public const STATUS_NOT_ALLOWED = 405;
    
    // Input-Errors
    public const STATUS_CLIENT_ERROR = 400;
    public const STATUS_CLIENT_UNAUTHENTICATED = 401;
    public const STATUS_CLIENT_UNAUTHORIZED = 401;
    public const STATUS_FORMAT_MISSING = 400;
    public const STATUS_FORMAT_ERROR = 400;
    public const STATUS_FORMAT_UNSUPPORTED = 415;
    public const STATUS_FORMAT_REJECTED = 422;
    
    // Output-Errors
    public const STATUS_NO_FORMAT = 406;
    public const STATUS_UNNAMED_CHILD_ERROR = 405;
    
    public const STATUS_UNSUPPORTED = 501;
    public const STATUS_ERROR = 500;
    
    // {{{ getRequest
    /**
     * Retrive the request this response is generated for
     * 
     * @access public
     * @return Request
     **/
    public function getRequest () : Request;
    // }}}
    
    // {{{ getStatus
    /**
     * Retrive the status of the response
     * 
     * @access public
     * @return enum
     **/
    public function getStatus () : int;
    // }}}
    
    // {{{ setStatus
    /**
     * Update the status
     * 
     * @param enum $newStatus
     * 
     * @access public
     * @return void
     **/
    public function setStatus (int $newStatus) : void;
    // }}}
    
    // {{{ getMeta
    /**
     * Retrive the meta-data of this response
     * 
     * @param string $metaKey (optional)
     * 
     * @access public
     * @return mixed
     **/
    public function getMeta (string $metaKey = null);
    // }}}
    
    // {{{ setMeta
    /**
     * Set new meta-data for this response
     * 
     * @param string $metaKey
     * @param mixed $metaValue
     * 
     * @access public
     * @return void
     **/
    public function setMeta (string $metaKey, $metaValue) : void;
    // }}}
    
    // {{{ getContentType
    /**
     * Retrive the content-type of the response
     * 
     * @access public
     * @return string
     **/
    public function getContentType () : string;
    // }}}
    
    // {{{ getContent
    /**
     * Retrive the whole response-data
     * 
     * @access public
     * @return string
     **/
    public function getContent () : string;
    // }}}
  }
