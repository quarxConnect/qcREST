<?PHP

  interface qcREST_Interface_Collection_Representation {
    // {{{ getCollectionRepresentation
    /**
     * Retrive an additional representation from this resource to be included in collection-output
     * 
     * @param $Callback A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Resource $Self, qcREST_Interface_Representation $Representation = null, mixed $Private) { }
     * 
     * @access public
     * @return void
     **/
    public function getCollectionRepresentation (callable $Callback, $Private = null);
    // }}}
  }

?>