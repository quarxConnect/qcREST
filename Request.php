<?PHP

  /**
   * qcREST - Request
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
  
  require_once ('qcREST/Interface/Request.php');
  
  class qcREST_Request implements qcREST_Interface_Request {
    private $requestURI = '';
    private $requestMethod = 0;
    private $requestParameters = array ();
    private $requestContent = '';
    private $requestContentType = '';
    private $acceptedContentTypes = array ();
    private $authenticatedUser = null;
    private $Meta = array ();
    
    // {{{ __construct
    /**
     * Create a new Request
     * 
     * @param string $URI The requested URI
     * @param string $Method The used request-method
     * @param array $Parameters Additional Parameters for this request
     * @param array $Meta Meta-Data for this request
     * @param string $Content The payload from the request
     * @param string $ContentType Type of payload
     * @param array $acceptedContentTypes List of accepted content-types
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($URI, $Method, array $Parameters, array $Meta, $Content, $ContentType, array $acceptedContentTypes) {
      $this->requestURI = $URI;
      $this->requestMethod = $Method;
      $this->requestParameters = $Parameters;
      $this->requestContent = $Content;
      $this->requestContentType = $ContentType;
      $this->acceptedContentTypes = $acceptedContentTypes;
      $this->Meta = $Meta;
    }
    // }}}
    
    // {{{ getMethod
    /**
     * Retrive the Method of this request
     * 
     * @access public
     * @return enum
     **/
    public function getMethod () {
      return $this->requestMethod;
    }
    // }}}
    
    // {{{ getURI
    /**
     * Retrive the URI of the request, this should be the local URI without any prefix of the implementation
     * 
     * @access public
     * @return string
     **/
    public function getURI () {
      return $this->requestURI;
    }
    // }}}
    
    // {{{ getParameters
    /**
     * Retrive additional parameters for this request
     * 
     * @access public
     * @return array
     **/
    public function getParameters () {
      return $this->requestParameters;
    }
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
    public function getMeta ($Key = null) {
      if ($Key === null)
        return $this->Meta;
      
      if (isset ($this->Meta [$Key]))
        return $this->Meta [$Key];
    }
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
    public function getUser () {
      return $this->authenticatedUser;
    }
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
    public function setUser (qcEntity_Card $User) {
      $this->authenticatedUser = $User;
    }
    // }}}
    
    // {{{ getContentType
    /**
     * Retrive the content-type of the request-body
     * 
     * @access public
     * @return string May be NULL if no request-body is present
     **/
    public function getContentType () {
      return $this->requestContentType;
    }
    // }}}
    
    // {{{ getContent
    /**
     * Retrive the request-body
     * 
     * @access public
     * @return string May be NULL if no request-body is present
     **/
    public function getContent () {
      return $this->requestContent;
    }
    // }}}
    
    // {{{ setContent
    /**
     * Store a new request-body
     * 
     * @param string $Content
     * 
     * @access public
     * @return void
     **/
    public function setContent ($Content) {
      $this->requestContent = $Content;
    }
    // }}}
    
    // {{{ getAcceptedContentTypes
    /**
     * Retrive the accepted mime-types for a response
     *  
     * @access public
     * @return array
     **/
    public function getAcceptedContentTypes () {
      return $this->acceptedContentTypes;
    }
    // }}}
  }

?>