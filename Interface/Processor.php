<?PHP

  /**
   * qcREST - Input/Output Processor Interface
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
  
  interface qcREST_Interface_Processor {
    // {{{ getSupportedContentTypes
    /**
     * Retrive a set of MIME-Types supported by this processor
     * 
     * @access public
     * @return array
     **/
    public function getSupportedContentTypes ();
    // }}}
    
    // {{{ processInput
    /**
     * Process input-data
     * 
     * @param string $Data
     * @param string $Type
     * @param qcREST_Interface_Request $Request (optional)
     * 
     * @access public
     * @return qcREST_Interface_Representation
     **/
    public function processInput ($Data, $Type, qcREST_Interface_Request $Request = null);
    // }}}
    
    // {{{ processOutput
    /**
     * Process output-data
     * 
     * @param callable $Callback
     * @param mixed $Private (optional)
     * @param qcREST_Interface_Resource $Resource
     * @param qcREST_Interface_Representation $Representation
     * @param qcREST_Interface_Request $Request (optional)
     * @param qcREST_Interface_Controller $Controller (optional)
     * 
     * The callback will be raised in the form of
     * 
     *   function (qcREST_Interface_Processor $Self, string $Output, string $OutputType, qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $Representation, qcREST_Interface_Request $Request = null, qcREST_Interface_Controller $Controller = null, mixed $Private = null) { }
     * 
     * @access public
     * @return bool
     **/
    public function processOutput (callable $Callback, $Private = null, qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $Representation, qcREST_Interface_Request $Request = null, qcREST_Interface_Controller $Controller = null);
    // }}}
  }

?>