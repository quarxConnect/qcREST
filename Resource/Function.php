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
     * @param qcREST_Interface_Request $Request (optional) The request that triggered this call
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function setRepresentation (qcREST_Interface_Representation $Representation, qcREST_Interface_Request $Request = null) : qcEvents_Promise {
      return qcEvents_Promise::reject ('Unimplemented');
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
     * @param qcREST_Interface_Request $Request (optional) The Request that triggered this function-call
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function getChildren (qcREST_Interface_Request $Request = null) : qcEvents_Promise {
      return qcEvents_Promise::reject ('Unimplemented');
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
      return qcEvents_Promise::reject ('Unimplemented');
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
      return $this->invoke ($Representation, $Request);
    }
    // }}}
    
    // {{{ invoke
    /**
     * Run the callback and return result as a promise
     * 
     * @param qcREST_Interface_Representation $Representation
     * @param qcREST_Interface_Request $Request (optional)
     * 
     * @access private
     * @return qcEvents_Promise
     **/
    private function invoke (qcREST_Interface_Representation $Representation, qcREST_Interface_Request $Request = null) : qcEvents_Promise {
      // Create the promise
      if ($this->Async)
        $Promise = new qcEvents_Promise (
          function ($resolve, $reject)
          use ($Representation, $Request) {
            call_user_func ($this->Callback, $Representation, $Request, function ($Result) use ($resolve, $reject) {
              $resolve ($Result);
            });
          }
        );
      else
        $Promise = qcEvents_Promise::resolve (call_user_func ($this->Callback, $Representation, $Request));
      
      return $Promise->then (function ($Result) {
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
        
        return $Result;
      });
    }
    // }}}
    
    // {{{ triggerFunction
    /**
     * Trigger execution of stored function
     * 
     * @param array $Params Parameters for the call
     * @apram qcREST_Interface_Request $Request (optional) A request associated with this call
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function triggerFunction (array $Params, qcREST_Interface_Request $Request = null) : qcEvents_Promise {
      return $this->invoke (new qcREST_Representation ($Params), $Request);
    }
    // }}}
  }

?>