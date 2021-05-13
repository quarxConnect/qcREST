<?php

  /**
   * qcREST - Simple Authenticator
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

  namespace quarxConnect\REST\Authenticator;
  use \quarxConnect\Entity;
  use \quarxConnect\Events;
  use \quarxConnect\REST;

  class Simple implements REST\ABI\Authenticator {
    /* Collection of registered users */
    private $registeredUsers = [ ];
    
    // {{{ addUser
    /**
     * Add a registered user
     * 
     * @param string $userName
     * @param string $userPassword
     * 
     * @access public
     + @return void
     **/
    public function addUser (string $userName, string $userPassword) : void {
      $this->registeredUsers [$userName] = $userPassword;
    }
    // }}}
    
    // {{{ authenticateRequest
    /**
     * Try to authenticate a given request
     * 
     * @param REST\ABI\Request $requestToAuthenticate
     *    
     * @access public
     * @return Events\Promise A promise that resolves to a Entity\Card-Instance or NULL
     **/
    public function authenticateRequest (REST\ABI\Request $requestToAuthenticate) : Events\Promise {
      // Check if there is authentication-information on the request
      if ((($authMeta = $requestToAuthenticate->getMeta ('Authorization')) === null) ||
          (($schemeSeparator = strpos ($authMeta, ' ')) === false))
        return Events\Promise::resolve (null);
      
      // Extract scheme
      $usedScheme = substr ($authMeta, 0, $schemeSeparator);
      
      // Check the scheme
      if (strcasecmp ($usedScheme, 'Basic') !== 0)
        return Events\Promise::resolve (null);
      
      // Extract username and password
      $userCredentials = base64_decode (trim (substr ($authMeta, $schemeSeparator + 1)));
      
      if (($credentialSeparator = strpos ($userCredentials, ':')) === false)
        return Events\Promise::reject ('Malformed basic authentication');
      
      $userName = substr ($userCredentials, 0, $credentialSeparator);
      $userPassword = substr ($userCredentials, $credentialSeparator + 1);
      
      // Check the credentials
      if (!isset ($this->registeredUsers [$userName]))
        return Events\Promise::reject ('Unknown user');
      
      if (strcmp ($this->registeredUsers [$userName], $userPassword) !== 0)
        return Events\Promise::reject ('Invalid password');
      
      // Create dummy-entity
      $userEntity = new Entity\Card (null, 'VCARD');
      $userEntity->setKind ('individual');
      $userEntity->updateProperty ('FN', $userName);
      
      return Events\Promise::resolve ($userEntity);
    }
    // }}}

    // {{{ getSchemes
    /**
     * Retrive a list of supported authentication-schemes.
     * 
     * The list is represented by an array of associative arrays, each with the following keys:
     * 
     *   scheme: A well known name of the scheme
     *   realm:  A realm for the scheme
     * 
     * @access public
     * @return array
     **/
    public function getSchemes () : array {
      return [
        [
          'scheme' => 'Basic',
          'realm' => 'Basic HTTP Authentication',
        ],
      ];
    }
    // }}}
  }
