<?php

  /**
   * qcREST - Collection Trait
   * Copyright (C) 2019-2021 Bernd Holzmueller <bernd@quarxconnect.de>
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
   * long with this program.  If not, see <http://www.gnu.org/licenses/>.
   **/
  
  declare (strict_types=1);
  
  namespace quarxConnect\REST\Feature;
  use \quarxConnect\REST\ABI;
  use \quarxConnect\Entity;
  use \quarxConnect\Events;
  
  trait Collection {
    /* Parent resource of this collection */
    private $parentResource = null;
    
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
      return false;
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
      return false;
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
      return true;
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
     * Assign the parented resource of this collection
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
    
    // {{{ getNameAttribute
    /**
     * Retrive the name of the name-attribute
     * 
     * The name-attribute is used on listings to output the name of each child
     * 
     * @access public
     * @return string
     **/
    public function getNameAttribute () : string {
      if (!defined (get_class ($this) . '::COLLECTION_NAME_ATTRIBUTE'))
        return 'id';
      
      return $this::COLLECTION_NAME_ATTRIBUTE;
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
      return $this->getChildren ($forRequest)->then (
        function (array $childCollection) use ($childName) {
          foreach ($childCollection as $collectionChild)
            if ($collectionChild->getName () == $childName)
              return $collectionChild;
          
          throw new \Exception ('No child by this name found');
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
      return Events\Promise::reject ('This collection is not writable and you should not have called this function');
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
      return Events\Promise::reject ('This collection is not writable and you should not have called this function');
    }
    // }}}
  }
