<?php

  /**
   * qcREST - Invokable Resource
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
  
  class Invoke extends REST\Resource implements ABI\Collection {
    // Callback for the represented function
    private $functionCallback = null;
    
    // {{{ __construct
    /**
     * Create a new REST-Function-Resource
     * 
     * @param string $resourceName Name of this REST-Resource
     * @param callable $functionCallback Function to raise
     * 
     * @access friendly
     * @return void
     **/
    function __construct (string $resourceName, callable $functionCallback) {
      // Remember the callback
      $this->functionCallback = $functionCallback;
      
      // Inherit to our parent
      parent::__construct (
        $resourceName,
        [ 'Function' => $Name ],
        false,
        true,
        false,
        $this
      );
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
    public function isBrowsable (Entity\Card $forUser = null) : ?bool {
      return false;
    }
    // }}}
    
    // {{{ getResource
    /**
     * Retrive the parented resource when treating us as collection
     * 
     * @access public
     * @return ABI\Resource
     **/
    public function getResource () : ?ABI\Resource {
      return $this;
    }
    // }}}
    
    // {{{ setRepresentation
    /**
     * Update this resource with a given representation
     * 
     * @param ABI\Representation $newRepresentation Representation to update this resource with
     * @param ABI\Request $fromRequest (optional) The request that triggered this call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function setRepresentation (ABI\Representation $newRepresentation, ABI\Request $fromRequest = null) : Events\Promise {
      # TODO: We could tread PUTs as POST here
      return Events\Promise::reject ('Unimplemented');
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
      return 'NoName';
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
      return Events\Promise::reject ('Unimplemented');
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
      return Events\Promise::reject ('Unimplemented');
    }
    // }}}
    
    // {{{ createChild
    /**
     * Create a new child on this directory
     * 
     * @param ABI\Representation $childRepresentation Representation to create the child from
     * @param string $childName (optional) Explicit name for the child, if none given the directory should generate a new one
     * @param ABI\Request $fromRequest (optional) The Request that triggered this function-call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function createChild (ABI\Representation $childRepresentation, string $childName = null, ABI\Request $fromRequest = null) : Events\Promise {
      if ($childName !== null)
        return Events\Promise::reject ('Invalid endpoint');
      
      return $this->invoke ($childRepresentation, $fromRequest);
    }
    // }}}
    
    // {{{ invoke
    /**
     * Run the callback and return result as a promise
     * 
     * @param ABI\Representation $callRepresentation
     * @param ABI\Request $fromRequest (optional)
     * 
     * @access private
     * @return Events\Promise
     **/
    private function invoke (ABI\Representation $callRepresentation, ABI\Request $fromRequest = null) : Events\Promise {
      // Invoke the callback
      try {
        $callbackResult = call_user_func ($this->functionCallback, $callRepresentation, $fromRequest);
      } catch (\Throwable $callError) {
        $callbackResult = Events\Promise::reject ($callError);
      }
      
      // Make sure we have a promise
      if (!($callbackResult instanceof Events\Promise))
        $callbackResult = Events\Promise::resolve ($callbackResult);
      
      return $callbackResult->then (
        function ($callbackResult) {
          // Process the result
          if ($callbackResult instanceof ABI\Representation) {
            if (count ($callbackResult) == 0)
              $callbackResult ['Result'] = ($callbackResult->getStatus () < 400);
          } else {
            $resultInfo = $callbackResult;
            
            if (is_object ($resultInfo))
              $resultInfo = (array)$resultInfo;
            
            $callbackResult = new REST\Representation (is_array ($resultInfo) ? $resultInfo : [ 'Result' => $resultInfo ]);
            $callbackResult->setStatus ($resultInfo == false ? 500 : ($resultInfo !== null ? 200 : 400));
            $callbackResult->allowRedirect (false);
          }
          
          return $callbackResult;
        }
      );
    }
    // }}}
    
    // {{{ triggerFunction
    /**
     * Trigger execution of stored function
     * 
     * @param array $requestParameters Parameters for the call
     * @apram ABI\Request $fromRequest (optional) A request associated with this call
     * 
     * @access public
     * @return Events\Promise
     **/
    public function triggerFunction (array $requestParameters, ABI\Request $fromRequest = null) : Events\Promise {
      return $this->invoke (new REST\Representation ($requestParameters), $fromRequest);
    }
    // }}}
  }
