<?PHP

  /**
   * qcREST - Resource
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
  
  require_once ('qcREST/Interface/Resource.php');
  require_once ('qcREST/Representation.php');
  require_once ('qcEvents/Promise.php');
  
  class qcRest_Resource implements qcREST_Interface_Resource {
    private $Readable = true;
    private $Writable = true;
    private $Removable = true;
    
    private $Name = '';
    private $Attributes = array ();
    private $Collection = null;
    private $ChildCollection = null;
    
    // {{{ __construct
    /**
     * Create a new resource
     * 
     * @param string $Name
     * @param array $Attributes (optional)
     * @param bool $Readable (optional)
     * @param bool $Writable (optional)
     * @param bool $Removable (optional)
     * @param qcREST_Interface_Collection $ChildCollection (optional)
     * @param qcREST_Interface_Collection $Collection (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($Name = '', array $Attributes = array (), $Readable = true, $Writable = true, $Removable = true, qcREST_Interface_Collection $ChildCollection = null, qcREST_Interface_Collection $Collection = null) {
      $this->Name = $Name;
      $this->Attributes = $Attributes;
      $this->Readable = $Readable;
      $this->Writable = $Writable;
      $this->Removable = $Removable;
      $this->Collection = $Collection;
      
      if ($ChildCollection)
        $this->setChildCollection ($ChildCollection);
    }
    // }}}
    
    // {{{ isReadable
    /**
     * Checks if this resource's attributes might be forwarded to the client
     * 
     * @param qcEntity_Card $User (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isReadable (qcEntity_Card $User = null) {
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
    public function isWritable (qcEntity_Card $User = null) {
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
    public function isRemovable (qcEntity_Card $User = null) {
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
    
    // {{{ getAttributes
    /**
     * Retrive statically set attributes of this resource
     * 
     * @access public
     * @return array
     **/
    public function getAttributes () {
      return $this->Attributes;
    }
    // }}}
    
    // {{{ getCollection
    /**
     * Retrive the parented collection of this resource
     * 
     * @access public
     * @return qcREST_Interface_Collection
     **/
    public function getCollection () {
      return $this->Collection;
    }
    // }}}
    
    // {{{ setCollection
    /**
     * Set the parented collection
     * 
     * @param qcREST_Interface_Collection $Collection
     * 
     * @access public
     * @return void
     **/
    public function setCollection (qcREST_Interface_Collection $Collection) {
      $this->Collection = $Collection;
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
      return ($this->ChildCollection !== null);
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
     * @return void
     **/
    public function getChildCollection (callable $Callback, $Private = null) {
      call_user_func ($Callback, $this, $this->ChildCollection, $Private);
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
      $this->ChildCollection = $Collection;
      
      if ($Collection && is_callable (array ($Collection, 'setResource')))
        $Collection->setResource ($this);
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
      $this->ChildCollection = null;
    }
    // }}}
    
    // {{{ getRepresentation
    /**
     * Retrive a representation of this resource
     * 
     * @param qcREST_Interface_Request $Request (optional) A Request-Object associated with this call
     * 
     * @access public
     * @return void
     **/
    public function getRepresentation (qcREST_Interface_Request $Request = null) : qcEvents_Promise {
      return qcEvents_Promise::resolve (new qcREST_Representation ($this->Attributes));
    }
    // }}}
    
    // {{{ setRepresentation
    /**
     * Update this resource with a given representation
     * 
     * @param qcREST_Interface_Representation $Representation Representation to update this resource with
     * @param callable $Callback (optional) A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback
     * @param qcREST_Interface_Request $Request (optional) A Request-Object associated with this call
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Resource $Self, qcREST_Interface_Representation $Representation, bool $Status, mixed $Private) { }
     * 
     * @access public
     * @return bool  
     **/
    public function setRepresentation (qcREST_Interface_Representation $Representation, callable $Callback = null, $Private = null, qcREST_Interface_Request $Request = null) {
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
     * @access public
     * @return qcEvents_Promise 
     **/
    public function remove () : qcEvents_Promise {
      return qcEvents_Promise::reject ('Unimplemented');
    }
    // }}}
  }

?>