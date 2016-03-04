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
      'multipart/form-data',
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
     * @return qcREST_Interface_Representation
     **/
    public function processInput ($Data, $Type, qcREST_Interface_Request $Request = null) {
      // Create an empty representaiton
      $Result = new qcREST_Representation;
      
      // Check type of input
      if ($Type == 'application/x-www-form-urlencoded') {
        foreach (explode ('&', $Data) as $Parameter)
          if (($p = strpos ($Parameter, '=')) !== false)
            $Result [urldecode (substr ($Parameter, 0, $p))] = urldecode (substr ($Parameter, $p + 1));
          else
            $Result [urldecode ($Parameter)] = true;
      } elseif ($Type == 'multipart/form-data') {
        // Detect boundary
        if ((substr ($Data, 0, 2) != '--') || (($bLength = strpos ($Data, "\r\n", 2)) === false))
          return false;
        
        $Boundary = substr ($Data, 0, $bLength);
        
        // Parse the data
        $Pos = $bLength + 2;
        $Len = strlen ($Data);
        $Params = null;
        
        while ($Pos < $Len) {
          // Read the headers
          while ($Pos < $Len) {
            // Find the end of the current line
            if (($lEnd = strpos ($Data, "\r\n", $Pos)) === false)
              return false;
            
            // Check if headers are finished
            if ($lEnd == $Pos) {
              $Pos += 2;
              
              break;
            }
            
            // Peek the line and move forward
            $Header = substr ($Data, $Pos, $lEnd - $Pos);
            $Pos = $lEnd + 2;
            
            // Check for content-disposition
            if (strcasecmp ('Content-Disposition:', substr ($Header, 0, 20)) != 0)
              continue;
            
            if (($lEnd = strpos ($Header, ';', 20)) === false)
              continue;
            
            $Params = qcREST_Controller::httpHeaderParameters (trim (substr ($Header, $lEnd + 1)));
          }
          
          // Find end of the block
          if (($lEnd = strpos ($Data, "\r\n" . $Boundary, $Pos)) === false)
            return false;
          
          // Read the payload
          if ($Params && isset ($Params ['name']))
            $Representation [$Params ['name']] = $x = substr ($Data, $Pos, $lEnd - $Pos);
          
          // Move to next block
          $Pos = $lEnd + $bLength + 4;
          
          if (($Next = substr ($Data, $Pos - 2, 2)) == '--')
            break;
          elseif ($Next != "\r\n")
            return false;
        }
      } else
        return false;
      
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