<?PHP

  require_once ('qcREST/Interface/Response.php');
  
  class qcREST_Response implements qcREST_Interface_Response {
    private $Request = null;
    private $Status = qcREST_Interface_Response::STATUS_OK;
    private $Meta = array ();
    private $ContentType = 'application/octet-stream';
    private $Content = '';
    
    function __construct (qcREST_Interface_Request $Request, $Status, $Content = '', $ContentType = 'application/octet-stream', array $Meta = array ()) {
      $this->Request = $Request;
      $this->Status = (int)$Status;
      $this->Meta = $Meta;
      $this->Content = $Content;
      $this->ContentType = $ContentType;
    }
    
    // {{{ getRequest
    /**
     * Retrive the request this response is generated for
     * 
     * @access public
     * @return qcREST_Interfaces_Request
     **/
    public function getRequest () {
      return $this->Request;
    }
    // }}}
    
    // {{{ getStatus
    /**
     * Retrive the status of the response
     * 
     * @access public
     * @return enum
     **/
    public function getStatus () {
      return $this->Status;
    }
    // }}}

    // {{{ getMeta
    /**
     * Retrive the meta-data of this response
     * 
     * @access public
     * @return array
     **/
    public function getMeta () {
      return $this->Meta;
    }
    // }}}

    // {{{ getContentType
    /**
     * Retrive the content-type of the response
     * 
     * @access public
     * @return string
     **/
    public function getContentType () {
      return $this->ContentType;
    }
    // }}}

    // {{{ getContent
    /**
     * Retrive the whole response-data
     * 
     * @access public
     * @return string
     **/
    public function getContent () {
      return $this->Content;
    }
    // }}}
  }

?>