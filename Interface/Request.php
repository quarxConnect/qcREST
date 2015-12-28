<?PHP

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
    
    // {{{ getUser
    /**
     * Retrive the user that was authenticated with this request
     * Authentication means any kind of identification but NOT authorized. Resources have to check on their own
     * if a user is authorized to access the resource!
     * 
     * @access public
     * @return qcVCard_Entity
     **/
    public function getUser ();
    // }}}
    
    // {{{ setUser
    /**
     * Store a user-entity on this request that is belived to be authenticated from the request
     * 
     * @param qcVCard_Entity $User
     * 
     * @access public
     * @return void
     **/
    public function setUser (qcVCard_Entity $User);
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