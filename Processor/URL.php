<?PHP

  /**
   * qcREST - URL-encoded Input/Output-Processor
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
  
  class qcREST_Processor_URL implements qcREST_Interface_Processor {
    private static $Types = array (
      'application/x-www-form-urlencoded',
    );
    
    public function getSupportedContentTypes () {
      return self::$Types;
    }
    
    // {{{ processInput
    /**
     * Process input-data
     * 
     * @param string $Data
     * 
     * @access public
     * @return qcREST_Interface_Representation
     **/
    public function processInput ($Data) {
      $Result = new qcREST_Representation;
      
      foreach (explode ('&', $Data) as $Parameter)
        if (($p = strpos ($Parameter, '=')) !== false)
          $Result [urldecode (substr ($Parameter, 0, $p))] = urldecode (substr ($Parameter, $p + 1));
        else
          $Result [urldecode ($Parameter)] = true;
      
      return $Result;
    }  
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
     *   function (qcREST_Interface_Processor $Self, string $Output, string $OutputType, qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $Representation, qcREST_Interface_Request $Request = null, qcREST_Controller $Controller = null) { }
     * 
     * @access public
     * @return bool  
     **/
    public function processOutput (callable $Callback, $Private = null, qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $Representation, qcREST_Interface_Request $Request = null, qcREST_Interface_Controller $Controller = null) {
      call_user_func ($Callback, $this, null, null, $Resource, $Representation, $Request, $Controller, $Private);
      
      return false;
    }
    // }}}
  }

?>