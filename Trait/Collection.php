<?PHP

  /**
   * qcREST - Collection Trait
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
  
  trait qcREST_Trait_Collection {
    /* Parent resource of this collection */
    private $Resource = null;
    
    // {{{ isWritable
    /**
     * Checks if this collection is writable and may be modified by the client
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
     * Checks if this collection may be removed by the client
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

    // {{{ isBrowsable
    /**
     * Checks if children of this directory may be discovered
     * 
     * @param qcEntity_Card $User (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isBrowsable (qcEntity_Card $User = null) {
      return true;
    }
    // }}}
    
    // {{{ getResource
    /**
     * Retrive the resource of this collection
     * 
     * @access public
     * @return qcREST_Interface_Resource
     **/
    public function getResource () {
      return $this->Resource;
    }
    // }}}
    
    // {{{ setResource
    /**
     * Assign the parented resource of this collection
     * 
     * @param qcREST_Interface_Resource $Resource
     * 
     * @access public
     * @return void
     **/
    public function setResource (qcREST_Interface_Resource $Resource) {
      $this->Resource = $Resource;
    }
    // }}}
    
    // {{{ getNameAttribute
    /**
     * Retrive the name of the name-attribute
     * The name-attribute is used on listings to output the name of each child
     * 
     * @access public
     * @return string
     **/
    public function getNameAttribute () {
      if (!defined (get_class ($this) . '::COLLECTION_NAME_ATTRIBUTE'))
        return 'id';
      
      return $this::COLLECTION_NAME_ATTRIBUTE;
    }
    // }}}
    
    // {{{ getChild
    /**
     * Retrive a single child by its name from this directory
     * 
     * @param string $Name Name of the child to return
     * @param qcREST_Interface_Request $Request (optional) The Request that triggered this function-call
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function getChild ($Name, qcREST_Interface_Request $Request = null) : qcEvents_Promise {
      return $this->getChildren ($Request)->then (
        function (array $Children) use ($Name) {
          foreach ($Children as $Child)
            if ($Child->getName () == $Name)
              return $Child;
          
          throw new exception ('No child by this name found');
        }
      );
    }
    // }}}
    
    // {{{ createChild
    /**
     * Create a new child on this directory
     * 
     * @param qcREST_Interface_Representation $Representation Representation to create the child from
     * @param string $Name (optional) Explicit name for the child, if none given the directory should generate a new one
     * @param qcREST_Interface_Request $Request (optional) The Request that triggered this function-call
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function createChild (qcREST_Interface_Representation $Representation, $Name = null, qcREST_Interface_Request $Request = null) : qcEvents_Promise {
      return qcEvents_Promise::reject ('This collection is not writable and you should not have called this function');
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
      return qcEvents_Promise::reject ('This collection is not writable and you should not have called this function');
    }
    // }}}
  }

?>