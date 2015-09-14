<?PHP

  require_once ('qcREST/Interface/Collection.php');
  
  class qcREST_Resource_Collection implements qcREST_Interface_Collection {
    private $Writable = true;
    private $Removable = false;
    private $Browsable = true;
    private $Children = array ();
    
    // {{{ isWritable
    /**
     * Checks if this collection is writable and may be modified by the client
     * 
     * @access public 
     * @return bool
     **/
    public function isWritable () {
      return $this->Writable;
    }
    // }}}
    
    // {{{ isRemovable
    /**
     * Checks if this collection may be removed by the client
     * 
     * @access public
     * @return bool   
     **/
    public function isRemovable () {
      return $this->Removable;
    }
    // }}}
    
    // {{{ isBrowsable
    /**
     * Checks if children of this directory may be discovered
     * 
     * @access public
     * @return bool
     **/
    public function isBrowsable () {
      return $this->Browsable;
    }
    // }}}
    
    // {{{ getChildren
    /**
     * Retrive all children on this directory
     * 
     * @param callable $Callback A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback   
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Collection $Self, array $Children = null, mixed $Private) { }
     * 
     * @access public
     * @return bool  
     **/
    public function getChildren (callable $Callback, $Private = null) {
      call_user_func ($Callback, $this, $this->Children, $Private);
      
      return true;
    }
    // }}}
    
    // {{{ getChild
    /**
     * Retrive a single child by its name from this directory
     * 
     * @param string $Name Name of the child to return
     * @param callable $Callback A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback   
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Collection $Self, string $Name, qcREST_Interface_Resource $Child = null, mixed $Private) { }
     * 
     * @access public
     * @return bool  
     **/
    public function getChild ($Name, callable $Callback, $Private = null) {
      foreach ($this->Children as $Child)
        if ($Child->getName () == $Name) {
          call_user_func ($Callback, $this, $Name, $Child, $Private);
          
          return true;
        }
      
      call_user_func ($Callback, $this, $Name, null, $Private);
      
      return true;
    }
    // }}}
    
    // {{{ createChild
    /**
     * Create a new child on this directory
     * 
     * @param array $Attributes Attributes to create the child with
     * @param string $Name (optional) Explicit name for the child, if none given the directory should generate a new one
     * @param callable $Callback (optional) A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback   
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Collection $Self, string $Name = null, qcREST_Interface_Resource $Child = null, mixed $Private) { }
     * 
     * @access public
     * @return bool  
     **/
    public function createChild (array $Attributes, $Name = null, callable $Callback = null, $Private = null) {
      if ($Callback)
        call_user_func ($Callback, $this, $Name, null, $Private);
      
      return false;
    }
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
    public function remove (callable $Callback = null, $Private = null) {
      if ($Callback)
        call_user_func ($Callback, $this, false, $Private);
      
      return false;
    }
    // }}}
  }

?>