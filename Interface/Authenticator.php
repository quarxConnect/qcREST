<?PHP

  interface qcREST_Interface_Authenticator {
    // {{{ authenticateRequest
    /**
     * Try to authenticate a given request
     * 
     * @param qcREST_Interface_Request $Request
     * @param qcREST_Interface_Resource $Resource (optional)
     * @param qcREST_Interface_Collection $Collection (optional)
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * The callback will be raised in the form of
     *   
     *   function (qcREST_Interface_Authenticator $Self, qcREST_Interface_Request $Request, qcREST_Interface_Resource $Resource = null, qcREST_Interface_Collect
     * 
     * $Status indicated wheter the request should be processed or not - if unsure this should be NULL,
     * $User may contain an user-entity that was identified for the request
     *    
     * @access private
     * @return void
     **/
    public function authenticateRequest (qcREST_Interface_Request $Request, qcREST_Interface_Resource $Resource = null, qcREST_Interface_Collection $Collection = null, callable $Callback, $Private = null);
    // }}}
  }

?>