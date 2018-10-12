<?PHP

  /**
   * qcREST - Response Interface
   * Copyright (C) 2016 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  interface qcREST_Interface_Response {
    const STATUS_OK = 200;
    const STATUS_CREATED = 201;
    const STATUS_STORED = 204;
    const STATUS_REMOVED = 204;
    
    const STATUS_NOT_FOUND = 404;
    const STATUS_NOT_ALLOWED = 405;
    
    // Input-Errors
    const STATUS_CLIENT_ERROR = 400;
    const STATUS_CLIENT_UNAUTHENTICATED = 401;
    const STATUS_CLIENT_UNAUTHORIZED = 401;
    const STATUS_FORMAT_MISSING = 400;
    const STATUS_FORMAT_ERROR = 400;
    const STATUS_FORMAT_UNSUPPORTED = 415;
    const STATUS_FORMAT_REJECTED = 422;
    
    // Output-Errors
    const STATUS_NO_FORMAT = 406;
    const STATUS_UNNAMED_CHILD_ERROR = 405;
    
    const STATUS_UNSUPPORTED = 501;
    const STATUS_ERROR = 500;
    
    // {{{ getRequest
    /**
     * Retrive the request this response is generated for
     * 
     * @access public
     * @return qcREST_Interfaces_Request
     **/
    public function getRequest ();
    // }}}
    
    // {{{ getStatus
    /**
     * Retrive the status of the response
     * 
     * @access public
     * @return enum
     **/
    public function getStatus ();
    // }}}
    
    // {{{ setStatus
    /**
     * Update the status
     * 
     * @param enum $Status
     * 
     * @access public
     * @return void
     **/
    public function setStatus ($Status);
    // }}}
    
    // {{{ getMeta
    /**
     * Retrive the meta-data of this response
     * 
     * @access public
     * @return array
     **/
    public function getMeta ($Key = null);
    // }}}
    
    // {{{ setMeta
    /**
     * Set new meta-data for this response
     * 
     * @param string $Key
     * @param mixed $Value
     * 
     * @access public
     * @return void
     **/
    public function setMeta ($Key, $Value);
    // }}}
    
    // {{{ getContentType
    /**
     * Retrive the content-type of the response
     * 
     * @access public
     * @return string
     **/
    public function getContentType ();
    // }}}
    
    // {{{ getContent
    /**
     * Retrive the whole response-data
     * 
     * @access public
     * @return string
     **/
    public function getContent ();
    // }}}
  }

?>