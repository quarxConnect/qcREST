<?PHP

  require_once ('qcREST/Interface/Resource.php');
  
  class qcRest_Resource implements qcREST_Interface_Resource {
    private $Readable = true;
    private $Writable = true;
    private $Removable = true;
    
    private $Name = '';
    private $Attributes = array ('Hallo' => 'World');
    
    // {{{ isReadable
    /**
     * Checks if this resource's attributes might be forwarded to the client
     * 
     * @access public
     * @return bool
     **/
    public function isReadable () {
      return $this->Readable;
    }
    // }}}
    
    // {{{ isWritable
    /**
     * Checks if this resource is writable and may be modified by the client
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
     * Checks if this resource may be removed by the client
     * 
     * @access public
     * @return bool
     **/
    public function isRemovable () {
      return $this->Removable;
    }
    // }}}
    
    // {{{ getName
    /**
     * Retrive the name of this resource
     * 
     * @access public
     * @return string
     **/
    public function getName () {
      return $this->Name;
    }
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
    public function getChildCollection (callable $Callback, $Private = null) {
      call_user_func ($Callback, $this, null, $Private);
      
      return false;
    }
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
    public function getAttributes (callable $Callback, $Private = null) {
      call_user_func ($Callback, $this, $this->Attributes, $Private);
      
      return true;
    }
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
    public function setAttributes (array $Attributes, callable $Callback = null, $Private = null) {
      $this->Attributes = $Attributes;
      
      if ($Callback)
        call_user_func ($Callback, $this, $Attributes, true, $Private);
      
      return true;
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
     *   function (qcREST_Interface_Resource $Self, bool $Status, mixed $Private) { }
     * 
     * @access public
     * @return bool  
     **/
    public function remove (callable $Callback = null, $Private = null) {
      if ($Callback)
        call_user_func ($Callback, $this, false, $Private);
      
      return true;
    }
    // }}}
  }

?>