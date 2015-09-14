<?PHP

  interface qcRest_Interface_Resource {
    // {{{ isReadable
    /**
     * Checks if this resource's attributes might be forwarded to the client
     * 
     * @access public
     * @return bool
     **/
    public function isReadable ();
    // }}}
    
    // {{{ isWritable
    /**
     * Checks if this resource is writable and may be modified by the client
     * 
     * @access public
     * @return bool
     **/
    public function isWritable ();
    // }}}
    
    // {{{ isRemovable
    /**
     * Checks if this resource may be removed by the client
     * 
     * @access public
     * @return bool
     **/
    public function isRemovable ();
    // }}}
    
    
    // {{{ getName
    /**
     * Retrive the name of this resource
     * 
     * @access public
     * @return string
     **/
    public function getName ();
    // }}}
    
    // {{{ getChildCollection
    /**
     * Retrive a child-collection for this node
     * 
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * The callback will be raised in the form of
     * 
     *   function (qcREST_Interface_Resource $Self, qcREST_Interface_Collection $Collection = null, mixed $Private = null) { }
     * 
     * @access public
     * @return bool
     **/
    public function getChildCollection (callable $Callback, $Private = null);
    // }}}
    
    // {{{ getAttributes
    /**
     * Retrive all attributes of this resource
     * 
     * @param callable $Callback A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Resource $Self, array $Attributes = null, mixed $Private) { }
     * 
     * @access public
     * @return bool
     **/
    public function getAttributes (callable $Callback, $Private = null);
    // }}}
    
    // {{{ setAttributes
    /**
     * Store a set of attributes
     * 
     * @param array $Attributes Attributes to set on this resource
     * @param callable $Callback (optional) A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Resource $Self, array $Attributes, bool $Status, mixed $Private) { }
     * 
     * @access public
     * @return bool
     **/
    public function setAttributes (array $Attributes, callable $Callback = null, $Private = null);
    // }}}
    
    // {{{ remove
    /**
     * Remove this resource from the server
     * 
     * @param callable $Callback (optional) A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Resource $Self, bool $Status, mixed $Private) { }
     * 
     * @access public
     * @return bool  
     **/
    public function remove (callable $Callback = null, $Private = null);
    // }}}
  }

?>