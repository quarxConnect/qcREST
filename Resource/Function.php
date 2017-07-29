<?PHP

  /**
   * qcREST - Function Resource
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
  
  require_once ('qcREST/Interface/Collection.php');
  require_once ('qcREST/Resource.php');
  require_once ('qcREST/Representation.php');
  
  class qcREST_Resource_Function extends qcREST_Resource implements qcREST_Interface_Collection {
    // Callback for the represented function
    private $Callback = null;
    
    // Async-Status
    private $Async = false;
    
    // {{{ __construct
    /**
     * Create a new REST-Function-Resource
     * 
     * @param string $Name Name of this REST-Resource
     * @param callable $Function Function to raise
     * @param bool $Async (optional) Perform the function-call asynchronous
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($Name, callable $Callback, $Async = false) {
      // Remember the callback
      $this->Callback = $Callback;
      $this->Async = $Async;
      
      // Inherit to our parent
      parent::__construct (
        $Name,
        array ('Function' => $Name),
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
     * @param qcEntity_Card $User (optional)
     * 
     * @access public
     * @return bool
     **/
    public function isBrowsable (qcEntity_Card $User = null) { return false; }
    // }}}
    
    // {{{ getResource
    /**
     * Retrive the parented resource when treating us as collection
     * 
     * @access public
     * @return qcREST_Interface_Resource
     **/
    public function getResource () {
      return $this;
    }
    // }}}
    
    // {{{ setRepresentation
    /**
     * Update this resource with a given representation
     * 
     * @param qcREST_Interface_Representation $Representation Representation to update this resource with
     * @param callable $Callback (optional) A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback
     * 
     * The callback will be raised once the operation was completed in the form of:
     *    
     *   function (qcREST_Interface_Resource $Self, qcREST_Interface_Representation $Representation, bool $Status, mixed $Private) { }
     * 
     * @access public
     * @return void
     **/
    public function setRepresentation (qcREST_Interface_Representation $Representation, callable $Callback = null, $Private = null) {
      if ($Callback)
        call_user_func ($Callback, $this, $Representation, false, $Private);
      
      return true;
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
    public function getNameAttribute () { return 'NoName'; }
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
     * @return void
     **/
    public function getChildren (callable $Callback, $Private = null, qcREST_Interface_Request $Request = null) {
      return call_user_func ($Callback, $this, null, $Private);
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
     *   function (qcREST_Interface_Collection $Self, qcREST_Interface_Resource $Child = null, mixed $Private) { }
     * 
     * @access public
     * @return void
     **/
    public function getChild ($Name, callable $Callback, $Private = null, qcREST_Interface_Request $Request = null) {
      return call_user_func ($Callback, $this, null, $Private);
    }
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
     *   function (qcREST_Interface_Collection $Self qcREST_Interface_Resource $Child = null, qcREST_Interface_Representation $Representation = null, mixed $Private) { }
     * 
     * @access public
     * @return void
     **/
    public function createChild (qcREST_Interface_Representation $Representation, $Name = null, callable $Callback = null, $Private = null, qcREST_Interface_Request $Request = null) {
      // Don't do anything without a valid callback
      if (!$Callback)
        return;
      
      // Check if the call is synchronous
      if (!$this->Async)
        return $this->forwardResult (call_user_func ($this->Callback, $Representation, $Request), false, $Callback, $Private);
      
      return call_user_func ($this->Callback, $Representation, $Request, function ($Result) use ($Callback, $Private) {
        $this->forwardResult ($Result, false, $Callback, $Private);
      });
    }
    // }}}
    
    // {{{ forwardResult
    /**
     * Forward the result of a function-call
     * 
     * @param mixed $Result
     * @param bool $Short (optional)
     * @param callable $Callback (optional)
     * @param mixed $Private (optional)
     *  
     * @access private
     * @return void
     **/
    private function forwardResult ($Result, $Short = false, callable $Callback = null, $Private = null) {
      // Check if we have to do anything
      if (!$Callback)
        return;
      
      // Process the result
      if ($Result instanceof qcREST_Representation) {
        if (count ($Result) == 0)
          $Result ['Result'] = ($Result->getStatus () < 400);
      } else {
        $Info = $Result;
        
        if (is_object ($Info))
          $Info = (array)$Info;
        
        $Result = new qcREST_Representation (is_array ($Info) ? $Info : array ('Result' => $Info));
        $Result->setStatus ($Info == false ? 500 : ($Info !== null ? 200 : 400));
        $Result->allowRedirect (false);
      }
      
      // Forward the result
      if ($Short)
        return call_user_func ($Callback, $this, $Result, $Private);
      
      return call_user_func ($Callback, $this, $this, $Result, $Private);
    }
    // }}}
    
    // {{{ triggerFunction
    /**
     * Trigger execution of stored function
     * 
     * @param array $Params Parameters for the call
     * @param callable $Callback (optional) A callback to forward the result to
     * @param mixed $Private (optional) Any private data to pass to the callback
     * @apram qcREST_Interface_Request $Request (optional) A request associated with this call
     * 
     * The callback will be raised in the form of:
     * 
     *   function (qcREST_Resource_Function $Self, qcREST_Interface_Representation $Result = null, mixed $Private = null) { }
     * 
     * @access public
     * @return void
     **/
    public function triggerFunction (array $Params, callable $Callback = null, $Private = null, qcREST_Interface_Request $Request = null) {
      // Create Representation
      $Representation = new qcREST_Representation ($Params);
      
      // Check if the call is synchronous
      if (!$this->Async)
        return $this->forwardResult (call_user_func ($this->Callback, $Representation, $Request), true, $Callback, $Private);
      
      // Call asynchronous
      return call_user_func ($this->Callback, $Representation, $Request, function ($Result) use ($Callback, $Private) {
        $this->forwardResult ($Result, true, $Callback, $Private);
      });
    }
    // }}}
  }

?>