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
    private $Controller = null;
    private $requestURI = '';
    private $requestMethod = 0;
    private $requestParameters = array ();
    private $requestContent = '';
    private $requestContentType = '';
    private $acceptedContentTypes = array ();
    private $authenticatedUser = null;
    private $Meta = array ();
    private $IP = '';
    private $TLS = false;
    
    // {{{ __construct
    /**
     * Create a new Request
     * 
     * @param qcREST_Controller $Controller Instance of the controller that received this request
     * @param string $URI The requested URI
     * @param string $Method The used request-method
     * @param array $Parameters Additional Parameters for this request
     * @param array $Meta Meta-Data for this request
     * @param string $Content The payload from the request
     * @param string $ContentType Type of payload
     * @param array $acceptedContentTypes List of accepted content-types
     * @param string $IP IP-Address the request was issued from
     * @param bool $TLS The request was made using TLS-Encryption (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (qcREST_Controller $Controller, $URI, $Method, array $Parameters, array $Meta, $Content, $ContentType, array $acceptedContentTypes, $IP, $TLS = false) {
      $this->Controller = $Controller;
      $this->requestURI = $URI;
      $this->requestMethod = $Method;
      $this->requestParameters = $Parameters;
      $this->requestContent = $Content;
      $this->requestContentType = $ContentType;
      $this->acceptedContentTypes = $acceptedContentTypes;
      $this->Meta = $Meta;
      $this->IP = $IP;
      $this->TLS = !!$TLS;
    }
    // }}}
    
    // {{{ getController
    /**
     * Retrive the controller for this request
     * 
     * @access public
     * @return qcREST_Interface_Controller
     **/
    public function getController () {
      return $this->Controller;
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
    
    // {{{ getFullURI
    /**
     * Retrive the full URI of the request including prefix of the implementation
     * 
     * @access public
     * @return string
     **/
    public function getFullURI () {
      $baseURI = $this->Controller->getURI ();
      
      if (($this->requestURI [0] == '/') && (substr ($baseURI, -1, 1) == '/'))
        $baseURI = substr ($baseURI, 0, -1);
      
      return $baseURI . $this->requestURI;
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
    
    // {{{ getParameter
    /**
     * Retrive a named parameter of this request
     * 
     * @param string $Key
     * 
     * @access public
     * @return mixed
     **/
    public function getParameter ($Key) {
      if (isset ($this->requestParameters [$Key]))
        return $this->requestParameters [$Key];
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
    
    // {{{ getIP
    /**
     * Retrive the IP-Address this request was issued from
     * 
     * @access public
     * @return string
     **/
    public function getIP () {
      return $this->IP;
    }
    // }}}
    
    // {{{ isTLS
    /**
     * Check if the request was made using TLS-encryption
     * 
     * @access public
     * @return bool
     **/
    public function isTLS () {
      return $this->TLS;
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