<?php

  /**
   * qcREST - Representation Interface
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
  
  interface Representation extends \Countable, \ArrayAccess, \Traversable {
    // {{{ getStatus
    /**
     * Retrive the desired status
     * 
     * @access public
     * @return enum NULL if not set
     **/
    public function getStatus () : ?int;
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
    public function setStatus (int $newStatus) : void;
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
    public function allowRedirect (bool $setPolicy = null) : bool;
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
    public function getMeta (string $metaKey = null);
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
    public function addMeta (string $metaKey, string $metaValue) : void;
    // }}}
    
    // {{{ getPreferedOutputTypes
    /**
     * Retrive a list of prefered output-types
     * 
     * @access public
     * @return array
     **/
    public function getPreferedOutputTypes () : array;
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
    public function setPreferedOutputTypes (array $outputPreferences) : void;
    // }}}
    
    // {{{ toArray
    /**
     * Create an array from this representation
     * 
     * @access public
     * @return array
     **/
    public function toArray () : array;
    // }}}
  }
