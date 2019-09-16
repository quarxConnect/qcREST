<?PHP

  /**
   * qcREST - JSON Input/Output-Processor
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
  
  require_once ('qcREST/Interface/Processor.php');
  require_once ('qcREST/Representation.php');
  require_once ('qcREST/Response.php');
  require_once ('qcEvents/Promise.php');
  
  class qcREST_Processor_JSON implements qcREST_Interface_Processor {
    private static $Types = array (
      'application/json',
      'text/json',
    );
    
    public function getSupportedContentTypes () {
      return self::$Types;  
    }
    
    // {{{ processInput
    /**
     * Process input-data
     * 
     * @param string $Data
     * @param string $Type
     * @param qcREST_Interface_Request $Request (optional)
     * 
     * @access public
     * @return array
     **/
    public function processInput ($Data, $Type, qcREST_Interface_Request $Request = null) {
      // Try to convert JSON-Data
      $Data = json_decode ($Data);
      
      // Convert object into array
      // REMARK: We do this using get_object_vars as a direct cast will mess things up here
      if (is_object ($Data))
        $Data = get_object_vars ($Data);
      
      // Make sure it's an array
      elseif (!is_array ($Data))
        return false;
      
      // Return a new representation
      return new qcREST_Representation ($Data);
    }
    // }}}
    
    // {{{ processOutput
    /**
     * Process output-data
     * 
     * @param qcREST_Interface_Resource $Resource (optional)
     * @param qcREST_Interface_Representation $Representation
     * @param qcREST_Interface_Request $Request (optional)
     * @param qcREST_Interface_Controller $Controller (optional)
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function processOutput (qcREST_Interface_Resource $Resource = null, qcREST_Interface_Representation $Representation, qcREST_Interface_Request $Request = null, qcREST_Interface_Controller $Controller = null) : qcEvents_Promise {
      return qcEvents_Promise::resolve (
        new qcREST_Response ($Request, qcREST_Interface_Response::STATUS_OK, json_encode ((object)$Representation->toArray ()), 'application/json')
      );
    }
    // }}}
  }

?>