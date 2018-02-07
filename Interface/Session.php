<?PHP

  /**
   * qcREST - Session Interface
   * Copyright (C) 2018 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  interface qcREST_Interface_Session extends ArrayAccess, Traversable {
    // {{{ hasSession
    /**
     * Check if a given request contains a session
     * 
     * @param qcREST_Interface_Request $Request
     * 
     * @access public
     * @return bool
     **/
    public static function hasSession (qcREST_Interface_Request $Request);
    // }}}
    
    // {{{ __construct
    /**
     * Open/Create a session for a given request
     * 
     * @param qcREST_Interface_Request $Request
     * 
     * @access friendly
     * @return void
     **/
    function __construct (qcREST_Interface_Request $Request);
    // }}}
    
    // {{{ getID
    /**
     * Retrive the ID of this session
     * 
     * @access public
     * @return string
     **/
    public function getID ();
    // }}}
    
    // {{{ addToResponse
    /**
     * Register this session on a REST-Response
     * 
     * @param qcREST_Interface_Response $Response
     * 
     * @access public
     * @return void
     **/
    public function addToResponse (qcREST_Interface_Response $Response);
    // }}}
    
    // {{{ load
    /**
     * Load this session from its storage
     * 
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * @access public
     * @return void
     **/
    public function load (callable $Callback, $Private = null);
    // }}}
    
    // {{{ store
    /**
     * Store this session anywhere
     * 
     * @param callable $Callback (optional)
     * @param mixed $Private (optional)
     * 
     * @access public
     * @return void
     **/
    public function store (callable $Callback = null, $Private = null);
    // }}}
  }

?>