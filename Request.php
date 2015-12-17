<?PHP

  require_once ('qcREST/Interface/Request.php');
  
  class qcREST_Request implements qcREST_Interface_Request {
    private $requestURI = '';
    private $requestMethod = 0;
    private $requestParameters = array ();
    private $requestContent = '';
    private $requestContentType = '';
    private $acceptedContentTypes = array ();
    
    // {{{ __construct
    /**
     * Create a new Request
     * 
     * @param string $URI The requested URI
     * @param string $Method The used request-method
     * @param array $Parameters Additional Parameters for this request
     * @param string $Content The payload from the request
     * @param string $ContentType Type of payload
     * @param array $acceptedContentTypes List of accepted content-types
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($URI, $Method, array $Parameters, $Content, $ContentType, array $acceptedContentTypes) {
      $this->requestURI = $URI;
      $this->requestMethod = $Method;
      $this->requestParameters = $Parameters;
      $this->requestContent = $Content;
      $this->requestContentType = $ContentType;
      $this->acceptedContentTypes = $acceptedContentTypes;
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
    
    // {{{ getUser
    /**
     * Retrive the user that was authenticated with this request
     * Authentication means any kind of identification but NOT authorized. Resources have to check on their own
     * if a user is authorized to access the resource!
     * 
     * @access public 
     * @return qcVCard
     **/
    public function getUser () { }
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