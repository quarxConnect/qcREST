<?PHP

  require_once ('qcREST/Interface/Processor.php');
  
  class qcREST_Processor_JSON implements qcREST_Interface_Processor {
    private static $Types = array (
      'application/json',
      'text/json',
    );
    
    public function getSupportedContentTypes () {
      return self::$Types;  
    }
    
    // {{{ processInput
    /**
     * Process input-data
     * 
     * @param string $Data
     * 
     * @access public
     * @return array
     **/
    public function processInput ($Data) {
      if (!is_object ($Data = json_decode ($Data)))
        return false;
      
      return (array)$Data;
    }
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
     *   function (qcREST_Interface_Processor $Self, string $Output,  qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $Representation, qcREST_Interface_Request $Request = null, qcREST_
     * 
     * @access public
     * @return bool
     **/
    public function processOutput (callable $Callback, $Private = null, qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $Representation, qcREST_Interface_Request $Request = null, qcREST_Interface_Controller $Controller = null) {
      call_user_func ($Callback, $this, json_encode ((object)$Representation->toArray ()), 'application/json', $Resource, $Representation, $Request, $Controller, $Private);
      
      return true;
    }
    // }}}
  }

?>