<?PHP

  /**
   * qcREST - Merged Resource
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
  
  require_once ('qcREST/Resource.php');
  require_once ('qcREST/Representation.php');
  require_once ('qcREST/Interface/Collection.php');
  
  class qcREST_Resource_Merge extends qcREST_Resource implements qcREST_Interface_Collection {
    /* Stored resources */
    private $Resources = array ();
    
    /* Separator for merged resources */
    private $Separator = ',';
    
    // {{{ __construct
    /**
     * Create a new Merge-Resource
     * 
     * @param string $Name
     * @param bool $Readable (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($Name, $Readable = true) {
      return parent::__construct ($Name, array (), $Readable, false, false);
    }
    // }}}
    
    // {{{ getRepresentation
    /**
     * Retrive a representation of this resource
     * 
     * @param callable $Callback A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback
     * @param qcREST_Interface_Request $Request (optional) A Request-Object associated with this call
     * 
     * The callback will be raised once the operation was completed in the form of:
     *    
     *   function (qcREST_Interface_Resource $Self, qcREST_Interface_Representation $Representation = null, mixed $Private) { }
     * 
     * @access public
     * @return bool  
     **/  
    public function getRepresentation (callable $Callback, $Private = null, qcREST_Interface_Request $Request = null) {
      // Prepare the attributes
      $Counter = count ($this->Resources);
      $Attributes = array (
        'type' => 'Merge-Resource',
        'items' => array (),
      );
      
      // Check if we have to retrive attributes from our children
      if ($Counter == 0)
        return call_user_func ($Callback, $this, new qcREST_Representation ($Attributes), $Private);
      
      // Retrive all attributes from our children
      foreach ($this->Resources as $Resource)
        $Resource->getRepresentation (function (qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $eRepresentation = null) use (&$Counter, &$Attributes, $Callback, $Private) {
          // Check if there were attributes returned
          if ($eAttributes !== null) {
            // Find the right place for this resource
            $Name = $Resource->getName ();
            $Suff = 0;
            
            while (array_key_exists ($Name . ($Suff > 0 ? '_' . $Suff : ''), $Attributes ['items']))
              $Suff++;
            
            // Push the attributes to the collection
            $Attributes ['items'][$Name . ($Suff > 0 ? '_' . $Suff : '')] = $eRepresentation->toArray ();
          }
          
          // Check if we have finished
          if (--$Counter == 0)
            call_user_func ($Callback, $this, new qcREST_Representation ($Attributes), $Private);
        }, null, $Request);
      
      return true;
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
    
    // {{{ hasChildCollection
    /**
     * Determine if this resource as a child-collection available
     * 
     * @access public
     * @return bool  
     **/
    public function hasChildCollection () {
      foreach ($this->Resources as $Resource)
        if ($Resource->hasChildCollection ())
          return true;
      
      return false;
    }
    // }}}
    
    // {{{ getChildCollection
    /**
     * Retrive a child-collection for this node
     * 
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * The callback will be raised in the form of
     * 
     *   function (qcREST_Interface_Resource $Self, qcREST_Interface_Collection $Collection = null, mixed $Private = null) { }
     * 
     * @access public
     * @return bool
     **/
    public function getChildCollection (callable $Callback, $Private = null) {
      call_user_func ($Callback, $this, $this, $Private);
        
      return false;
    }
    // }}}
    
    // {{{ hasResources
    /**
     * Check if there are resources stored on this one
     * 
     * @access public
     * @return bool
     **/
    public function hasResources () {
      return (count ($this->Resources) > 0);  
    }
    // }}}
    
    // {{{ addResource
    /**
     * Store another resource on this one
     * 
     * @param qcREST_Interface_Resource $Resource
     * 
     * @access public
     * @return void
     **/
    public function addResource (qcREST_Interface_Resource $Resource) {
      $this->Resources [] = $Resource;
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
      return 'name';
    }
    // }}}
    
    // {{{ getChildren
    /**
     * Retrive all children on this directory
     * 
     * @param callable $Callback A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback
     * @param qcREST_Interface_Request $Request (optional) The Request that triggered this function-call
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Collection $Self, array $Children = null, mixed $Private) { }
     * 
     * @access public
     * @return bool
     **/
    public function getChildren (callable $Callback, $Private = null, qcREST_Interface_Request $Request = null) {
      // Check if we have any entities assigned
      if (($Counter = count ($this->Resources)) == 0)
        return call_user_func ($Callback, $this, null, $Private);
      
      // Increase the counter
      $Counter++;
      
      // Collect all children
      $Children = array ();
      
      foreach ($this->Resources as $Resource)
        if ($Resource->hasChildCollection ())
          // Request collection-object for this resource
          $Resource->getChildCollection (function (qcREST_Interface_Resource $Resource, qcREST_Interface_Collection $Collection = null) use (&$Counter, &$Children, $Callback, $Private) {
            // Find the right place for this resource
            $Name = $Resource->getName ();
            $Suff = 0;
            
            while (array_key_exists ($Name . ($Suff > 0 ? '_' . $Suff : ''), $Children))
              $Suff++;
            
            $Name = $Name . ($Suff > 0 ? '_' . $Suff : '');
            
            // Reserve that name
            $Children [$Name] = null;
            
            // Process contents of this resource
            if ($Collection && $Collection->isBrowsable ())
              return $Collection->getChildren (function (qcREST_Interface_Collection $Collection, array $eChildren = null) use (&$Counter, &$Children, $Name, $Callback, $Private) {
                $Children [$Name] = $eChildren;
                
                if (--$Counter == 0)
                  $this->forwardChildren ($Children, $Callback, $Private);
              });
            
            if (--$Counter == 0)
              $this->forwardChildren ($Children, $Callback, $Private);
          });
        else
          $Counter--;
      
      // Check if we are done
      if (--$Counter > 0)
        return;
      
      $this->forwardChildren ($Children, $Callback, $Private);
    }
    // }}}
    
    // {{{ forwardChildren
    /**
     * Collect and prepare child-resources to be pushed back to our callee
     * 
     * @param array $Collections
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * @access private
     * @return void
     **/
    private function forwardChildren (array $Collections, callable $Callback, $Private = null) {
      $Resources = array ();
      
      foreach ($Collections as $Parent=>$Children)
        if (is_array ($Children))
          foreach ($Children as $Child) {
            $Name = $Child->getName ();
            
            if (!isset ($Resources [$Name]))
              $Resources [$Name] = $Child;
            elseif ($Resources [$Name] instanceof qcREST_Resource_Merge)
              $Resources [$Name]->addResource ($Child);
            else {
              $Meta = new $this ($Name);
              $Meta->addResource ($Resources [$Name]);
              $Meta->addResource ($Child);
              $Resources [$Name] = $Meta;
            }
          }
      
      call_user_func ($Callback, $this, $Resources, $Private);
    }
    // }}}
    
    // {{{ getChild
    /**
     * Retrive a single child by its name from this directory
     * 
     * @param string $Name Name of the child to return
     * @param callable $Callback A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback
     * @param qcREST_Interface_Request $Request (optional) The Request that triggered this function-call
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Collection $Self, string $Name, qcREST_Interface_Resource $Child = null, mixed $Private) { }
     * 
     * @access public
     * @return bool
     **/
    public function getChild ($Name, callable $Callback, $Private = null, qcREST_Interface_Request $Request = null) {
      # TODO: Speed this up
      return $this->getChildren (function (qcREST_Interface_Collection $Self, array $Resources = null) use ($Name, $Callback, $Private) {
        // Check if the call was successfull
        if (!is_array ($Resources))
          return call_user_func ($Callback, $this, $Name, null, $Private);
        
        // Check if we have a direct match
        if (isset ($Resources [$Name]))
          return call_user_func ($Callback, $this, $Name, $Resources [$Name], $Private);
        
        // Try to split the name up
        if (count ($Names = explode ($this->Separator, $Name)) < 2)
          return call_user_func ($Callback, $this, $Name, null, $Private);
        
        // Create a new merge
        $Merge = new $this ($Name);
        
        foreach ($Names as $Part)
          if (isset ($Resources [$Part]))
            $Merge->addResource ($Resources [$Part]);
        
        // Return the lookup-result
        return call_user_func ($Callback, $this, $Name, ($Merge->hasResources () ? $Merge : null), $Private);
      });
    }
    // }}}
    
    // {{{ createChild
    /**
     * Create a new child on this directory
     * 
     * @param qcREST_Interface_Representation $Representation Representation create the child from
     * @param string $Name (optional) Explicit name for the child, if none given the directory should generate a new one
     * @param callable $Callback (optional) A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback   
     * @param qcREST_Interface_Request $Request (optional) The Request that triggered this function-call
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Collection $Self, string $Name = null, qcREST_Interface_Resource $Child = null, qcREST_Interface_Representation $Representation = null, mixed $Private) { }
     * 
     * @access public
     * @return bool
     **/
    public function createChild (qcREST_Interface_Representation $Representation, $Name = null, callable $Callback = null, $Private = null, qcREST_Interface_Request $Request = null) {
      if ($Callback)
        return call_user_func ($Callback, $this, $Name, null, null, $Private);
    }
    // }}}
  }

?>