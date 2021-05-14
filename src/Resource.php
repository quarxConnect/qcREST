<?php

  /**
   * qcREST - Resource
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
  
  namespace quarxConnect\REST;
  use \quarxConnect\Entity;
  use \quarxConnect\Events;
  
  class Resource implements ABI\Resource {
    private $isReadable = true;
    private $isWritable = true;
    private $isRemovable = true;
    
    private $resourceName = '';
    private $resourceAttributes = [ ];
    private $parentCollection = null;
    private $childCollection = null;
    
    // {{{ __construct
    /**
     * Create a new resource
     * 
     * @param string $resourceName
     * @param array $resourceAttributes (optional)
     * @param bool $isReadable (optional)
     * @param bool $isWritable (optional)
     * @param bool $isRemovable (optional)
     * @param ABI\Collection $childCollection (optional)
     * @param ABI\Collection $parentCollection (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (
      string $resourceName = '',
      array $resourceAttributes = [ ],
      bool $isReadable = true,
      bool $isWritable = true,
      bool $isRemovable = true,
      ABI\Collection $childCollection = null,
      ABI\Collection $parentCollection = null
    ) {
      $this->resourceName = $resourceName;
      $this->resourceAttributes = $resourceAttributes;
      $this->isReadable = $isReadable;
      $this->isWritable = $isWritable;
      $this->isRemovable = $isRemovable;
      $this->parentCollection = $parentCollection;
      
      if ($childCollection)
        $this->setChildCollection ($childCollection);
    }
    // }}}
    
    // {{{ isReadable
    /**
     * Checks if this resource's attributes might be forwarded to the client
     * 
     * @param Entity\Card $forUser (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isReadable (Entity\Card $forUser = null) : ?bool {
      return $this->isReadable;
    }
    // }}}
    
    // {{{ isWritable
    /**
     * Checks if this resource is writable and may be modified by the client
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
     * Checks if this resource may be removed by the client
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
    
    // {{{ getName
    /**
     * Retrive the name of this resource
     * 
     * @access public
     * @return string
     **/
    public function getName () : string {
      return $this->resourceName;
    }
    // }}}
    
    // {{{ setName
    /**
     * Change the name of this resource
     * 
     * @param string $resourceName
     * 
     * @access public
     * @return void
     **/
    public function setName (string $resourceName) : void {
      $this->resourceName = $resourceName;
    }
    // }}}
    
    // {{{ getAttributes
    /**
     * Retrive statically set attributes of this resource
     * 
     * @access public
     * @return array
     **/
    public function getAttributes () : array {
      return $this->resourceAttributes;
    }
    // }}}
    
    // {{{ getCollection
    /**
     * Retrive the parented collection of this resource
     * 
     * @access public
     * @return ABI\Collection
     **/
    public function getCollection () : ?ABI\Collection {
      return $this->parentCollection;
    }
    // }}}
    
    // {{{ setCollection
    /**
     * Set the parented collection
     * 
     * @param ABI\Collection $parentCollection
     * 
     * @access public
     * @return void
     **/
    public function setCollection (ABI\Collection $parentCollection) : void {
      $this->parentCollection = $parentCollection;
    }
    // }}}
    
    // {{{ hasChildCollection
    /**
     * Determine if this resource as a child-collection available
     * 
     * @access public
     * @return bool
     **/
    public function hasChildCollection () : bool {
      return ($this->childCollection !== null);
    }
    // }}}
    
    // {{{ getChildCollection
    /**
     * Retrive a child-collection for this node
     * 
     * @access public
     * @return Events\Promise
     **/
    public function getChildCollection () : Events\Promise {
      if ($this->childCollection)
        return Events\Promise::resolve ($this->childCollection);
      
      return Events\Promise::reject ('No child-collection assigned');
    }
    // }}}
    
    // {{{ setChildCollection
    /**
     * Store a child-collection on this resource
     * 
     * @param ABI\Collection $childCollection
     * 
     * @access public
     * @return void
     **/
    public function setChildCollection (ABI\Collection $childCollection) : void {
      $this->childCollection = $childCollection;
      
      if ($childCollection && is_callable ([ $childCollection, 'setResource' ]))
        $childCollection->setResource ($this);
    }
    // }}}
    
    // {{{ unsetChildCollection
    /**
     * Remove any child-collection from this node
     * 
     * @access public
     * @return void
     **/
    public function unsetChildCollection () : void {
      $this->childCollection = null;
    }
    // }}}
    
    // {{{ getRepresentation
    /**
     * Retrive a representation of this resource
     * 
     * @param ABI\Request $forRequest (optional) A Request-Object associated with this call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function getRepresentation (ABI\Request $forRequest = null) : Events\Promise {
      return Events\Promise::resolve (new Representation ($this->resourceAttributes));
    }
    // }}}
    
    // {{{ setRepresentation
    /**
     * Update this resource with a given representation
     * 
     * @param ABI\Representation $newRepresentation Representation to update this resource with
     * @param ABI\Request $fromRequest (optional) A Request-Object associated with this call
     * 
     * @access public
     * @return Events\Promise 
     **/
    public function setRepresentation (ABI\Representation $newRepresentation, ABI\Request $fromRequest = null) : Events\Promise {
      // Store the attribues
      $this->resourceAttributes = $newRepresentation->toArray ();
      
      // Push back the representation
      return Events\Promise::resolve ($newRepresentation);
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
    public function remove (ABI\Request $fromRequest) : Events\Promise {
      return Events\Promise::reject ('Unimplemented');
    }
    // }}}
  }
