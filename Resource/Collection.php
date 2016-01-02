<?PHP

  require_once ('qcREST/Interface/Collection.php');
  
  class qcREST_Resource_Collection implements qcREST_Interface_Collection {
    private $Children = array ();
    private $Browsable = true;
    private $Writable = true;
    private $Removable = false;
    private $Callbacks = array ();
    
    // {{{ __construct
    /**
     * Create a new resource-collection
     * 
     * @param array $Children (optional) Children on this collection
     * @param bool $Browsable (optional)
     * @param bool $Writable (optional)
     * @param bool $Removable (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (array $Children = null, $Browsable = true, $Writable = true, $Removable = true) {
      if ($Children)
        $this->Children = $Children;
      
      $this->Browsable = $Browsable;
      $this->Writable = $Writable;
      $this->Removable = $Removable;
    }
    // }}}
    
    // {{{ isWritable
    /**
     * Checks if this collection is writable and may be modified by the client
     * 
     * @param qcVCard_Entity $User (optional)
     * 
     * @access public 
     * @return bool
     **/
    public function isWritable (qcVCard_Entity $User = null) {
      return $this->Writable;
    }
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
    public function isRemovable (qcVCard_Entity $User = null) {
      return $this->Removable;
    }
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
    public function isBrowsable (qcVCard_Entity $User = null) {
      return $this->Browsable;
    }
    // }}}
    
    // {{{ addChild
    /**
     * Append a child to our collection
     * 
     * @param qcREST_Interface_Resource $Child
     * @param mixed $Offset (optional)
     * 
     * @access public
     * @return void
     **/
    public function addChild (qcREST_Interface_Resource $Child, $Offset = null) {
      if ($Offset !== null)
        $this->Children [$Offset] = $Child;
      else
        $this->Children [] = $Child;
    }
    // }}}
    
    // {{{ addChildCallback
    /**
     * Add a callback to be raised once children are requested from this collection
     * 
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * The callback will be raised in the form of
     * 
     *   function (qcREST_REsource_Collection $Self, string $Name = null, array $Children, callable $Callback, mixed $Private) { }
     * 
     * The variable contains the name of a children if a single one is requested, $Children will carry the actual child-set
     * 
     * @access public
     * @return void
     **/
    public function addChildCallback (callable $Callback, $Private = null) {
      $this->Callbacks [] = array ($Callback, $Private);
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
      // Setup handler
      $Counter = 1;
      $Handler = function () use (&$Counter, $Callback, $Private) {
        // Check if we are ready
        if (--$Counter > 0)
          return;
        
        call_user_func ($Callback, $this, $this->Children, $Private);
      };
      
      // Process callbacks if registered
      if (count ($this->Callbacks) > 0) {
        // Raise all callbacks
        foreach ($this->Callbacks as $cCallback) {
          $Counter++;
          call_user_func ($cCallback [0], $this, null, $this->Children, $Handler, $cCallback [1]);
        }
        
        // Stop here if callbacks are pending
        if ($Counter-- > 1)
          return;
      }
      
      // Just call the handler if we get here
      call_user_func ($Handler);
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
      // Setup handler
      $Counter = 1;
      $Handler = function () use (&$Counter, $Name, $Callback, $Private) {
        // Check if we are ready
        if (--$Counter > 0)
          return;
        
        // Look for a matching child
        foreach ($this->Children as $Child)
          if ($Child->getName () == $Name)
            return call_user_func ($Callback, $this, $Name, $Child, $Private);
        
        // Just return nothing
        return call_user_func ($Callback, $this, $Name, null, $Private);
      };
      
      if (count ($this->Callbacks) > 0) {
        // Raise all callbacks
        foreach ($this->Callbacks as $cCallback) {
          $Counter++;
          call_user_func ($cCallback [0], $this, $Name, $this->Children, $Handler, $cCallback [1]);
        }
        
        // Stop here if callbacks are pending
        if ($Counter-- > 1)
          return;
      }
      
      // Just call the handler if we get here
      call_user_func ($Handler);
      return true;
    }
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
    public function createChild (qcREST_Interface_Representation $Representation, $Name = null, callable $Callback = null, $Private = null, qcREST_Interface_Request $Request = null) {
      if ($Callback)
        call_user_func ($Callback, $this, $Name, null, null, $Private);
      
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