<?php

  /**
   * qcREST - Resource Trait
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
  
  trait Resource {
    /* Assigned parent-collection */
    private $parentCollection = null;
    
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
      return true;
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
      return false;
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
      return false;
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
     * Assign a parented collection to this resource
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
      return false;
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
      return Events\Promise::reject ('This resource does not have a collection assigned and you should not have called this function');
    }
    // }}}
    
    // {{{ setRepresentation
    /**
     * Update this resource from a given representation
     * 
     * @param ABI\Representation $newRepresentation Representation to update this resource from
     * @param ABI\Request $fromRequest (optional) The request that triggered this call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function setRepresentation (ABI\Representation $newRepresentation, ABI\Request $fromRequest = null) : Events\Promise {
      return Events\Promise::reject ('This resource is not writable and you should not have called this function');
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
      return Events\Promise::reject ('This resource is not writable and you should not have called this function');
    }
    // }}}
  }
