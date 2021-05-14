<?php

  /**
   * qcREST - Response
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
  
  class Response implements ABI\Response {
    private $parentRequest = null;
    private $responseStatus = ABI\Response::STATUS_OK;
    private $responseMeta = [ ];
    private $contentType = 'application/octet-stream';
    private $responseContent = null;
    
    // {{{ __construct
    /**
     * Create a new response-object
     * 
     * @param ABI\Request $parentRequest Request-Object for this response
     * @param int $responseStatus
     * @param string $responseContent (optional)
     * @param string $contentType (optional)
     * @param array $responseMeta (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (ABI\Request $parentRequest, int $responseStatus, string $responseContent = null, string $contentType = null, array $responseMeta = [ ]) {
      $this->parentRequest = $parentRequest;
      $this->responseStatus = $responseStatus;
      $this->responseMeta = $responseMeta;
      $this->responseContent = $responseContent;
      $this->contentType = $contentType ?? 'application/octet-stream';
    }
    // }}}
    
    // {{{ getRequest
    /**
     * Retrive the request this response is generated for
     * 
     * @access public
     * @return ABI\Request
     **/
    public function getRequest () : ABI\Request {
      return $this->parentRequest;
    }
    // }}}
    
    // {{{ getStatus
    /**
     * Retrive the status of the response
     * 
     * @access public
     * @return enum
     **/
    public function getStatus () : int {
      return $this->responseStatus;
    }
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
    public function setStatus (int $newStatus) : void {
      $this->responseStatus = $newStatus;
    }
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
    public function getMeta (string $metaKey = null) {
      if ($metaKey === null)
        return $this->responseMeta;
      
      return $this->responseMeta [$metaKey] ?? null;
    }
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
    public function setMeta (string $metaKey, $metaValue) : void {
      $this->responseMeta [$metaKey] = $metaValue;
    }
    // }}}
    
    // {{{ getContentType
    /**
     * Retrive the content-type of the response
     * 
     * @access public
     * @return string
     **/
    public function getContentType () : string {
      return $this->contentType;
    }
    // }}}

    // {{{ getContent
    /**
     * Retrive the whole response-data
     * 
     * @access public
     * @return string
     **/
    public function getContent () : ?string {
      return $this->responseContent;
    }
    // }}}
  }
