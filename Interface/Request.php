<?PHP

  /**
   * qcREST - Request Interface
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
  
  interface qcREST_Interface_Request {
    const METHOD_GET = 0;
    const METHOD_POST = 1;
    const METHOD_PUT = 2;
    const METHOD_PATCH = 3;
    const METHOD_DELETE = 4;
    const METHOD_HEAD = 5;
    const METHOD_OPTIONS = 6;
    
    // {{{ getMethod
    /**
     * Retrive the Method of this request
     * 
     * @access public
     * @return enum
     **/
    public function getMethod ();
    // }}}
    
    // {{{ getURI
    /**
     * Retrive the URI of the request, this should be the local URI without any prefix of the implementation
     * 
     * @access public
     * @return string
     **/
    public function getURI ();
    // }}}
    
    // {{{ getParameters
    /**
     * Retrive additional parameters for this request
     * 
     * @access public
     * @return array
     **/
    public function getParameters ();
    // }}}
    
    // {{{ getMeta
    /**
     * Retrive given or all meta-data from this request
     * 
     * @param string $Key (optional)
     * 
     * @access public
     * @return mixed
     **/
    public function getMeta ($Key = null);
    // }}}
    
    // {{{ getIP
    /**
     * Retrive the IP-Address this request was issued from
     * 
     * @access public
     * @return string
     **/
    public function getIP ();
    // }}}
    
    // {{{ isTLS
    /**
     * Check if the request was made using TLS-encryption
     * 
     * @access public
     * @return bool
     **/
    public function isTLS ();
    // }}}
    
    // {{{ getUser
    /**
     * Retrive the user that was authenticated with this request
     * Authentication means any kind of identification but NOT authorized. Resources have to check on their own
     * if a user is authorized to access the resource!
     * 
     * @access public
     * @return qcEntity_Card
     **/
    public function getUser ();
    // }}}
    
    // {{{ setUser
    /**
     * Store a user-entity on this request that is belived to be authenticated from the request
     * 
     * @param qcEntity_Card $User
     * 
     * @access public
     * @return void
     **/
    public function setUser (qcEntity_Card $User);
    // }}}
    
    // {{{ getContentType
    /**
     * Retrive the content-type of the request-body
     * 
     * @access public
     * @return string May be NULL if no request-body is present
     **/
    public function getContentType ();
    // }}}
    
    // {{{ getContent
    /**
     * Retrive the request-body
     * 
     * @access public
     * @return string May be NULL if no request-body is present
     **/
    public function getContent ();
    // }}}
    
    // {{{ getAcceptedContentTypes
    /**
     * Retrive the accepted mime-types for a response
     * 
     * @access public
     * @return array
     **/
    public function getAcceptedContentTypes ();
    // }}}
  }

?>