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
     * 
     * @access private
     * @return qcEvents_Promise
     **/
    public function authorizeRequest (qcREST_Interface_Request $Request, qcREST_Interface_Resource $Resource = null, qcREST_Interface_Collection $Collection = null) : qcEvents_Promise;
    // }}}
    
    // {{{ getAuthorizedMethods
    /**
     * Request the authorized methods for a given resource and/or collection
     * 
     * @param qcREST_Interface_Resource $Resource A resource this request is for or that is hosting the collection
     * @param qcREST_Interface_Collection $Collection (optional) The collection this request is for (if NULL, the request is regarding the resource)
     * @param qcREST_Interface_Request $Request (optional) A REST-Request assigned with this one
     * 
     * @access public
     * @return qcEvents_Promise A promise that resolves into an array of Methods once fullfulled
     **/
    public function getAuthorizedMethods (qcREST_Interface_Resource $Resource, qcREST_Interface_Collection $Collection = null, qcREST_Interface_Request $Request = null) : qcEvents_Promise;
    // }}}
  }

?>