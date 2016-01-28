<?PHP

  /**
   * qcREST - Special Resource Representation on Collections Interface
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
  
  interface qcREST_Interface_Collection_Representation {
    // {{{ getCollectionRepresentation
    /**
     * Retrive an additional representation from this resource to be included in collection-output
     * 
     * @param $Callback A callback to fire once the operation was completed
     * @param mixed $Private (optional) Some private data to pass to the callback
     * 
     * The callback will be raised once the operation was completed in the form of:
     * 
     *   function (qcREST_Interface_Resource $Self, qcREST_Interface_Representation $Representation = null, mixed $Private) { }
     * 
     * @access public
     * @return void
     **/
    public function getCollectionRepresentation (callable $Callback, $Private = null);
    // }}}
  }

?>