<?PHP

  require_once ('qcREST/Interface/Request.php');
  
  class qcREST_Request implements qcREST_Interface_Request {
    private $requestURI = '';
    private $requestMethod = 0;
    private $requestContent = '';
    private $requestContentType = '';
    private $acceptedContentTypes = array ();
    
    function __construct ($URI, $Method, $Content, $ContentType, $acceptedContentTypes) {
      $this->requestURI = $URI;
      $this->requestMethod = $Method;
      $this->requestContent = $Content;
      $this->requestContentType = $ContentType;
      $this->acceptedContentTypes = $acceptedContentTypes;
    }
    
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