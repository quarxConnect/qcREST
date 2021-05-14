<?php

  /**
   * qcREST - Resource Collection
   * Copyright (C) 2016-2021 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  declare (strict_types=1);
  
  namespace quarxConnect\REST\Resource;
  use \quarxConnect\REST\ABI;
  use \quarxConnect\REST;
  use \quarxConnect\Entity;
  use \quarxConnect\Events;
  
  class Collection implements ABI\Collection {
    private $parentResource = null;
    private $childResources = [ ];
    private $isBrowsable = true;
    private $isWritable = true;
    private $isRemovable = false;
    private $fullRepresentation = false;
    private $childCallbacks = array ();
    
    // {{{ __construct
    /**
     * Create a new resource-collection
     * 
     * @param array $childResources (optional) Children on this collection
     * @param bool $isBrowsable (optional)
     * @param bool $isWritable (optional)
     * @param bool $isRemovable (optional)
     * @param bool $fullRepresentation (optional)
     * @param ABI\Resource $parentResource (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (
      array $childResources = null,
      bool $isBrowsable = true,
      bool $isWritable = true,
      bool $isRemovable = true,
      bool $fullRepresentation = false,
      ABI\Resource $parentResource = null
    ) {
      if ($childResources)
        foreach ($childResources as $childResource)
          $this->addChild ($childResource);
      
      $this->parentResource = $parentResource;
      $this->isBrowsable = $isBrowsable;
      $this->isWritable = $isWritable;
      $this->isRemovable = $isRemovable;
      $this->fullRepresentation = $fullRepresentation;
    }
    // }}}
    
    // {{{ isWritable
    /**
     * Checks if this collection is writable and may be modified by the client
     * 
     * @param Entity\Card $forUser (optional)
     * 
     * @access public 
     * @return bool
     **/
    public function isWritable (Entity\Card $forUser = null) : ?bool {
      return $this->isWritable;
    }
    // }}}
    
    // {{{ isRemovable
    /**
     * Checks if this collection may be removed by the client
     * 
     * @param Entity\Card $forUser (optional)
     * 
     * @access public
     * @return bool   
     **/
    public function isRemovable (Entity\Card $forUser = null) : ?bool {
      return $this->isRemovable;
    }
    // }}}
    
    // {{{ isBrowsable
    /**
     * Checks if children of this directory may be discovered
     * 
     * @param Entity\Card $forUser (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isBrowsable (Entity\Card $forUser = null) : ?bool {
      return $this->isBrowsable;
    }
    // }}}
    
    // {{{ getResource
    /**
     * Retrive the resource of this collection
     * 
     * @access public
     * @return ABI\Resource
     **/
    public function getResource () : ?ABI\Resource {
      return $this->parentResource;
    }
    // }}}
    
    // {{{ setResource
    /**
     * Store the resource of this collection
     * 
     * @param ABI\Resource $parentResource
     * 
     * @access public
     * @return void
     **/
    public function setResource (ABI\Resource $parentResource) : void {
      $this->parentResource = $parentResource;
    }
    // }}}
    
    // {{{ addChild
    /**
     * Append a child to our collection
     * 
     * @param ABI\Resource $childResource
     * @param string $childOffset (optional)
     * 
     * @access public
     * @return void
     **/
    public function addChild (ABI\Resource $childResource, string $childOffset = null) : void {
      if (($childResource instanceof REST\Resource) || method_exists ($childResource, 'setCollection'))
        $childResource->setCollection ($this);
      
      if ($childOffset !== null)
        $this->childResources [$childOffset] = $childResource;
      else
        $this->childResources [] = $childResource;
    }
    // }}}
    
    // {{{ addChildCallback
    /**
     * Add a callback to be raised once children are requested from this collection
     * 
     * @param callable $childCallback
     * 
     * The callback will be raised in the form of
     * 
     *   function (ABI\Collection $Self, string $childName = null, array $childResources, ABI\Request $forRequest = null) { }
     * 
     * The variable contains the name of a children if a single one is requested, $childResources will carry the actual child-set.
     * The callback is expected to return a Events\Promise
     * 
     * @access public
     * @return void
     **/
    public function addChildCallback (callable $childCallback) {
      $this->childCallbacks [] = $childCallback;
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
    public function getNameAttribute () : string {
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
    public function getChildFullRepresenation () : bool {
      return $this->fullRepresentation;
    }
    // }}}
    
    // {{{ setChildFullRepresenation
    /**
     * Decide wheter to output the full representation of children instead of a simple summary
     * 
     * @param bool $setPolicy (optional)
     * 
     * @access public
     * @return void
     **/
    public function setChildFullRepresenation (bool $setPolicy = true) : void {
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
    protected function getChildrenS () : array {
      return $this->childResources;
    }
    // }}}
    
    // {{{ getChildren
    /**
     * Retrive all children on this directory
     * 
     * @param ABI\Request $forRequest (optional) The Request that triggered this function-call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function getChildren (ABI\Request $forRequest = null) : Events\Promise {
      $childPromises = [ ];
      
      foreach ($this->childCallbacks as $childCallback)
        $childPromises [] = $childCallback ($this, null, $this->childResources, $forRequest);
      
      return Events\Promise::all ($childPromises)->then (
        function (array $promiseResults) {
          $volatileChildren = call_user_func_array ('array_merge', $promiseResults);
          
          foreach ($volatileChildren as $childIndex=>$volatileChild)
            if (!($volatileChild instanceof ABI\Resource))
              unset ($volatileChildren [$childIndex]);
          
          return array_merge (
            $this->childResources,
            $volatileChildren
          );
        }
      );
    }
    // }}}
    
    // {{{ getChild
    /**
     * Retrive a single child by its name from this directory
     * 
     * @param string $childName Name of the child to return
     * @param ABI\Request $forRequest (optional) The Request that triggered this function-call   
     * 
     * @access public
     * @return Events\Promise
     **/
    public function getChild (string $childName, ABI\Request $forRequest = null) : Events\Promise {
      // Check if the child is persistent here
      foreach ($this->childResources as $childResource)
        if ($childResource->getName () == $childName)
          return Events\Promise::resolve ($childResource);
      
      // Ask our callbacks
      $childPromises = [ ];
      
      foreach ($this->childCallbacks as $childCallback)
        $childPromises [] = $childCallback ($this, $childName, $this->childResources, $forRequest);
      
      return Events\Promise::all ($childPromises)->then (
        function (array $promiseResults) use ($childName) {
          // Check volatile children
          foreach ($promiseResults as $promiseResult)
            foreach ($promiseResult as $childResource)
              if (($childResource instanceof ABI\Resource) && ($childResource->getName () == $childName))
                return $childResource;
          
          // Check our resources again
          foreach ($this->childResources as $childResource)
            if ($childResource->getName () == $childName)
              return $childResource;
        }
      );
    }
    // }}}
    
    // {{{ createChild
    /**
     * Create a new child on this directory
     * 
     * @param ABI\Representation $childRepresentation Representation to create the child from
     * @param string $childName (optional) Explicit name for the child, if none given the directory should generate a new one
     * @param ABI\Request $fromRequest (optional) The Request that triggered this function-call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function createChild (ABI\Representation $childRepresentation, string $childName = null, ABI\Request $fromRequest = null) : Events\Promise {
      return Events\Promise::reject ('Unimplemented');
    }
    // }}}
    
    // {{{ remove  
    /**   
     * Remove this resource from the server
     * 
     * @param ABI\Request $fromRequest (optional) The Request that triggered this function-call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function remove (ABI\Request $fromRequest = null) : Events\Promise {
      return Events\Promise::reject ('Unimplemented');
    }
    // }}}
  }
