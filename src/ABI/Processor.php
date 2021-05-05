<?php

  /**
   * qcREST - Input/Output Processor Interface
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

  namespace quarxConnect\REST\ABI;
  use \quarxConnect\Events;
  
  interface Processor {
    // {{{ getSupportedContentTypes
    /**
     * Retrive a set of MIME-Types supported by this processor
     * 
     * @access public
     * @return array
     **/
    public function getSupportedContentTypes () : array;
    // }}}
    
    // {{{ processInput
    /**
     * Process input-data
     * 
     * @param string $inputData
     * @param string $contentType
     * @param Request $fromRequest (optional)
     * 
     * @access public
     * @return Representation
     **/
    public function processInput (string $inputData, string $contentType, Request $fromRequest = null) : Representation;
    // }}}
    
    // {{{ processOutput
    /**
     * Process output-data
     * 
     * @param Resource $outputResource (optional)
     * @param Representation $outputRepresentation
     * @param Request $forRequest (optional)
     * @param Controller $viaController (optional)
     * 
     * @access public
     * @return Events\Promise
     **/
    public function processOutput (Resource $outputResource = null, Representation $outputRepresentation, Request $forRequest = null, Controller $viaController = null) : Events\Promise;
    // }}}
  }
