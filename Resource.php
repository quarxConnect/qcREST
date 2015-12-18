<?PHP

  require_once ('qcREST/Interface/Resource.php');
  require_once ('qcREST/Representation.php');
  
  class qcRest_Resource implements qcREST_Interface_Resource {
    private $Readable = true;
    private $Writable = true;
    private $Removable = true;
    
    private $Name = '';
    private $Attributes = array ();
    private $Collection = null;
    
    // {{{ __construct
    /**
     * Create a new resource
     * 
     * @param string $Name
     * @param array $Attributes (optional)
     * @param bool $Readable (optional)
     * @param bool $Writable (optional)
     * @param bool $Removable (optional)
     * @param qcREST_Interface_Collection $Collection (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($Name = '', array $Attributes = array (), $Readable = true, $Writable = true, $Removable = true, qcREST_Interface_Collection $Collection = null) {
      $this->Name = $Name;
      $this->Attributes = $Attributes;
      $this->Readable = $Readable;
      $this->Writable = $Writable;
      $this->Removable = $Removable;
      $this->Collection = $Collection;
    }
    // }}}
    
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
    
    // {{{ setName
    /**
     * Change the name of this resource
     * 
     * @param string $Name
     * 
     * @access public
     * @return void
     **/
    public function setName ($Name) {
      $this->Name = $Name;
    }
    // }}}
    
    // {{{ hasChildCollection
    /**
     * Determine if this resource as a child-collection available
     * 
     * @access public
     * @return bool
     **/
    public function hasChildCollection () {
      return ($this->Collection !== null);
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
      call_user_func ($Callback, $this, $this->Collection, $Private);
      
      return ($this->Collection !== null);
    }
    // }}}
    
    // {{{ setChildCollection
    /**
     * Store a child-collection on this resource
     * 
     * @param qcREST_Interface_Collection $Collection
     * 
     * @access public
     * @return void
     **/
    public function setChildCollection (qcREST_Interface_Collection $Collection) {
      $this->Collection = $Collection;
    }
    // }}}
    
    // {{{ unsetChildCollection
    /**
     * Remove any child-collection from this node
     * 
     * @access public
     * @return void
     **/
    public function unsetChildCollection () {
      $this->Collection = null;
    }
    // }}}
    
    // {{{ getRepresentation
    /**
     * Retrive a representation of this resource
     * 
     * @param callable $Callback A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback   
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Resource $Self, qcREST_Interface_Representation $Representation = null, mixed $Private) { }
     * 
     * @access public
     * @return bool  
     **/
    public function getRepresentation (callable $Callback, $Private = null) {
      call_user_func ($Callback, $this, new qcREST_Representation ($this->Attributes), $Private);
      
      return true;
    }
    // }}}
    
    // {{{ setRepresentation
    /**
     * Update this resource with a given representation
     * 
     * @param qcREST_Interface_Representation $Representation Representation to update this resource with
     * @param callable $Callback (optional) A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Resource $Self, qcREST_Interface_Representation $Representation, bool $Status, mixed $Private) { }
     * 
     * @access public
     * @return bool  
     **/
    public function setRepresentation (qcREST_Interface_Representation $Representation, callable $Callback = null, $Private = null) {
      $this->Attributes = (array)$Representation->toArray ();
      
      if ($Callback)
        call_user_func ($Callback, $this, $Representation, true, $Private);
      
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