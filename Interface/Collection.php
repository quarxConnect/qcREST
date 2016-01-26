<?PHP

  require_once ('qcREST/Interface/Resource.php');
  
  interface qcREST_Interface_Collection {
    // {{{ isWritable
    /**
     * Checks if this collection is writable and may be modified by the client
     * 
     * @param qcVCard_Entity $User (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isWritable (qcVCard_Entity $User = null);
    // }}}
    
    // {{{ isRemovable
    /**
     * Checks if this collection may be removed by the client
     * 
     * @param qcVCard_Entity $User (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isRemovable (qcVCard_Entity $User = null);
    // }}}
    
    // {{{ isBrowsable
    /**
     * Checks if children of this directory may be discovered
     * 
     * @param qcVCard_Entity $User (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isBrowsable (qcVCard_Entity $User = null);
    // }}}
    
    
    // {{{ getNameAttribute
    /**
     * Retrive the name of the name-attribute
     * The name-attribute is used on listings to output the name of each child
     * 
     * @access public
     * @return string
     **/
    public function getNameAttribute ();
    // }}}
    
    // {{{ getChildFullRepresenation
    /**
     * Check wheter full representation of children should be shown on listings
     * 
     * @remark This function is optional and need not to be implemented, the controller will check on his own wheter this is usable
     * 
     * @access public
     * @return bool
     **/
    # public function getChildFullRepresenation ();
    // }}}
    
    // {{{ getChildren
    /**
     * Retrive all children on this directory
     * 
     * @param callable $Callback A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback
     * @param qcREST_Interface_Request $Request (optional) The Request that triggered this function-call
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Collection $Self, array $Children = null, mixed $Private) { }
     * 
     * @access public
     * @return bool
     **/
    public function getChildren (callable $Callback, $Private = null, qcREST_Interface_Request $Request = null);
    // }}}
    
    // {{{ getChild
    /**
     * Retrive a single child by its name from this directory
     * 
     * @param string $Name Name of the child to return
     * @param callable $Callback A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback
     * @param qcREST_Interface_Request $Request (optional) The Request that triggered this function-call
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Collection $Self, string $Name, qcREST_Interface_Resource $Child = null, mixed $Private) { }
     * 
     * @access public
     * @return bool
     **/
    public function getChild ($Name, callable $Callback, $Private = null, qcREST_Interface_Request $Request = null);
    // }}}
    
    // {{{ createChild
    /**
     * Create a new child on this directory
     * 
     * @param qcREST_Interface_Representation $Representation Representation to create the child from
     * @param string $Name (optional) Explicit name for the child, if none given the directory should generate a new one
     * @param callable $Callback (optional) A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback
     * @param qcREST_Interface_Request $Request (optional) The Request that triggered this function-call
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Collection $Self, string $Name = null, qcREST_Interface_Resource $Child = null, qcREST_Interface_Representation $Representation = null, mixed $Private) { }
     * 
     * @access public
     * @return bool
     **/
    public function createChild (qcREST_Interface_Representation $Representation, $Name = null, callable $Callback = null, $Private = null, qcREST_Interface_Request $Request = null);
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
     *   function (qcREST_Interface_Collection $Self, bool $Status, mixed $Private) { }
     * 
     * @access public
     * @return bool
     **/
    public function remove (callable $Callback = null, $Private = null);
    // }}}
  }

?>