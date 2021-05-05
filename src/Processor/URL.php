<?php

  /**
   * qcREST - URL-encoded Input/Output-Processor
   * Copyright (C) 2016-2021 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  declare (strict_types=1);

  namespace quarxConnect\REST\Processor;
  use \quarxConnect\REST\ABI;
  use \quarxConnect\REST;
  use \quarxConnect\Events;
  
  class URL implements ABI\Processor {
    private static $mimeTypes = [
      'application/x-www-form-urlencoded',
      'multipart/form-data',
    ];
    
    public function getSupportedContentTypes () : array {
      return self::$mimeTypes;
    }
    
    // {{{ processInput
    /**
     * Process input-data
     * 
     * @param string $inputData
     * @param string $contentType
     * @param ABI\Request $fromRequest (optional)
     * 
     * @access public
     * @return ABI\Representation
     **/
    public function processInput (string $inputData, string $contentType, ABI\Request $fromRequest = null) : ABI\Representation {
      // Create an empty representaiton
      $resultRepresentation = new REST\Representation ();
      
      // Check type of input
      if ($contentType == 'application/x-www-form-urlencoded') {
        foreach (explode ('&', $inputData) as $inputParameter)
          if (($p = strpos ($inputParameter, '=')) !== false)
            $resultRepresentation [urldecode (substr ($inputParameter, 0, $p))] = urldecode (substr ($inputParameter, $p + 1));
          else
            $resultRepresentation [urldecode ($inputParameter)] = true;
      } elseif ($contentType == 'multipart/form-data') {
        // Detect boundary
        if ((substr ($inputData, 0, 2) != '--') || (($boundaryLength = strpos ($inputData, "\r\n", 2)) === false))
          throw new \Exception ('Invalid encoded form-data');
        
        $inputBoundary = substr ($inputData, 0, $boundaryLength);
        
        // Parse the data
        $inputPosition = $boundaryLength + 2;
        $inputLength = strlen ($inputData);
        $inputParameters = null;
        
        while ($inputPosition < $inputLength) {
          // Read the headers
          while ($inputPosition < $inputLength) {
            // Find the end of the current line
            if (($lineEnd = strpos ($inputData, "\r\n", $inputPosition)) === false)
              throw new \Exception ('Invalid encoded form-data');
            
            // Check if headers are finished
            if ($lineEnd == $inputPosition) {
              $inputPosition += 2;
              
              break;
            }
            
            // Peek the line and move forward
            $inputHeader = substr ($inputData, $inputPosition, $lineEnd - $inputPosition);
            $inputPosition = $lineEnd + 2;
            
            // Check for content-disposition
            if (strcasecmp ('Content-Disposition:', substr ($inputHeader, 0, 20)) != 0)
              continue;
            
            if (($lineEnd = strpos ($inputHeader, ';', 20)) === false)
              continue;
            
            $inputParameters = REST\Controller::httpHeaderParameters (trim (substr ($inputHeader, $lineEnd + 1)));
          }
          
          // Find end of the block
          if (($lineEnd = strpos ($inputData, "\r\n" . $inputBoundary, $inputPosition)) === false)
            throw new \Exception ('Invalid encoded form-data');
          
          // Read the payload
          if ($inputParameters && isset ($inputParameters ['name']))
            $resultRepresentation [$inputParameters ['name']] = substr ($inputData, $inputPosition, $lineEnd - $inputPosition);
          
          // Move to next block
          $inputPosition = $lineEnd + $bounaryLength + 4;
          
          if (($inputNext = substr ($inputData, $inputPosition - 2, 2)) == '--')
            break;
          elseif ($inputNext != "\r\n")
            throw new \Exception ('Invalid encoded form-data');
        }
      } else
        throw new \Exception ('Unsupported Content-Type');
      
      return $resultRepresentation;
    }  
    // }}}
    
    // {{{ processOutput
    /**
     * Process output-data
     * 
     * @param ABI\Resource $outputResource (optional)
     * @param ABI\Representation $outputRepresentation
     * @param ABI\Request $forRequest (optional)   
     * @param ABI\Controller $viaController (optional)
     * 
     * @access public
     * @return Events\Promise
     **/
    public function processOutput (
      ABI\Resource $outputResource = null,
      ABI\Representation $outputRepresentation,
      ABI\Request $forRequest = null,
      ABI\Controller $viaController = null
    ) : Events\Promise {
      return Events\Promise::reject ('Unsupported');
    }
    // }}}
  }
