<?PHP

  /**
   * qcREST - Authorizer Interface
   * Copyright (C) 2017 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  interface qcREST_Interface_Authorizer {
    // {{{ authorizeRequest
    /**
     * Try to authorize a given request
     * 
     * @param qcREST_Interface_Request $Request A request-object to authorize
     * @param qcREST_Interface_Resource $Resource (optional) Resource matching the request
     * @param qcREST_Interface_Collection (optional) Collection matching the request
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * The callback will be raised in the form of
     * 
     *   function (qcREST_Interface_Authorizer $Self, bool $Status, mixed $Private = null) { }
     * 
     * $Status indicated wheter the request should be processed or not - if unsure this should be NULL,
     * 
     * @access private
     * @return void
     **/
    public function authenticateRequest (qcREST_Interface_Request $Request, qcREST_Interface_Resource $Resource = null, qcREST_Interface_Collection $Collection = null, callable $Callback, $Private = null);
    // }}}
  }

?>