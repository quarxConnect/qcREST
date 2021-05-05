<?php

  /**
   * qcREST - Basic Session-Handler (using PHP's own session-functions)
   * Copyright (C) 2018-2021 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  namespace quarxConnect\REST;
  use \quarxConnect\Events;
  
  class Session implements \IteratorAggregate, ABI\Session {
    /* ID of this session */
    private $sessionID = null;
    
    /* All values on this session */
    private $sessionValues = [ ];
    
    // {{{ hasSession
    /**
     * Check if a given request contains a session
     * 
     * @param ABI\Request $forRequest
     * 
     * @access public
     * @return bool
     **/
    public static function hasSession (ABI\Request $forRequest) : bool {
      return (self::getSessionID ($forRequest, false) !== null);
    }
    // }}}
    
    // {{{ getSessionID
    /**
     * Retrive a session-id from/for a request
     * 
     * @param ABI\Request $forRequest
     * @param bool $createIfMissing (optional)
     * 
     * @access public
     * @return string
     **/
    private static function getSessionID (ABI\Request $forRequest, bool $createIfMissing = false) : ?string {
      // Retrive the key for the session
      $sessionKey = ini_get ('session.name');  
      
      if (strlen ($sessionKey) < 1)
        $sessionKey = 'PHPSESSID'; 
      
      // Check if there is a session-id already set on the request
      $sessionID = null;
      
      if ($requestCookies = $forRequest->getMeta ('Cookie')) {
        // Split the list into key-values
        $requestCookies = explode ('; ', $requestCookies);
        
        // Search for the session-id
        foreach ($requestCookies as $requestCookie) {
          // Check for delimiter
          if (($p = strpos ($requestCookie, '=')) === false)
            continue;
          
          // Check the key
          if (substr ($requestCookie, 0, $p) != $sessionKey)
            continue;
          
          // Extract the ID
          $sessionID = substr ($requestCookie, $p + 1);
          
          if (strlen ($sessionID) == 0)
            $sessionID = null;
          elseif ($sessionID [0] == '"')
            $sessionID = urldecode (substr ($sessionID, 1, -1));
          else
            $sessionID = urldecode ($sessionID);
          
          break;
        }
      }
      
      if (($sessionID === null) && !($sessionID = $forRequest->getParameter ($sessionKey)) && $createIfMissing) {
        // Check wheter to close an active session
        if (strlen (session_id ()) > 0)
          session_write_close ();
        
        // Generate a new session
        session_start ();
        $sessionID = session_id ();
      }
      
      return $sessionID;
    }
    // }}}
    
    // {{{ encode
    /**
     * Encode a cookie-value
     * 
     * @param string $sessionValue
     * 
     * @access private
     * @return string
     **/
    private static function encode (string $sessionValue) : string {
      $outputValue = '';
      $valueLength = strlen ($sessionValue);
      
      for ($p = 0; $p < $valueLength; $p++) {
        $c = ord ($sessionValue [$p]);
        
        if (($c < 0x21) || ($c == 0x22) || ($c == 0x3B) || ($c == 0x5C) || ($c > 0x7E))
          $outputValue .= sprintf ('%%%02X', $c);
        else
          $outputValue .= $sessionValue [$p];
      }
      
      return $outputValue;
    }
    // }}}
    
    // {{{ __construct
    /**
     * Open/Create a session for a given request
     * 
     * @param ABI\Request $forRequest
     * 
     * @access friendly
     * @return void
     **/
    function __construct (ABI\Request $forRequest) {
      $this->sessionID = self::getSessionID ($forRequest, true);
    }
    // }}}
    
    // {{{ getID
    /**
     * Retrive the ID of this session
     * 
     * @access public
     * @return string
     **/
    public function getID () : string {
      return $this->sessionID;
    }
    // }}}
    
    // {{{ getIterator
    /**
     * Retrieve an external iterator for this session
     * 
     * @access public
     * @return ArrayIterator
     **/
    public function getIterator () : \ArrayIterator {
      return new \ArrayIterator ($this->sessionValues);
    }
    // }}}
    
    // {{{ addToResponse
    /**
     * Register this session on a REST-Response
     * 
     * @param ABI\Response $theResponse
     * 
     * @access public
     * @return void
     **/
    public function addToResponse (ABI\Response $theResponse) : void {
      // Check wheter to do anything
      if (self::getSessionID ($theResponse->getRequest ()) == $this->sessionID)
        return;
      
      // Retrive the key for the session
      $sessionKey = ini_get ('session.name');  
      
      if (strlen ($sessionKey) < 1)
        $sessionKey = 'PHPSESSID';
      
      // Retrive parameters for cookie
      $sessionParameters = session_get_cookie_params ();
      
      // Create Cookie-Header
      $sessionCookie = self::encode ($sessionKey) . '=' . self::encode ($this->sessionID);
      
      if ($sessionParameters ['lifetime'])
        $sessionCookie .= '; Max-Age=' . (int)$sessionParameters ['lifetime'];
      
      if ($sessionParameters ['path'])
        $sessionCookie .= '; Path=' . self::encode ($sessionParameters ['path']);
      
      if ($sessionParameters ['domain'])
        $sessionCookie .= '; Domain=' . self::encode ($sessionParameters ['domain']);
      
      if ($sessionParameters ['secure'])
        $sessionCookie .= '; Secure';
      
      if ($sessionParameters ['httponly'])
        $sessionCookie .= '; HttpOnly';
      
      // Store on the response
      if ($responseCookies = $theResponse->getMeta ('Set-Cookie')) {
        if (is_array ($responseCookies))
          $responseCookies [] = $sessionCookie;
        else
          $responseCookies = [ $responseCookies, $sessionCookie ];
        
        $theResponse->setMeta ('Set-Cookie', $responseCookies);
      } else
        $theResponse->setMeta ('Set-Cookie', $sessionCookie);
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
      $sessionID = session_id ();
      
      // Check wheter to change the current session
      if ($sessionID == $this->sessionID)
        return;
      
      // Check wheter to close an active session
      if (strlen ($sessionID) > 0)
        session_write_close ();
      
      // Start our session   
      session_id ($this->sessionID);
      session_start ();
    }
    // }}}
    
    // {{{ load
    /**
     * Load this session from its storage
     * 
     * @access public
     * @return Events\Promise
     **/
    public function load () : Events\Promise {
      // Make sure our session is running
      $this->switchSession ();
       
      // Copy session-values
      $this->sessionValues = $_SESSION;
      
      // Run the callback
      return Events\Promise::resolve ();
    }
    // }}}
    
    // {{{ store
    /**
     * Store this session anywhere
     * 
     * @access public
     * @return Events\Promise
     **/
    public function store () : Events\Promise {
      // Make sure our session is running
      $this->switchSession ();
       
      // Copy session-values 
      $_SESSION = $this->sessionValues;
      
      // Store the session-values
      /* session_commit() is actual an alias of session_write_close(),
         but we don't want to close the session here so we use this alias */
      session_commit ();
       
      // Run the callback
      return Events\Promise::resolve ();
    }
    // }}}
    
    // {{{ offsetExists
    /**
     * Check if a key exists on this session
     * 
     * @param string $attributeName
     * 
     * @access public
     * @return bool
     **/
    public function offsetExists ($attributeName) : bool {
      return isset ($this->sessionValues [$attributeName]);
    }
    // }}}
    
    // {{{ offsetGet
    /**
     * Retrive a value from this session by key
     * 
     * @param string $attributeName
     * 
     * @access public
     * @return mixed
     **/
    public function offsetGet ($attributeName) {
      return $this->sessionValues [$attributeName] ?? null;
    }
    // }}}
    
    // {{{ offsetSet
    /**
     * Set the value of a session-value
     * 
     * @param string $attributeName
     * @param mixed $attributeValue
     * 
     * @access public
     * @return void
     **/
    public function offsetSet ($attribteName, $attributeValue) : void {
      if ($attribute === null)
        $this->sessionValues [] = $attributeValue;
      else
        $this->sessionValues [$attributeName] = $attributeValue;
    }
    // }}}
    
    // {{{ offsetUnset
    /**
     * Remove a value from this session
     * 
     * @param string $attributeName
     * 
     * @access public
     * @return void
     **/
    public function offsetUnset ($attributeName) : void {
      unset ($this->sessionValues [$attributeName]);
    }
    // }}}
  }
