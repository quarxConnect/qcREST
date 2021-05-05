<?php

  /**
   * qcREST - Representation
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
  
  class Representation implements \IteratorAggregate, ABI\Representation {
    /* Key/Value-Storage of this representation */
    private $representationAttributes = [ ];
    
    private $Meta = [ ];
    private $outputPreferences = [ ];
    private $desiredStatus = null;
    private $allowRedirect = true;
    
    // {{{ __construct
    /**
     * Create a new representation
     * 
     * @param array $Set (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (array $representationAttributes = null) {
      if ($representationAttributes !== null)
        $this->representationAttributes = $representationAttributes;
    }
    // }}}
    
    // {{{ __isset
    /**
     * Check if an attribute is known here
     * 
     * @param string $attributeName
     * 
     * @access friendly
     * @return bool
     **/
    function __isset ($attributeName) : bool {
      return isset ($this->representationAttributes [$attributeName]);
    }
    // }}}
    
    // {{{ __get
    /**
     * Retrive a named attribute
     * 
     * @param string $attributeName
     * 
     * @access friendly
     * @return mixed
     **/
    function __get (string $attributeName) {
      return $this->representationAttributes [$attributeName] ?? null;
    }
    // }}}
    
    // {{{ getStatus
    /**
     * Retrive the desired status
     * 
     * @access public
     * @return enum NULL if not set
     **/
    public function getStatus () : ?int {
      return $this->desiredStatus;
    }
    // }}}
    
    // {{{ setStatus
    /**
     * Force a specific status for the output
     * 
     * @param enum $newStatus
     * 
     * @access public
     * @return void  
     **/
    public function setStatus (int $newStatus) : void {
      $this->desiredStatus = $newStatus;
    }
    // }}}
    
    // {{{ allowRedirect
    /**
     * Define wheter redirects are allowed on this representation
     * 
     * @param bool $setPolicy (optional)
     * 
     * @access public
     * @return bool  
     **/
    public function allowRedirect (bool $setPolicy = null) : bool {
      if ($setPolicy === null)
        return $this->allowRedirect;
      
      $this->allowRedirect = $setPolicy;
      
      return true;
    }
    // }}}
    
    // {{{ getMeta
    /**
     * Retrive all or a specific meta from this representation
     * 
     * @param string $metaKey (optional) Name of meta-information
     * 
     * @access public
     * @return mixed
     **/
    public function getMeta (string $metaKey = null) {
      if ($metaKey === null)
        return $this->Meta;
      
      return $this->Meta [$metaKey] ?? null;
    }
    // }}}
    
    // {{{ addMeta
    /**
     * Register a meta-value for this representation
     * 
     * @param string $metaKey
     * @param string $metaValue
     * 
     * @access public
     * @return void  
     **/
    public function addMeta (string $metaKey, string $metaValue) : void {
      $this->Meta [$metaKey] = $metaValue;
    }
    // }}}
    
    // {{{ getPreferedOutputTypes
    /**
     * Retrive a list of prefered output-types
     * 
     * @access public
     * @return array
     **/
    public function getPreferedOutputTypes () : array {
      return $this->outputPreferences;
    }
    // }}}
    
    // {{{ setPreferedOutputTypes
    /**
     * Set a list of prefered output-types
     * 
     * @param array $outputPreferences
     * 
     * @access public
     * @return void  
     **/
    public function setPreferedOutputTypes (array $outputPreferences) : void {
      $this->outputPreferences = $outputPreferences;
    }
    // }}}
    
    // {{{ toArray
    /**
     * Create an array from this representation
     * 
     * @access public
     * @return array
     **/
    public function toArray () : array {
      return $this->representationAttributes;
    }
    // }}}
    
    /***
     * Implementation of Interface ArrayAccess
     ***/
    
    // {{{ offsetExists
    /**
     * Whether an offset exists
     * 
     * @param mixed $attributeName An offset to check for
     * 
     * @access public
     * @return bool
     **/
    public function offsetExists ($attributeName) : bool {
      return array_key_exists ($attributeName, $this->representationAttributes);
    }
    // }}}
    
    // {{{ offsetGet
    /**
     * Offset to retrieve
     * 
     * @param mixed $attributeName The offset to retrieve
     * 
     * @access public
     * @return mixed
     **/
    public function offsetGet ($attributeName) {
      return $this->representationAttributes [$attributeName] ?? null;
    }
    // }}}
    
    // {{{ offsetSet
    /**
     * Assign a value to the specified offset
     * 
     * @param mixed $attributeName The offset to assign the value to
     * @param mixed $attributeValue The value to set
     * 
     * @access public
     * @return void
     **/
    public function offsetSet ($attributeName, $attributeValue) : void {
      if ($attributeName !== null)
        $this->representationAttributes [$attributeName] = $attributeValue;
      else
        $this->representationAttributes [] = $attributeValue;
    }
    // }}}
    
    // {{{ offsetUnset
    /**
     * Unset an offset
     * 
     * @param mixed $attributeName The offset to unset
     * 
     * @access public
     * @return void
     **/
    public function offsetUnset ($attributeName) : void {
      unset ($this->representationAttributes [$attributeName]);
    }
    // }}}
    
    /***
     * Implementation of Interface Countable
     ***/
    
    // {{{ count
    /**
     * Count elements of an object
     * 
     * @access public
     * @return int
     **/
    public function count () : int {
      return count ($this->representationAttributes);
    }
    // }}}
    
    /***
     * Implementation of Interface IteratorAggregate
     ***/
    
    // {{{ getIterator
    /**
     * Retrieve an external iterator
     * 
     * @access public
     * @return ArrayIterator
     **/
    public function getIterator () : \ArrayIterator {
      return new \ArrayIterator ($this->representationAttributes);
    }
    // }}}
  }
