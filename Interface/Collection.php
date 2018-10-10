<?PHP

  /**
   * qcREST - Collection Interface
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
  
  require_once ('qcREST/Interface/Entity.php');
  require_once ('qcREST/Interface/Resource.php');
  
  interface qcREST_Interface_Collection extends qcREST_Interface_Entity {
    // {{{ isWritable
    /**
     * Checks if this collection is writable and may be modified by the client
     * 
     * @param qcEntity_Card $User (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isWritable (qcEntity_Card $User = null);
    // }}}
    
    // {{{ isRemovable
    /**
     * Checks if this collection may be removed by the client
     * 
     * @param qcEntity_Card $User (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isRemovable (qcEntity_Card $User = null);
    // }}}
    
    // {{{ isBrowsable
    /**
     * Checks if children of this directory may be discovered
     * 
     * @param qcEntity_Card $User (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isBrowsable (qcEntity_Card $User = null);
    // }}}
    
    
    // {{{ getResource
    /**
     * Retrive the resource of this collection
     * 
     * @access public
     * @return qcREST_Interface_Resource
     **/
    public function getResource ();
    // }}}
    
    // {{{ getNameAttribute
    /**
     * Retrive the name of the name-attribute
     * The name-attribute is used on listings to output the name of each child
     * 
     * @access public
     * @return string
     **/
    public function getNameAttribute ();
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
    # public function getChildFullRepresenation ();
    // }}}
    
    // {{{ getChildren
    /**
     * Retrive all children on this directory
     * 
     * @param qcREST_Interface_Request $Request (optional) The Request that triggered this function-call
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function getChildren (qcREST_Interface_Request $Request = null) : qcEvents_Promise;
    // }}}
    
    // {{{ getChild
    /**
     * Retrive a single child by its name from this directory
     * 
     * @param string $Name Name of the child to return
     * @param qcREST_Interface_Request $Request (optional) The Request that triggered this function-call
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Collection $Self, qcREST_Interface_Resource $Child = null, mixed $Private) { }
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function getChild ($Name, qcREST_Interface_Request $Request = null) : qcEvents_Promise;
    // }}}
    
    // {{{ createChild
    /**
     * Create a new child on this directory
     * 
     * @param qcREST_Interface_Representation $Representation Representation to create the child from
     * @param string $Name (optional) Explicit name for the child, if none given the directory should generate a new one
     * @param callable $Callback (optional) A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback
     * @param qcREST_Interface_Request $Request (optional) The Request that triggered this function-call
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Collection $Self, qcREST_Interface_Resource $Child = null, qcREST_Interface_Representation $Representation = null, mixed $Private) { }
     * 
     * @access public
     * @return void
     **/
    public function createChild (qcREST_Interface_Representation $Representation, $Name = null, callable $Callback = null, $Private = null, qcREST_Interface_Request $Request = null);
    // }}}
    
    // {{{ remove  
    /** 
     * Remove this resource from the server
     *    
     * @access public
     * @return qcEvents_Promise
     **/
    public function remove () : qcEvents_Promise;
    // }}}
  }

?>