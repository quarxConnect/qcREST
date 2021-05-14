<?php

  /**
   * qcREST - Collection Interface
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

  namespace quarxConnect\REST\ABI;
  use \quarxConnect\Events;
  
  interface Collection extends Entity {
    // {{{ isWritable
    /**
     * Checks if this collection is writable and may be modified by the client
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
     * Checks if this collection may be removed by the client
     * 
     * @param \quarxConnect\Entity\Card $forUser (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isRemovable (\quarxConnect\Entity\Card $forUser = null) : ?bool;
    // }}}
    
    // {{{ isBrowsable
    /**
     * Checks if children of this directory may be discovered
     * 
     * @param \quarxConnect\Entity\Card $forUser (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isBrowsable (\quarxConnect\Entity\Card $forUser = null) : ?bool;
    // }}}
    
    
    // {{{ getResource
    /**
     * Retrive the resource of this collection
     * 
     * @access public
     * @return Resource
     **/
    public function getResource () : ?Resource;
    // }}}
    
    // {{{ getNameAttribute
    /**
     * Retrive the name of the name-attribute
     * The name-attribute is used on listings to output the name of each child
     * 
     * @access public
     * @return string
     **/
    public function getNameAttribute () : string;
    // }}}
    
    // {{{ getChildFullRepresenation
    /**
     * Check wheter full representation of children should be shown on listings
     * 
     * @remark This function is optional and need not to be implemented, the controller will check on his own wheter this is usable
     * 
     * @access public
     * @return bool
     **/
    # public function getChildFullRepresenation () : bool;
    // }}}
    
    // {{{ getChildren
    /**
     * Retrive all children on this directory
     * 
     * @param Request $forRequest (optional) The Request that triggered this function-call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function getChildren (Request $forRequest = null) : Events\Promise;
    // }}}
    
    // {{{ getChild
    /**
     * Retrive a single child by its name from this directory
     * 
     * @param string $childName Name of the child to return
     * @param Request $forRequest (optional) The Request that triggered this function-call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function getChild (string $childName, Request $forRequest = null) : Events\Promise;
    // }}}
    
    // {{{ createChild
    /**
     * Create a new child on this directory
     * 
     * @param Representation $childRepresentation Representation to create the child from
     * @param string $childName (optional) Explicit name for the child, if none given the directory should generate a new one
     * @param Request $fromRequest (optional) The Request that triggered this function-call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function createChild (Representation $childRepresentation, string $childName = null, Request $fromRequest = null) : Events\Promise;
    // }}}
    
    // {{{ remove  
    /** 
     * Remove this resource from the server
     * 
     * @param Request $fromRequest (optional) The Request that triggered this function-call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function remove (Request $fromRequest = null) : Events\Promise;
    // }}}
  }
