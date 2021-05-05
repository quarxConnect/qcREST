<?php

  /**
   * qcREST - Methods for HTTP-Based Controllers
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
  
  namespace quarxConnect\REST\Controller;
  use \quarxConnect\REST\ABI;
  use \quarxConnect\REST;
  
  abstract class HTTP extends REST\Controller {
    /* Timeout for CORS-Informations */
    private $corsTimeout = 86400;
    
    /* List of allowed origins */
    private $corsOrigins = [ ];
    
    // {{{ explodeURI
    /**
     * Split up URI and Parameters
     * 
     * @param string $URI
     * 
     * @access protected
     * @return array
     **/
    protected function explodeURI (string $URI) : array {
      // Check wheter to do anything
      if (($p = strpos ($URI, '?')) === false)
        return [ $URI, [ ] ];
      
      // Parse parameters
      $Parameters = [ ];
      
      foreach (explode ('&', substr ($URI, $p + 1)) as $Parameter)
        if (($pv = strpos ($Parameter, '=')) !== false)
          $Parameters [urldecode (substr ($Parameter, 0, $pv))] = urldecode (substr ($Parameter, $pv + 1));
        else
          $Parameters [urldecode ($Parameter)] = true;
      
      // Strip parameters off URI
      $URI = substr ($URI, 0, $p);
      
      // Return the result
      return [ $URI, $Parameters ];
    }
    // }}}
    
    // {{{ explodeAcceptHeader
    /**
     * Split up a given accept-header and return a structure for internal use on requests
     * 
     * @param string $Value
     * 
     * @access protected
     * @return array
     **/
    protected function explodeAcceptHeader (string $Value) : array {
      // Prepare variables
      $Types = [ ];
      $Preferences = [ ];
      
      // Parse the value
      if (strlen ($Value) > 0)
        foreach (explode (',', $Value) as $Pref) {
          // Split preference into parts
          $Data = explode (';', $Pref);
          
          // Grab the actual mime-type
          $Mime = array_shift ($Data); 
          
          // Lookup preference
          $Preference = 1.0;  
          
          foreach ($Data as $Param)
            if (substr ($Param, 0, 2) == 'q=')
              $Preference = floatval (substr ($Param, 2));
          
          $Preference = floor ($Preference * 100);
          
          // Append to preferences
          if (isset ($Preferences [$Preference]))
            $Preferences [$Preference][] = $Mime;
          else
            $Preferences [$Preference] = [ $Mime ];
        }
      
      // Sort by preferences
      krsort ($Preferences);
      
      // Append to result
      foreach ($Preferences as $Mimes)
        foreach ($Mimes as $Mime)
          $Types [] = $Mime;
      
      // Sanity-check
      if (count ($Types) == 0)
        $Types [] = '*/*';
      
      // Return the result
      return $Types;
    }
    // }}}
    
    // {{{ getStatusCodeDescription
    /**
     * Retrive a desciptive text for a status-code
     * 
     * @param int $Code
     * 
     * @access public
     * @return string
     **/
    public function getStatusCodeDescription (int $Code) : ?string {
      // Description-Mapping
      static $codeMap = [
        ABI\Response::STATUS_OK => 'Okay',
        ABI\Response::STATUS_CREATED => 'Resource was created',
        ABI\Response::STATUS_STORED => 'Operation was successfull',
        ABI\Response::STATUS_NOT_FOUND => 'Resource could not be found',
        ABI\Response::STATUS_NOT_ALLOWED => 'Operation is not allowed',
        ABI\Response::STATUS_CLIENT_ERROR => 'There was an error with your request',
        ABI\Response::STATUS_FORMAT_UNSUPPORTED => 'Unsupported Input-Format',
        ABI\Response::STATUS_FORMAT_REJECTED => 'Input-Format was rejected by the resource',
        ABI\Response::STATUS_NO_FORMAT => 'No processor for the requested output-format was found',
        # ABI\Response::STATUS_UNNAMED_CHILD_ERROR => '',
        ABI\Response::STATUS_CLIENT_UNAUTHENTICATED => 'You need to authenticate',
        ABI\Response::STATUS_CLIENT_UNAUTHORIZED => 'You are not authorized to access this resource',
        ABI\Response::STATUS_UNSUPPORTED => 'Operation is not supported',
        ABI\Response::STATUS_ERROR => 'An internal error happened',
      ];
      
      return $codeMap [$Code] ?? null;
    }
    // }}}
    
    // {{{ getMeta
    /**
     * Retrive meta-data from this controller
     * 
     * @param ABI\Response $Response
     * 
     * @access protected
     * @return array
     **/
    protected function getMeta (ABI\Response $Response) : array {
      $Headers = [
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS', # HEAD is not implemented on controller
        'Access-Control-Allow-Credentials' => 'true',
        'Vary' => [ 'Accept', 'Authorization' ],
      ];
      
      // Append Timeout
      if ($this->corsTimeout > 0)
        $Headers ['Access-Control-Max-Age'] = $this->corsTimeout;
      
      // Retrive the initial request
      $Request = $Response->getRequest ();
      
      // Append allowed origins
      $Origin = $Request->getMeta ('Origin');
      
      if ($Origin || (count ($this->corsOrigins) > 0)) {
        // Look for an origin-header on the request
        if (!$Origin || !in_array (strtolower ($Origin), $this->corsOrigins))
          foreach ($this->corsOrigins as $Origin)
            break;
        
        // Append header
        $Headers ['Access-Control-Allow-Origin'] = $Origin;
        $Headers ['Vary'][] = 'Origin';
      } else
        $Headers ['Access-Control-Allow-Origin'] = '*';
      
      // Echo back requested headers
      if ($Meta = $Request->getMeta ('Access-Control-Request-Headers'))
        $Headers ['Access-Control-Allow-Headers'] = $Meta;
      
      // Check if anything varys
      if (count ($Headers ['Vary']) < 1)
        unset ($Headers ['Vary']);
      else
        $Headers ['Vary'] = implode (', ', $Headers ['Vary']);
      
      // Return the headers
      return $Headers;
    }
    // }}}
    
    // {{{ setCORSTimeout
    /**
     * Set the timeout for CORS-Information
     * 
     * @param int $Timeout
     * 
     * @access public
     * @return void
     **/
    public function setCORSTimeout (int $Timeout) : void {
      $this->corsTimeout = $Timeout;
    }
    // }}}
    
    // {{{ addCORSOrigin
    /**
     * Append an Origin to the list of allowed origins
     * 
     * @param string $Origin
     * 
     * @access public
     * @return void
     **/
    public function addCORSOrigin (string $Origin) : void {
      // Try to parse the URL
      if (!($Origin = parse_url ($Origin)) || !isset ($Origin ['scheme']) || !isset ($Origin ['host']))
        throw new \Exception ('Invalid origin');
      
      // Append to our list
      $this->corsOrigins [] = strtolower ($Origin ['scheme'] . '://' . $Origin ['host']) . (isset ($Origin ['port']) ? ':' . $Origin ['port'] : '');
    }
    // }}}
  }
