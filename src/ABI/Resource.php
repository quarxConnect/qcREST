<?php

  /**
   * qcREST - Resource Interface
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
   * long with this program.  If not, see <http://www.gnu.org/licenses/>.
   **/
  
  declare (strict_types=1);

  namespace quarxConnect\REST\ABI;
  use \quarxConnect\Events;
  
  interface Resource extends Entity {
    // {{{ isReadable
    /**
     * Checks if this resource's attributes might be forwarded to the client
     * 
     * @param \quarxConnect\Entity\Card $forUser (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isReadable (\quarxConnect\Entity\Card $forUser = null) : ?bool;
    // }}}
    
    // {{{ isWritable
    /**
     * Checks if this resource is writable and may be modified by the client
     * 
     * @param \quarxConnect\Entity\Card $forUser (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isWritable (\quarxConnect\Entity\Card $forUser = null) : ?bool;
    // }}}
    
    // {{{ isRemovable
    /**
     * Checks if this resource may be removed by the client
     * 
     * @param \quarxConnect\Entity\Card $forUser (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isRemovable (\quarxConnect\Entity\Card $forUser = null) : ?bool;
    // }}}
    
    
    // {{{ getName
    /**
     * Retrive the name of this resource
     * 
     * @access public
     * @return string
     **/
    public function getName () : string;
    // }}}
    
    // {{{ getCollection
    /**
     * Retrive the parented collection of this resource
     * 
     * @access public
     * @return Collection
     **/
    public function getCollection () : ?Collection;
    // }}}
    
    // {{{ hasChildCollection
    /**
     * Determine if this resource as a child-collection available
     * 
     * @access public
     * @return bool
     **/
    public function hasChildCollection () : bool;
    // }}}
    
    // {{{ getChildCollection
    /**
     * Retrive a child-collection for this node
     * 
     * @access public
     * @return Events\Promise
     **/
    public function getChildCollection () : Events\Promise;
    // }}}
    
    // {{{ getRepresentation
    /**
     * Retrive a representation of this resource
     * 
     * @param Request $forRequest (optional) A Request-Object associated with this call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function getRepresentation (Request $forRequest = null) : Events\Promise;
    // }}}
    
    // {{{ setRepresentation
    /**
     * Update this resource from a given representation
     * 
     * @param Representation $newRepresentation Representation to update this resource from
     * @param Request $fromRequest (optional) The request that triggered this call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function setRepresentation (Representation $newRepresentation, Request $fromRequest = null) : Events\Promise;
    // }}}
    
    // {{{ remove
    /**
     * Remove this resource from the server
     * 
     * @access public
     * @return Events\Promise
     **/
    public function remove () : Events\Promise;
    // }}}
  }
