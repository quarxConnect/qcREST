<?PHP

  /**
   * qcREST - Resource Collection
   * Copyright (C) 2016 Bernd Holzmueller <bernd@quarxconnect.de>
   * 
   * This program is free software: you can redistribute it and/or modify
   * it under the terms of the GNU General Public License as published by
   * the Free Software Foundation, either version 3 of the License, or
   * (at your option) any later version.
   * 
   * This program is distributed in the hope that it will be useful,
   * but WITHOUT ANY WARRANTY; without even the implied warranty of
   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   * GNU General Public License for more details.
   * 
   * You should have received a copy of the GNU General Public License
   * along with this program.  If not, see <http://www.gnu.org/licenses/>.
   **/
  
  require_once ('qcREST/Interface/Collection.php');
  require_once ('qcEvents/Queue.php');
  
  class qcREST_Resource_Collection implements qcREST_Interface_Collection {
    private $Resource = null;
    private $Children = array ();
    private $Browsable = true;
    private $Writable = true;
    private $Removable = false;
    private $fullRepresentation = false;
    private $Callbacks = array ();
    
    // {{{ __construct
    /**
     * Create a new resource-collection
     * 
     * @param array $Children (optional) Children on this collection
     * @param bool $Browsable (optional)
     * @param bool $Writable (optional)
     * @param bool $Removable (optional)
     * @param bool $fullRepresentation (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (array $Children = null, $Browsable = true, $Writable = true, $Removable = true, $fullRepresentation = false, qcREST_Interface_Resource $Resource = null) {
      if ($Children)
        foreach ($Children as $Child)
          $this->addChild ($Child);
      
      $this->Resource = $Resource;
      $this->Browsable = $Browsable;
      $this->Writable = $Writable;
      $this->Removable = $Removable;
      $this->fullRepresentation = $fullRepresentation;
    }
    // }}}
    
    // {{{ isWritable
    /**
     * Checks if this collection is writable and may be modified by the client
     * 
     * @param qcEntity_Card $User (optional)
     * 
     * @access public 
     * @return bool
     **/
    public function isWritable (qcEntity_Card $User = null) {
      return $this->Writable;
    }
    // }}}
    
    // {{{ isRemovable
    /**
     * Checks if this collection may be removed by the client
     * 
     * @param qcEntity_Card $User (optional)
     * 
     * @access public
     * @return bool   
     **/
    public function isRemovable (qcEntity_Card $User = null) {
      return $this->Removable;
    }
    // }}}
    
    // {{{ isBrowsable
    /**
     * Checks if children of this directory may be discovered
     * 
     * @param qcEntity_Card $User (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isBrowsable (qcEntity_Card $User = null) {
      return $this->Browsable;
    }
    // }}}
    
    // {{{ getResource
    /**
     * Retrive the resource of this collection
     * 
     * @access public
     * @return qcREST_Interface_Resource
     **/
    public function getResource () {
      return $this->Resource;
    }
    // }}}
    
    // {{{ setResource
    /**
     * Store the resource of this collection
     * 
     * @param qcREST_Interface_Resource $Resource
     * 
     * @access public
     * @return void
     **/
    public function setResource (qcREST_Interface_Resource $Resource) {
      $this->Resource = $Resource;
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
      if ($Child instanceof qcREST_Resource)
        $Child->setCollection ($this);
      
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
     *   function (qcREST_Resource_Collection $Self, string $Name = null, array $Children, callable $Callback, mixed $Private) { }
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
    
    // {{{ getNameAttribute
    /**
     * Retrive the name of the name-attribute
     * The name-attribute is used on listings to output the name of each child
     * 
     * @access public
     * @return string
     **/
    public function getNameAttribute () {
      return 'name';
    }
    // }}}
    
    // {{{ getChildFullRepresenation
    /**
     * Determine wheter to output the full representation of children instead of a simple summary
     * 
     * @access public
     * @return bool
     **/
    public function getChildFullRepresenation () {
      return $this->fullRepresentation;
    }
    // }}}
    
    // {{{ setChildFullRepresenation
    /**
     * Decide wheter to output the full representation of children instead of a simple summary
     * 
     * @access public
     * @return bool
     **/
    public function setChildFullRepresenation ($Toggle = true) {
      $this->fullRepresentation = !!$Toggle;
    }
    // }}}
    
    // {{{ getChildrenS
    /**
     * Retrive the entire set of currently known children
     * 
     * @access protected
     * @return array
     **/
    protected function getChildrenS () {
      return $this->Children;
    }
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
     * @return void
     **/
    public function getChildren (callable $Callback, $Private = null, qcREST_Interface_Request $Request = null) {
      $Queue = new qcEvents_Queue;
      
      foreach ($this->Callbacks as $cCallback)
        $Queue->addCall ($cCallback [0], $this, null, $this->Children, null, $cCallback [1]);
       
      $Queue->finish (function () use ($Callback, $Private) {
        return call_user_func ($Callback, $this, $this->Children, $Private);
      });
    }
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
     *   function (qcREST_Interface_Collection $Self, qcREST_Interface_Resource $Child = null, mixed $Private) { }
     * 
     * @access public
     * @return void
     **/
    public function getChild ($Name, callable $Callback, $Private = null, qcREST_Interface_Request $Request = null) {
      $Queue = new qcEvents_Queue;
      
      foreach ($this->Callbacks as $cCallback)
        $Queue->addCall ($cCallback [0], $this, $Name, $this->Children, null, $cCallback [1]);
      
      $Queue->finish (function () use ($Name, $Callback, $Private) {
        // Look for a matching child
        foreach ($this->Children as $Child)
          if ($Child->getName () == $Name)
            return call_user_func ($Callback, $this, $Child, $Private);
        
        // Just return nothing
        return call_user_func ($Callback, $this, null, $Private);
      });
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
     *   function (qcREST_Interface_Collection $Self, qcREST_Interface_Resource $Child = null, qcREST_Interface_Representation $Representation = null, mixed $Private) { }
     * 
     * @access public
     * @return void
     **/
    public function createChild (qcREST_Interface_Representation $Representation, $Name = null, callable $Callback = null, $Private = null, qcREST_Interface_Request $Request = null) {
      if ($Callback)
        call_user_func ($Callback, $this, null, null, $Private);
      
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
     * @return void
     **/
    public function remove (callable $Callback = null, $Private = null) {
      if ($Callback)
        call_user_func ($Callback, $this, false, $Private);
      
      return false;
    }
    // }}}
  }

?>