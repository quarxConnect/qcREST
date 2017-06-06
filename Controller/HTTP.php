<?PHP

  /**
   * qcREST - Methods for HTTP-Based Controllers
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
  
  require_once ('qcREST/Controller.php');
  require_once ('qcREST/Interface/Response.php');
  
  abstract class qcREST_Controller_HTTP extends qcREST_Controller {
    /* Timeout for CORS-Informations */
    private $corsTimeout = 86400;
    
    /* List of allowed origins */
    private $corsOrigins = array ();
    
    // {{{ explodeURI
    /**
     * Split up URI and Parameters
     * 
     * @param string $URI
     * 
     * @access protected
     * @return array
     **/
    protected function explodeURI ($URI) {
      // Check wheter to do anything
      if (($p = strpos ($URI, '?')) === false)
        return array ($URI, array ());
      
      // Parse parameters
      $Parameters = array ();
      
      foreach (explode ('&', substr ($URI, $p + 1)) as $Parameter)
        if (($pv = strpos ($Parameter, '=')) !== false)
          $Parameters [urldecode (substr ($Parameter, 0, $pv))] = urldecode (substr ($Parameter, $pv + 1));
        else
          $Parameters [urldecode ($Parameter)] = true;
      
      // Strip parameters off URI
      $URI = substr ($URI, 0, $p);
      
      // Return the result
      return array ($URI, $Parameters);
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
    protected function explodeAcceptHeader ($Value) {
      // Prepare variables
      $Types = array ();
      $Preferences = array ();
      
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
            $Preferences [$Preference] = array ($Mime);
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
    public function getStatusCodeDescription ($Code) {
      // Description-Mapping
      static $codeMap = array (
        qcREST_Interface_Response::STATUS_OK => 'Okay',
        qcREST_Interface_Response::STATUS_CREATED => 'Resource was created',
        qcREST_Interface_Response::STATUS_NOT_FOUND => 'Resource could not be found',
        qcREST_Interface_Response::STATUS_NOT_ALLOWED => 'Operation is not allowed',
        qcREST_Interface_Response::STATUS_CLIENT_ERROR => 'There was an error with your request',
        qcREST_Interface_Response::STATUS_FORMAT_UNSUPPORTED => 'Unsupported Input-Format',
        qcREST_Interface_Response::STATUS_FORMAT_REJECTED => 'Input-Format was rejected by the resource',
        qcREST_Interface_Response::STATUS_NO_FORMAT => 'No processor for the requested output-format was found',
        # qcREST_Interface_Response::STATUS_UNNAMED_CHILD_ERROR => '',
        qcREST_Interface_Response::STATUS_CLIENT_UNAUTHENTICATED => 'You need to authenticate',
        qcREST_Interface_Response::STATUS_CLIENT_UNAUTHORIZED => 'You are not authorized to access this resource',
        qcREST_Interface_Response::STATUS_UNSUPPORTED => 'Operation is not supported',
        qcREST_Interface_Response::STATUS_ERROR => 'An internal error happened',
      );
      
      if (isset ($codeMap [$Code]))
        return $codeMap [$Code];
    }
    // }}}
    
    // {{{ getMeta
    /**
     * Retrive meta-data from this controller
     * 
     * @param qcREST_Interface_Response $Response
     * 
     * @access protected
     * @return array
     **/
    protected function getMeta (qcREST_Interface_Response $Response) {
      $Headers = array (
        'Access-Control-Allow-Method' => 'GET, POST, PUT, PATCH, DELETE', # HEAD / OPTIONS not implemented on controller
        'Access-Control-Allow-Credentials' => 'true',
        'Vary' => array (),
      );
      
      // Append Timeout
      if ($this->corsTimeout > 0)
        $Headers ['Access-Control-Max-Age'] = $this->corsTimeout;
      
      // Append allowed origins
      if (count ($this->corsOrigins) > 0) {
        // Retrive the initial request
        $Request = $Response->getRequest ();
        
        // Look for an origin-header on the request
        if (!($Origin = $Request->getMeta ('Origin')) || !in_array (strtolower ($Origin), $this->corsOrigins))
          foreach ($this->corsOrigins as $Origin)
            break;
        
        // Append header
        $Headers ['Access-Control-Allow-Origin'] = $Origin;
        $Headers ['Vary'][] = 'Origin';
      } else
        $Headers ['Access-Control-Allow-Origin'] = '*';
      
      // Check if anything varys
      if (count ($Headers ['Vary']) < 1)
        unset ($Headers ['Vary']);
      
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
    public function setCORSTimeout ($Timeout) {
      $this->corsTimeout = (int)$Timeout;
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
    public function addCORSOrigin ($Origin) {
      // Try to parse the URL
      if (!($Origin = parse_url ($Origin)) || !isset ($Origin ['scheme']) || !isset ($Origin ['host']))
        return trigger_error ('Invalid origin');
      
      // Append to our list
      $this->corsOrigins [] = strtolower ($Origin ['scheme'] . '://' . $Origin ['host']) . (isset ($Origin ['port']) ? ':' . $Origin ['port'] : '');
    }
    // }}}
  }

?>