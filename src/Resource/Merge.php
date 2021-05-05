<?php

  /**
   * qcREST - Merged Resource
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
  
  namespace quarxConnect\REST\Resource;
  use \quarxConnect\REST\ABI;
  use \quarxConnect\REST;
  use \quarxConnect\Entity;
  use \quarxConnect\Events;
  
  class Merge extends REST\Resource implements ABI\Collection {
    /* Stored resources */
    private $mergedResources = [ ];
    
    /* Stored collections */
    private $mergedCollections = [ ];
    
    /* Separator for merged resources */
    private $nameSeparator = ',';
    
    /* Output full representation of child-resources */
    private $childFullRepresentation = false;
    
    /* Representation-class for collections */
    private $childCollectionRepresentationClass = null;
    
    // {{{ __construct
    /**
     * Create a new Merge-Resource
     * 
     * @param string $resourceName
     * @param bool $isReadable (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (string $resourceName, bool $isReadable = true) {
      return parent::__construct ($resourceName, [ ], $isReadable, false, false);
    }
    // }}}
    
    // {{{ getRepresentation
    /**
     * Retrive a representation of this resource
     * 
     * @param ABI\Request $forRequest (optional) A Request-Object associated with this call
     * 
     * @access public
     * @return Events\Promise
     **/  
    public function getRepresentation (ABI\Request $forRequest = null) : Events\Promise {
      // Prepare the attributes
      $childPromises = [ ];
      
      foreach ($this->mergedResources as $resourceIndex=>$mergedResource)
        $childPromises [$resourceIndex] = $mergedResource->getRepresentation ($forRequest);
      
      // Collect the results
      return Events\Promise::all ($childPromises)->then (
        function (array $childResults) {
        // Setup final attributes
        $resultAttributes = array (
          'type' => 'Merge-Resource',
          'items' => [ ],
        );
        
        // Append each response to attributes
        foreach ($childResults as $resourceIndex=>$childResult) {
          // Check if there was a representation received
          if (!is_object ($childResult))
            continue;
          
          // Find the right place for this resource
          $childName = $this->mergedResources [$resourceIndex]->getName ();
          $childSuffix = 0;
          
          while (array_key_exists ($childName . ($childSuffix > 0 ? '_' . $childSuffix : ''), $resultAttributes ['items']))
            $childSuffix++;
          
          // Push the attributes to the collection
          $resultAttributes ['items'][$childName . ($childSuffix > 0 ? '_' . $childSuffix : '')] = $childResult->toArray ();
        }
          
        // Run the final callback
        return new REST\Representation ($resultAttributes);
      });
    }
    // }}}
    
    // {{{ isBrowsable
    /**
     * Checks if children of this directory may be discovered
     * 
     * @param Entity\Card $forUser (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isBrowsable (Entity\Card $forUser = null) {
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
    public function hasChildCollection () : bool {
      // Succeed if there are collections registered directly
      if (count ($this->mergedCollections) > 0)
        return true;
      
      // Ask our merged resources for collections
      foreach ($this->mergedResources as $mergedResource)
        if ($mergedResource->hasChildCollection ())
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
     * @return ABI\Resource
     **/
    public function getResource () : ?ABI\Resource {
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
    public function getChildFullRepresenation () : bool {
      return $this->childFullRepresentation;
    }
    // }}}
    
    // {{{ setChildFullRepresentation
    /**
     * Set wheter to output full representation of our child-resources
     * 
     * @param bool $setPolicy (optional)
     * 
     * @access public
     * @return void
     **/
    public function setChildFullRepresentation (bool $setPolicy = true) : void {
      $this->childFullRepresentation = $setPolicy;
    }
    // }}}
    
    // {{{ setCollectionRepresentationClass
    /**
     * Set a class or object-instance as child-collection-represenation-class
     * 
     * @param mixed $collectionRepresentationClass
     * 
     * @access public
     * @return void
     **/
    public function setCollectionRepresentationClass ($collectionRepresentationClass) : void {
      $this->childCollectionRepresentationClass = $collectionRepresentationClass;
    }
    // }}}
    
    // {{{ getChildCollection
    /**
     * Retrive a child-collection for this node
     * 
     * @access public
     * @return Events\Promise
     **/
    public function getChildCollection () : Events\Promise {
      return Events\Promise::resolve ($this);
    }
    // }}}
    
    // {{{ hasResources
    /**
     * Check if there are resources stored on this one
     * 
     * @access public
     * @return bool
     **/
    public function hasResources () : bool {
      return (count ($this->mergedResources) > 0);  
    }
    // }}}
    
    // {{{ addResource
    /**
     * Store another resource on this one
     * 
     * @param ABI\Resource $Resource
     * 
     * @access public
     * @return void
     **/
    public function addResource (ABI\Resource $mergeResource) : void {
      $this->mergedResources [] = $mergeResource;
    }
    // }}}
    
    // {{{ addCollection
    /**
     * Store another collection on this one
     * 
     * @param ABI\Collection $mergeCollection
     * 
     * @access public
     * @return void
     **/
    public function addCollection (ABI\Collection $mergeCollection) : void {
      $this->mergedCollections [] = $mergeCollection;
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
    public function getNameAttribute () : string {
      return 'name';
    }
    // }}}
    
    // {{{ getChildren
    /**
     * Retrive all children on this directory
     * 
     * @param ABI\Request $forRequest (optional) The Request that triggered this function-call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function getChildren (ABI\Request $forRequest = null) : Events\Promise {
      // Get user of the request
      $authenticatedUser = ($forRequest ? $forRequest->getUser () : null);
      
      // Prepare the promises
      $childPromises = [ ];
      
      foreach ($this->mergedResources as $mergedResource)
        if ($mergedResource->hasChildCollection ())
          $childPromises [] = $mergedResource->getChildCollection ()->then (
            function (ABI\Collection $childCollection) use ($authenticatedUser, $forRequest) {
              if (!$childCollection->isBrowsable ($authenticatedUser))
                return [ ];
              
              return $childCollection->getChildren ($forRequest);
            }
          )->catch (
            function () {
              return [ ];
            }
          );
      
      foreach ($this->mergeCollections as $mergedCollection)
        if ($mergedCollection->isBrowsable ($authenticatedUser))
          $childPromises [] = $mergedCollection->getChildren ($forRequest)->catch (
            function () {
              return [ ];
            }
          );
      
      return Events\Promise::all ($childPromises)->then (
        function (array $mergedResults) {
          // Collect all children
          $mergedChildren = call_user_func_array ('array_merge', $mergedResults);
          
          // Collect all child-resources and merge if neccessary
          $mergedResources = [ ];
          
          foreach ($mergedChildren as $mergedChild) {
            // Retrive the name of that child
            $childName = $mergedChild->getName ();
            
            // Check if a child by this name was already seen
            if (!isset ($mergedResources [$childName]))
              $mergedResources [$childName] = $mergedChild;
            
            // Try to merge child into an existing merge
            elseif ($mergedResources [$childName] instanceof Merge)
              $mergedResources [$childName]->addResource ($mergedChild);
            
            // Create a new merge for this child
            else {
              $mergedResource = new $this ($childName);
              $mergedResource->addResource ($mergedResources [$childName]);
              $mergedResource->addResource ($mergedChild);
              $mergedResource->setCollection ($this);
              $mergedResources [$childName] = $mergedResource;
            }
          }
          
          // Check wheter to create a representation-class for the children
          if ($this->childCollectionRepresentationClass) {
            $mergedRepresentation = $this->childCollectionRepresentationClass;
            
            if (!is_object ($mergedRepresentation))
              $mergedRepresentation = new $mergedRepresentation ($this, $mergedResources);
            else
              $mergedRepresentation->setCollection ($mergedResources);
          } else
            $mergedRepresentation = null;
          
          return [ $mergedResources, $mergedRepresentation ];
        }
      );
    }
    // }}}
    
    // {{{ getChild
    /**
     * Retrive a single child by its name from this directory
     * 
     * @param string $childName Name of the child to return
     * @param ABI\Request $forRequest (optional) The Request that triggered this function-call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function getChild (string $childName, ABI\Request $forRequest = null) : Events\Promise {
      # TODO: Speed this up
      return $this->getChildren ($forRequest)->then (
        function (array $mergedResources) use ($childName) {
          // check for a direct match
          if (isset ($mergedResources [$childName]))
            return $mergedResources [$childName];
          
          // Try to lookup multiple resources
          $childNames = explode ($this->nameSeparator, $childName);
          
          if (count ($childNames) < 2)
            throw new \Exception ('Resource not found');
          
          $mergedResource = new $this ($childName);
          
          foreach ($childNames as $childName)
            if (isset ($mergedResources [$childName]))
              $mergedResource->addResource ($mergedResources [$childName]);
          
          if (!$mergedResource->hasResources ())
            throw new \Exception ('No Subresources not found');
          
          return $mergedResource;
        }
      );
    }
    // }}}
    
    // {{{ createChild
    /**
     * Create a new child on this directory
     * 
     * @param ABI\Representation $childRepresentation Representation create the child from
     * @param string $childName (optional) Explicit name for the child, if none given the directory should generate a new one
     * @param ABI\Request $fromRequest (optional) The Request that triggered this function-call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function createChild (ABI\Representation $childRepresentation, string $childName = null, ABI\Request $fromRequest = null) : Events\Promise {
      return Events\Promise::reject ('Unimplemented');
    }
    // }}}
  }
