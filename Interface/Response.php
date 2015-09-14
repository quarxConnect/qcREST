<?PHP

  interface qcREST_Interface_Response {
    const STATUS_OK = 200;
    const STATUS_CREATED = 201;
    
    const STATUS_NOT_FOUND = 404;
    const STATUS_NOT_ALLOWED = 405;
    
    // Input-Errors
    const STATUS_CLIENT_ERROR = 400;
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
    
    // {{{ getMeta
    /**
     * Retrive the meta-data of this response
     * 
     * @access public
     * @return array
     **/
    public function getMeta ();
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