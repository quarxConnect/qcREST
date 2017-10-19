<?PHP

  /**
   * qcREST - Representation
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
  
  require_once ('qcREST/Interface/Representation.php');
  
  class qcREST_Representation implements IteratorAggregate, qcREST_Interface_Representation {
    /* Key/Value-Storage of this representation */
    private $Set = array ();
    
    private $Meta = array ();
    private $outputPreferences = null;
    private $Status = null;
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
    function __construct (array $Set = null) {
      if ($Set !== null)
        $this->Set = $Set;
    }
    // }}}
    
    // {{{ __isset
    /**
     * Check if an attribute is known here
     * 
     * @param string $Name
     * 
     * @access friendly
     * @return bool
     **/
    function __isset ($Name) {
      return isset ($this->Set [$Name]);
    }
    // }}}
    
    // {{{ __get
    /**
     * Retrive a named attribute
     * 
     * @param string $Name
     * 
     * @access friendly
     * @return mixed
     **/
    function __get ($Name) {
      if (isset ($this->Set [$Name]))
        return $this->Set [$Name];
    }
    // }}}
    
    // {{{ getStatus
    /**
     * Retrive the desired status
     * 
     * @access public
     * @return enum NULL if not set
     **/
    public function getStatus () {
      return $this->Status;
    }
    // }}}
    
    // {{{ setStatus
    /**
     * Force a specific status for the output
     * 
     * @param enum
     * 
     * @access public
     * @return void  
     **/
    public function setStatus ($Status) {
      $this->Status = (int)$Status;
    }
    // }}}
    
    // {{{ allowRedirect
    /**
     * Define wheter redirects are allowed on this representation
     * 
     * @param bool $SetRedirect (optional)
     * 
     * @access public
     * @return bool  
     **/
    public function allowRedirect ($SetRedirect = null) {
      if ($SetRedirect === null)
        return $this->allowRedirect;
      
      $this->allowRedirect = !!$SetRedirect;
      
      return true;
    }
    // }}}
    
    // {{{ getMeta
    /**
     * Retrive all or a specific meta from this representation
     * 
     * @param string $Key (optional) Name of meta-information
     * 
     * @access public
     * @return mixed
     **/
    public function getMeta ($Key = null) {
      if ($Key === null)
        return $this->Meta;
      
      if (isset ($this->Meta [$Key]))
        return $this->Meta [$Key];
    }
    // }}}
    
    // {{{ addMeta
    /**
     * Register a meta-value for this representation
     * 
     * @param string $Key
     * @param string $Value
     * 
     * @access public
     * @return void  
     **/
    public function addMeta ($Key, $Value) {
      $this->Meta [$Key] = $Value;
    }
    // }}}
    
    // {{{ setPreferedOutputTypes
    /**
     * Set a list of prefered output-types
     * 
     * @param array $Preferences
     * 
     * @access public
     * @return void  
     **/
    public function setPreferedOutputTypes (array $Preferences) {
      $this->outputPreferences = $Preferences;
    }
    // }}}
    
    // {{{ toArray
    /**
     * Create an array from this representation
     * 
     * @access public
     * @return array
     **/
    public function toArray () {
      return $this->Set;
    }
    // }}}
    
    /***
     * Implementation of Interface ArrayAccess
     ***/
    
    // {{{ offsetExists
    /**
     * Whether an offset exists
     * 
     * @param mixed $Offset An offset to check for
     * 
     * @access public
     * @return bool
     **/
    public function offsetExists ($Offset) {
      return array_key_exists ($Offset, $this->Set);
    }
    // }}}
    
    // {{{ offsetGet
    /**
     * Offset to retrieve
     * 
     * @param mixed $Offset The offset to retrieve
     * 
     * @access public
     * @return mixed
     **/
    public function offsetGet ($Offset) {
      if (isset ($this->Set [$Offset]))
        return $this->Set [$Offset];
    }
    // }}}
    
    // {{{ offsetSet
    /**
     * Assign a value to the specified offset
     * 
     * @param mixed $Offset The offset to assign the value to
     * @param mixed $Value The value to set
     * 
     * @access public
     * @return void
     **/
    public function offsetSet ($Offset, $Value) {
      if ($Offset !== null)
        $this->Set [$Offset] = $Value;
      else
        $this->Set [] = $Value;
    }
    // }}}
    
    // {{{ offsetUnset
    /**
     * Unset an offset
     * 
     * @param mixed $Offset The offset to unset
     * 
     * @access public
     * @return void
     **/
    public function offsetUnset ($Offset) {
      unset ($this->Set [$Offset]);
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
    public function count () {
      return count ($this->Set);
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
    public function getIterator () {
      return new ArrayIterator ($this->Set);
    }
    // }}}
  }

?>