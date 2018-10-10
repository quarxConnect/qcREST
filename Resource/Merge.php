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
  require_once ('qcEvents/Queue.php');
  require_once ('qcEvents/Promise.php');
  
  class qcREST_Resource_Merge extends qcREST_Resource implements qcREST_Interface_Collection {
    /* Stored resources */
    private $Resources = array ();
    
    /* Stored collections */
    private $Collections = array ();
    
    /* Separator for merged resources */
    private $Separator = ',';
    
    /* Output full representation of child-resources */
    private $childFullRepresentation = false;
    
    /* Representation-class for collections */
    private $childCollectionRepresentationClass = null;
    
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
     * @return void
     **/  
    public function getRepresentation (callable $Callback, $Private = null, qcREST_Interface_Request $Request = null) {
      // Prepare the attributes
      $Queue = new qcEvents_Queue;
      
      foreach ($this->Resources as $Resource)
        $Queue->addCall ($Resource, $getRepresentation, null, null, $Request);
       
      $Queue->finish (
        function (qcEvents_Queue $Queue, array $Results)
        use ($Callback, $Private) {
          // Setup final attributes
          $Attributes = array (
            'type' => 'Merge-Resource',
            'items' => array (),
          );
          
          // Append each response to attributes
          foreach ($Results as $Result) {
            // Check if there was a representation received
            if (!is_object ($Result [1]))
              continue;
            
            // Find the right place for this resource
            $Name = $Result [0]->getName ();
            $Suff = 0;
            
            while (array_key_exists ($Name . ($Suff > 0 ? '_' . $Suff : ''), $Attributes ['items']))
              $Suff++;
            
            // Push the attributes to the collection
            $Attributes ['items'][$Name . ($Suff > 0 ? '_' . $Suff : '')] = $Result [1]->toArray ();
          }
          
          // Run the final callback
          call_user_func ($Callback, $this, new qcREST_Representation ($Attributes), $Private);
        }, null,
        true
      );
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
      // Succeed if there are collections registered directly
      if (count ($this->Collections) > 0)
        return true;
      
      // Ask our merged resources for collections
      foreach ($this->Resources as $Resource)
        if ($Resource->hasChildCollection ())
          return true;
      
      // Fail if we get here
      return false;
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
      return $this;
    }
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
    public function getChildFullRepresenation () {
      return $this->childFullRepresentation;
    }
    // }}}
    
    // {{{ setChildFullRepresentation
    /**
     * Set wheter to output full representation of our child-resources
     * 
     * @param bool $Toggle
     * 
     * @access public
     * @return void
     **/
    public function setChildFullRepresentation ($Toggle) {
      $this->childFullRepresentation = !!$Toggle;
    }
    // }}}
    
    // {{{ setCollectionRepresentationClass
    /**
     * Set a class or object-instance as child-collection-represenation-class
     * 
     * @param mixed $Class
     * 
     * @access public
     * @return void
     **/
    public function setCollectionRepresentationClass ($Class) {
      $this->childCollectionRepresentationClass = $Class;
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
     * @return void
     **/
    public function getChildCollection (callable $Callback, $Private = null) {
      call_user_func ($Callback, $this, $this, $Private);
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
    
    // {{{ addCollection
    /**
     * Store another collection on this one
     * 
     * @param qcREST_Interface_Collection $Collection
     * 
     * @access public
     * @return void
     **/
    public function addCollection (qcREST_Interface_Collection $Collection) {
      $this->Collections [] = $Collection;
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
     *   function (qcREST_Interface_Collection $Self, array $Children = null, qcREST_Interface_Representation $Representation = null, mixed $Private) { }
     * 
     * @access public
     * @return void
     **/
    public function getChildren (callable $Callback, $Private = null, qcREST_Interface_Request $Request = null) {
      // Prepare the queue
      $Queue = new qcEvents_Queue;
      $User = ($Request ? $Request->getUser () : null);
      
      foreach ($this->Resources as $Resource)
        if ($Resource->hasChildCollection ())
          $Queue->addCall ($Resource, 'getChildCollection');
      
      foreach ($this->Collections as $Collection)
        if ($Collection->isBrowsable ($User))
          $Queue->addCall ($Collection, 'getChildren', null, null, $Request);
      
      $Queue->onResult (
        function (qcEvents_Queue $Queue, array $Result)
        use ($Request) {
          // Check for a received child-collection
          if (($Result [0] instanceof qcREST_Interface_Resource) && $Result [1])
            $Queue->addCall ($Result [1], 'getChildren', null, null, $Request);
        }
      );
      
      $Queue->finish (
        function (qcEvents_Queue $Queue, array $Results)
        use ($Callback, $Private) {
          // Collect all children
          $Children = array ();
          
          foreach ($Results as $Result)
            if (($Result [0] instanceof qcREST_Interface_Collection) && $Result [1])
              $Children [] = $Result [1];
          
          // Forward the result
          $this->forwardChildren ($Children, $Callback, $Private);
        }, null,
        true
      );
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
      // Collect all child-resources and merge if neccessary
      $Resources = array ();
      
      foreach ($Collections as $Children)
        if (is_array ($Children))
          foreach ($Children as $Child) {
            // Retrive the name of that child
            $Name = $Child->getName ();
            
            // Check if a child by this name was already seen
            if (!isset ($Resources [$Name]))
              $Resources [$Name] = $Child;
            
            // Try to merge child into an existing merge
            elseif ($Resources [$Name] instanceof qcREST_Resource_Merge)
              $Resources [$Name]->addResource ($Child);
            
            // Create a new merge for this child
            else {
              $Meta = new $this ($Name);
              $Meta->addResource ($Resources [$Name]);
              $Meta->addResource ($Child);
              $Meta->setCollection ($this);
              $Resources [$Name] = $Meta;
            }
          }
      
      // Check wheter to create a representation-class for the children
      if ($this->childCollectionRepresentationClass) {
        $Class = $this->childCollectionRepresentationClass;
        
        if (!is_object ($Class))
          $Class = new $Class ($this, $Resources);
        else
          $Class->setCollection ($Resources);
      } else
        $Class = null;
      
      // Forward the result
      call_user_func ($Callback, $this, $Resources, $Class, $Private);
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
      return new qcEvents_Promise (function ($resolve, $reject) use ($Name, $Request) {
        # TODO: Speed this up
        return $this->getChildren (
          function (qcREST_Interface_Collection $Self, array $Resources = null)
          use ($Name, $resolve, $reject) {
            // Check if the call was successfull
            if (!is_array ($Resources))
              return $reject ();
            
            // Check if we have a direct match
            if (isset ($Resources [$Name]))
              return $resolve ($Resources [$Name]);
            
            // Try to split the name up
            if (count ($Names = explode ($this->Separator, $Name)) < 2)
              return $reject ();
            
            // Create a new merge
            $Merge = new $this ($Name);
            
            foreach ($Names as $Part)
              if (isset ($Resources [$Part]))
                $Merge->addResource ($Resources [$Part]);
            
            // Return the lookup-result
            if ($Merge->hasResources ())
              return $resolve ($Merge);
            
            return $reject ();
          }
        );
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
     *   function (qcREST_Interface_Collection $Self, qcREST_Interface_Resource $Child = null, qcREST_Interface_Representation $Representation = null, mixed $Private) { }
     * 
     * @access public
     * @return bool
     **/
    public function createChild (qcREST_Interface_Representation $Representation, $Name = null, callable $Callback = null, $Private = null, qcREST_Interface_Request $Request = null) {
      if ($Callback)
        return call_user_func ($Callback, $this, null, null, $Private);
    }
    // }}}
  }

?>