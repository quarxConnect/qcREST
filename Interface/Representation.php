<?PHP

  /**
   * qcREST - Representation Interface
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
  
  interface qcREST_Interface_Representation extends Countable, ArrayAccess, Traversable {
    // {{{ getStatus
    /**
     * Retrive the desired status
     * 
     * @access public
     * @return enum NULL if not set
     **/
    public function getStatus ();
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
    public function setStatus ($Status);
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
    public function allowRedirect ($SetRedirect = null);
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
    public function getMeta ($Key = null);
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
    public function addMeta ($Key, $Value);
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
    public function setPreferedOutputTypes (array $Preferences);
    // }}}
    
    // {{{ toArray
    /**
     * Create an array from this representation
     * 
     * @access public
     * @return array
     **/
    public function toArray ();
    // }}}
  }

?>