<?php

  /**
   * qcREST - JSON Input/Output-Processor
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
  
  class JSON implements ABI\Processor {
    private static $mimeTypes = [
      'application/json',
      'text/json',
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
      // Try to convert JSON-Data
      $inputData = json_decode ($inputData);
      
      // Convert object into array
      // REMARK: We do this using get_object_vars as a direct cast will mess things up here
      if (is_object ($inputData))
        $inputData = get_object_vars ($inputData);
      
      // Make sure it's an array
      elseif (!is_array ($inputData))
        throw new \Exception ('JSON-Object or -array expected as input');
      
      // Return a new representation
      return new REST\Representation ($inputData);
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
      return Events\Promise::resolve (
        new REST\Response (
          $forRequest,
          ABI\Response::STATUS_OK,
          json_encode ((object)$outputRepresentation->toArray ()),
          'application/json'
        )
      );
    }
    // }}}
  }
