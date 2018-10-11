<?PHP

  /**
   * qcREST - Basic Session-Handler (using PHP's own session-functions)
   * Copyright (C) 2018 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  require_once ('qcREST/Interface/Session.php');
  
  class qcREST_Session implements IteratorAggregate, qcREST_Interface_Session {
    /* ID of this session */
    private $ID = null;
    
    /* All values on this session */
    private $Values = array ();
    
    // {{{ hasSession
    /**
     * Check if a given request contains a session
     * 
     * @param qcREST_Interface_Request $Request
     * 
     * @access public
     * @return bool
     **/
    public static function hasSession (qcREST_Interface_Request $Request) {
      return self::getSessionID ($Request, false);
    }
    // }}}
    
    // {{{ getSessionID
    /**
     * Retrive a session-id from/for a request
     * 
     * @param qcREST_Interface_Request $Request
     * @param bool $Create (optional)
     * 
     * @access public
     * @return string
     **/
    private static function getSessionID (qcREST_Interface_Request $Request, $Create = false) {
      // Retrive the key for the session
      $Key = ini_get ('session.name');  
      
      if (strlen ($Key) < 1)
        $Key = 'PHPSESSID'; 
      
      // Check if there is a session-id already set on the request
      $ID = null;
      
      if ($Cookies = $Request->getMeta ('Cookie')) {
        // Split the list into key-values
        $Cookies = explode ('; ', $Cookies);
        
        // Search for the session-id
        foreach ($Cookies as $Cookie) {
          // Check for delimiter
          if (($p = strpos ($Cookie, '=')) === false)
            continue;
          
          // Check the key
          if (substr ($Cookie, 0, $p) != $Key)
            continue;
          
          // Extract the ID
          $ID = substr ($Cookie, $p + 1);
          
          if (strlen ($ID) == 0)
            $ID = null;
          elseif ($ID [0] == '"')
            $ID = urldecode (substr ($ID, 1, -1));
          else
            $ID = urldecode ($ID);
          
          break;
        }
      }
      
      if (($ID === null) && !($ID = $Request->getParameter ($Key)) && $Create) {
        // Check wheter to close an active session
        if (strlen (session_id ()) > 0)
          session_write_close ();
        
        // Generate a new session
        session_start ();
        $ID = session_id ();
      }
      
      return $ID;
    }
    // }}}
    
    // {{{ encode
    /**
     * Encode a cookie-value
     * 
     * @param string $Value
     * 
     * @access private
     * @return string
     **/
    private static function encode ($Value) {
      $Output = '';
      $Length = strlen ($Value);
      
      for ($p = 0; $p < $Length; $p++) {
        $c = ord ($Value [$p]);
        
        if (($c < 0x21) || ($c == 0x22) || ($c == 0x3B) || ($c == 0x5C) || ($c > 0x7E))
          $Output .= sprintf ('%%%02X', $c);
        else
          $Output .= $Value [$p];
      }
      
      return $Output;
    }
    // }}}
    
    // {{{ __construct
    /**
     * Open/Create a session for a given request
     * 
     * @param qcREST_Interface_Request $Request
     * 
     * @access friendly
     * @return void
     **/
    function __construct (qcREST_Interface_Request $Request) {
      $this->ID = self::getSessionID ($Request, true);
    }
    // }}}
    
    // {{{ getID
    /**
     * Retrive the ID of this session
     * 
     * @access public
     * @return string
     **/
    public function getID () {
      return $this->ID;
    }
    // }}}
    
    // {{{ getIterator
    /**
     * Retrieve an external iterator for this session
     * 
     * @access public
     * @return ArrayIterator
     **/
    public function getIterator () {
      return new ArrayIterator ($this->Values);
    }
    // }}}
    
    // {{{ addToResponse
    /**
     * Register this session on a REST-Response
     * 
     * @param qcREST_Interface_Response $Response
     * 
     * @access public
     * @return void
     **/
    public function addToResponse (qcREST_Interface_Response $Response) {
      // Check wheter to do anything
      if (self::getSessionID ($Response->getRequest ()) == $this->ID)
        return;
      
      // Retrive the key for the session
      $Key = ini_get ('session.name');  
      
      if (strlen ($Key) < 1)
        $Key = 'PHPSESSID';
      
      // Retrive parameters for cookie
      $Parameters = session_get_cookie_params ();
      
      // Create Cookie-Header
      $Cookie = self::encode ($Key) . '=' . self::encode ($this->ID);
      
      if ($Parameters ['lifetime'])
        $Cookie .= '; Max-Age=' . (int)$Parameters ['lifetime'];
      
      if ($Parameters ['path'])
        $Cookie .= '; Path=' . self::encode ($Parameters ['path']);
      
      if ($Parameters ['domain'])
        $Cookie .= '; Domain=' . self::encode ($Parameters ['domain']);
      
      if ($Parameters ['secure'])
        $Cookie .= '; Secure';
      
      if ($Parameters ['httponly'])
        $Cookie .= '; HttpOnly';
      
      // Store on the response
      if ($Cookies = $Response->getMeta ('Set-Cookie')) {
        if (is_array ($Cookies))
          $Cookies [] = $Cookie;
        else
          $Cookies = array ($Cookies, $Cookie);
        
        $Response->setMeta ('Set-Cookie', $Cookies);
      } else
        $Response->setMeta ('Set-Cookie', $Cookie);
    }
    // }}}
    
    // {{{ switchSession
    /**
     * Make sure our own session is active
     * 
     * @access private
     * @return void
     **/
    private function switchSession () {
      // Try to get the current session-id
      $ID = session_id ();
      
      // Check wheter to change the current session
      if ($ID == $this->ID)
        return;
      
      // Check wheter to close an active session
      if (strlen ($ID) > 0)
        session_write_close ();
      
      // Start our session   
      session_id ($this->ID);
      session_start ();
    }
    // }}}
    
    // {{{ load
    /**
     * Load this session from its storage
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function load () : qcEvents_Promise {
      // Make sure our session is running
      $this->switchSession ();
       
      // Copy session-values
      $this->Values = $_SESSION;
      
      // Run the callback
      return qcEvents_Promise::resolve ();
    }
    // }}}
    
    // {{{ store
    /**
     * Store this session anywhere
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function store () : qcEvents_Promise {
      // Make sure our session is running
      $this->switchSession ();
       
      // Copy session-values 
      $_SESSION = $this->Values;
      
      // Store the session-values
      /* session_commit() is actual an alias of session_write_close(),
         but we don't want to close the session here so we use this alias */
      session_commit ();
       
      // Run the callback
      return qcEvents_Promise::resolve ();
    }
    // }}}
    
    // {{{ offsetExists
    /**
     * Check if a key exists on this session
     * 
     * @param string $Key
     * 
     * @access public
     * @return bool
     **/
    public function offsetExists ($Key) {
      return isset ($this->Values [$Key]);
    }
    // }}}
    
    // {{{ offsetGet
    /**
     * Retrive a value from this session by key
     * 
     * @param string $Key
     * 
     * @access public
     * @return mixed
     **/
    public function offsetGet ($Key) {
      if (isset ($this->Values [$Key]))
        return $this->Values [$Key];
    }
    // }}}
    
    // {{{ offsetSet
    /**
     * Set the value of a session-value
     * 
     * @param string $Key
     * @param mixed $Value
     * 
     * @access public
     * @return void
     **/
    public function offsetSet ($Key, $Value) {
      if ($Key === null)
        $this->Values [] = $Value;
      else
        $this->Values [$Key] = $Value;
    }
    // }}}
    
    // {{{ offsetUnset
    /**
     * Remove a value from this session
     * 
     * @param string $Key
     * 
     * @access public
     * @return void
     **/
    public function offsetUnset ($Key) {
      unset ($this->Values [$Key]);
    }
    // }}}
  }

?>