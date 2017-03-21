<?PHP

  /**
   * qcREST - Authenticator Interface
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
  
  interface qcREST_Interface_Authenticator {
    // {{{ authenticateRequest
    /**
     * Try to authenticate a given request
     * 
     * @param qcREST_Interface_Request $Request
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * The callback will be raised in the form of
     *   
     *   function (qcREST_Interface_Authenticator $Self, qcREST_Interface_Request $Request, bool $Status, qcEntity_Card $Entity = null, mixed $private = null) { }
     * 
     * $Status indicated wheter the request should be processed or not - if unsure this should be NULL,
     * $User may contain an user-entity that was identified for the request
     *    
     * @access private
     * @return void
     **/
    public function authenticateRequest (qcREST_Interface_Request $Request, callable $Callback, $Private = null);
    // }}}
    
    // {{{ getSchemes
    /**
     * Retrive a list of supported authentication-schemes.
     * The list is represented by an array of associative arrays, each with the following keys:
     * 
     *   scheme: A well known name of the scheme
     *   realm:  A realm for the scheme
     * 
     * @access public
     * @return array
     **/
    public function getSchemes ();
    // }}}
  }

?>