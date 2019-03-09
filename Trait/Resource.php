<?PHP

  /**
   * qcREST - Resource Trait
   * Copyright (C) 2019 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  require_once ('qcEvents/Promise.php');
  
  trait qcREST_Trait_Resource {
    /* Assigned parent-collection */
    private $Collection = null;
    
    // {{{ isReadable
    /**
     * Checks if this resource's attributes might be forwarded to the client
     * 
     * @param qcEntity_Card $User (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isReadable (qcEntity_Card $User = null) {
      return true;
    }
    // }}}
    
    // {{{ isWritable
    /**
     * Checks if this resource is writable and may be modified by the client
     * 
     * @param qcEntity_Card $User (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isWritable (qcEntity_Card $User = null) {
      return false;
    }
    // }}}
    
    // {{{ isRemovable
    /**
     * Checks if this resource may be removed by the client
     * 
     * @param qcEntity_Card $User (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isRemovable (qcEntity_Card $User = null) {
      return false;
    }
    // }}}
    
    // {{{ getCollection
    /**
     * Retrive the parented collection of this resource
     * 
     * @access public
     * @return qcREST_Interface_Collection
     **/
    public function getCollection () {
      return $this->Collection;
    }
    // }}}
    
    // {{{ setCollection
    /**
     * Assign a parented collection to this resource
     * 
     * @param qcREST_Interface_Collection $Collection
     * 
     * @access public
     * @return void
     **/
    public function setCollection (qcREST_Interface_Collection $Collection) {
      $this->Collection = $Collection;
    }
    // }}}
    
    // {{{ hasChildCollection
    /**
     * Determine if this resource as a child-collection available
     * 
     * @access public
     * @return bool
     **/
    public function hasChildCollection () {
      return false;
    }
    // }}}
    
    // {{{ getChildCollection
    /**
     * Retrive a child-collection for this node
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function getChildCollection () : qcEvents_Promise {
      return qcEvents_Promise::reject ('This resource does not have a collection assigned and you should not have called this function');
    }
    // }}}
    
    // {{{ setRepresentation
    /**
     * Update this resource from a given representation
     * 
     * @param qcREST_Interface_Representation $Representation Representation to update this resource from
     * @param qcREST_Interface_Request $Request (optional) The request that triggered this call
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function setRepresentation (qcREST_Interface_Representation $Representation, qcREST_Interface_Request $Request = null) : qcEvents_Promise {
      return qcEvents_Promise::reject ('This resource is not writable and you should not have called this function');
    }
    // }}}
    
    // {{{ remove
    /**
     * Remove this resource from the server
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function remove () : qcEvents_Promise {
      return qcEvents_Promise::reject ('This resource is not writable and you should not have called this function');
    }
    // }}}
  }

?>