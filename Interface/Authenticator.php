<?PHP

  interface qcREST_Interface_Authenticator {
    // {{{ authenticateRequest
    /**
     * Try to authenticate a given request
     * 
     * @param qcREST_Interface_Request $Request
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * The callback will be raised in the form of
     *   
     *   function (qcREST_Interface_Authenticator $Self, qcREST_Interface_Request $Request, bool $Status, qcVCard_Entity $Entity = null, mixed $private = null) { }
     * 
     * $Status indicated wheter the request should be processed or not - if unsure this should be NULL,
     * $User may contain an user-entity that was identified for the request
     *    
     * @access private
     * @return void
     **/
    public function authenticateRequest (qcREST_Interface_Request $Request, callable $Callback, $Private = null);
    // }}}
    
    // {{{ getSchemes
    /**
     * Retrive a list of supported authentication-schemes.
     * The list is represented by an array of associative arrays, each with the following keys:
     * 
     *   scheme: A well known name of the scheme
     *   realm:  A realm for the scheme
     * 
     * @access public
     * @return array
     **/
    public function getSchemes ();
    // }}}
  }

?>