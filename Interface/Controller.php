<?PHP

  /**
   * qcREST - Controller Interface
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
  
  interface qcREST_Interface_Controller {
    // {{{ getURI
    /**
     * Retrive the URI of this controller or a resource/collection related to this one
     * 
     * @param qcREST_Interface_Entity $Resource (optional)
     * 
     * @access public
     * @return string
     **/
    public function getURI (qcREST_Interface_Entity $Resource = null);
    // }}}
    
    // {{{ setRootElement
    /**
     * Set the root resource for this controller
     * 
     * @param qcREST_Interface_Resource $Root
     * 
     * @access public
     * @return bool
     **/
    public function setRootElement (qcREST_Interface_Resource $Root);
    // }}}
    
    // {{{ addProcessor
    /**
     * Register a new input/output-processor on this controller
     * 
     * @param qcREST_Interface_Processor $Processor
     * @param array $Mimetypes (optional) Restrict the processor for these  types
     * 
     * @access public
     * @return bool  
     **/
    public function addProcessor (qcREST_Interface_Processor $Processor, array $Mimetypes = null);
    // }}}
    
    // {{{ addAuthenticator
    /**
     * Register a new authenticator on this controller
     * 
     * @param qcREST_Interface_Authenticator $Authenticator
     * 
     * @access public
     * @return bool
     **/
    public function addAuthenticator (qcREST_Interface_Authenticator $Authenticator);
    // }}}
    
    
    // {{{ getRequest
    /**
     * Generate a Request-Object for a pending request
     * 
     * @access public
     * @return qcREST_Interface_Request
     **/
    public function getRequest ();
    // }}}
    
    // {{{ setResponse
    /** 
     * Write out a response for a previous request
     * 
     * @param qcREST_Interface_Response $Response The response
     * @param callable $Callback (optional) A callback to raise once the operation was completed
     * @param mixed $Private (optional) Any private data to pass to the callback
     * 
     * The callback will be raised once the operation was finished in the form of
     * 
     *   function (qcREST_Interface_Controller $Self, qcREST_Interface_Response $Response, bool $Status, mixed $Private) { }
     * 
     * @access public
     * @return bool  
     **/
    public function setResponse (qcREST_Interface_Response $Response, callable $Callback, $Private = null);
    // }}}
    
    // {{{ handle
    /**
     * Try to process a request, if no request is given explicitly try to fetch one from SAPI
     * 
     * @param callable $Callback
     * @param mixed $Private (optional)
     * @param qcREST_Interface_Request $Request (optional)
     * 
     * The callback will be raised in the form of:
     * 
     *   function (qcREST_Interface_Controller $Self, qcREST_Interface_Request $Request = null, qcREST_Interface_Response $Response = null, bool $Status, mixed $Private = null) { }
     * 
     * @access public
     * @return void
     **/
    public function handle (callable $Callback, $Private = null, qcREST_Interface_Request $Request = null);
    // }}}
  }

?>