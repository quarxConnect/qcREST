<?PHP

  interface qcREST_Interface_Processor {
    public function getSupportedContentTypes ();
    
    // {{{ processInput
    /**
     * Process input-data
     * 
     * @param string $Data
     * 
     * @access public
     * @return qcREST_Interface_Representation
     **/
    public function processInput ($Data);
    // }}}
    
    // {{{ processOutput
    /**
     * Process output-data
     * 
     * @param callable $Callback
     * @param mixed $Private (optional)
     * @param qcREST_Interface_Resource $Resource
     * @param qcREST_Interface_Representation $Representation
     * @param qcREST_Interface_Request $Request (optional)
     * @param qcREST_Interface_Controller $Controller (optional)
     * 
     * The callback will be raised in the form of
     * 
     *   function (qcREST_Interface_Processor $Self, string $Output, string $OutputType, qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $Representation, qcREST_Interface_Request $Request = null, qcREST_Interface_Controller $Controller = null, mixed $Private = null) { }
     * 
     * @access public
     * @return bool
     **/
    public function processOutput (callable $Callback, $Private = null, qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $Representation, qcREST_Interface_Request $Request = null, qcREST_Interface_Controller $Controller = null);
    // }}}
  }

?>