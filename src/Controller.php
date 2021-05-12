<?php

  /**
   * qcREST - Controller
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

  namespace quarxConnect\REST;
  use \quarxConnect\Events;
  use \quarxConnect\Entity;
  
  abstract class Controller implements ABI\Controller {
    /* REST-Element to use as root */
    private $rootElement = null;
    
    /* Registered Authenticators */
    private $Authenticators = [ ];
    
    /* Registered Authorizers */
    private $Authorizers = [ ];
    
    /* Registered Input/Output-Processors */
    private $Processors = [ ];
    
    /* Registered request-handlers */
    private $requestHandlerResource = [ ];
    private $requestHandlerCollection = [ ];
    
    /* Mapping of mime-types to processors */
    private $typeMaps = [ ];
    
    /* Always return a representation of the resource */
    private $alwaysRepresentation = false;
    
    // {{{ __construct
    /**
     * Setup a new controller
     * 
     * @access friendly
     * @return void
     **/
    function __construct () {
      
    }
    // }}}
    
    // {{{ setRootElement
    /**
     * Set the root resource for this controller
     * 
     * @param ABI\Entity $rootElement
     * 
     * @access public
     * @return void
     **/
    public function setRootElement (ABI\Entity $rootElement) : void {
      $this->rootElement = $rootElement;
    }
    // }}}
    
    // {{{ getEntityURI
    /**
     * Retrive the URI for a given entity
     * 
     * @param ABI\Entity $Entity (optional)
     * @param bool $Absolute (optional)
     * 
     * @access public
     * @return string
     **/
    public function getEntityURI (ABI\Entity $Entity = null, bool $Absolute = false) : string {
      if (!$Entity)
        return '/';
      
      $Path = '';
      $Full = false;
      
      do {
        // Check if we reached the root-element
        $Full = ($Entity === $this->rootElement);
        $cEntity = $Entity;
        
        // Check for a resource (and hybrids)
        if ($Entity instanceof ABI\Resource) {
          if (!$Full)
            $Path = $Entity->getName () . ($Entity instanceof ABI\Collection ? '/' : '') . $Path;
          
          $Entity = $Entity->getCollection ();
        
        // Must be a collection
        } else {
          $Path = '/' . $Path;
          $Entity = $Entity->getResource ();
        }
        
        // Sanity-Check for lockups
        if ($cEntity === $Entity) {
          trigger_error ('Loop detected');
          
          break;
        }
      } while (!$Full && $Entity);
      
      // Make sure we found the whole path
      if (!$Full)
        trigger_error ('Path may be incomplete: ' . $Path);
      
      // Convert to absolute path
      if ($Absolute) {
        $baseURI = $this->getURI ();
        
        if (substr ($baseURI, -1, 1) == '/')
          $baseURI = substr ($baseURI, 0, -1);
        
        $Path = $baseURI . $Path;
      }
      
      return $Path;
    }
    // }}}
    
    // {{{ addProcessor
    /**
     * Register a new input/output-processor on this controller
     * 
     * @param ABI\Processor $newProcessor
     * @param array $mimeTypes (optional) Restrict the processor for these  types
     * 
     * @access public
     * @return void
     **/
    public function addProcessor (ABI\Processor $newProcessor, array $mimeTypes = null) : void {
      // Make sure we have a set of mime-types
      if (!is_array ($mimeTypes) || (count ($mimeTypes) == 0))
        $mimeTypes = $newProcessor->getSupportedContentTypes ();
      
      // Process all mime-types
      $haveMime = false;
      
      foreach ($mimeTypes as $mimeType) {
        // Make sure the Mimetype is well-formeed
        if (($p = strpos ($mimeType, '/')) === false)
          continue;
        
        // Split up the mime-type
        $mimeMajor = substr ($mimeType, 0, $p);
        $mimeMinor = substr ($mimeType, $p + 1);
        $haveMime = true;
        
        // Add to our collection
        if (!isset ($this->typeMaps [$mimeMajor]))
          $this->typeMaps [$mimeMajor] = [ $mimeMinor => [ $newProcessor ] ];
        elseif (!isset ($this->typeMaps [$mimeMajor][$mimeMinor]))
          $this->typeMaps [$mimeMajor][$mimeMinor] = [ $newProcessor ];
        elseif (!in_array ($newProcessor, $this->typeMaps [$mimeMajor][$mimeMinor], true))
          $this->typeMaps [$mimeMajor][$mimeMinor][] = $newProcessor;
      }
      
      // Add the processor to our collection
      if ($haveMime && !in_array ($newProcessor, $this->Processors, true))
        $this->Processors [] = $newProcessor;
    }
    // }}}
    
    // {{{ getProcessor
    /**
     * Retrive a processor for a given MIME-Type
     * 
     * @param string $mimeType
     * 
     * @access protected
     * @return ABI\Processor
     **/
    protected function getProcessor (string $mimeType) : ?ABI\Processor {
      // Make sure the Mimetype is well-formeed
      if (($p = strpos ($mimeType, '/')) === false)
        return null;
      
      // Split up the mime-type
      $Major = substr ($mimeType, 0, $p);
      $Minor = substr ($mimeType, $p + 1);
      
      // Check for any major with a specific minor
      if (($Major == '*') && ($Minor != '*')) {
        foreach ($this->typeMaps as $mKey=>$Minors)
          if (isset ($Minors [$Minor]))
            return $Minors [$Minor];
      } elseif (($Major == '*') && ($Minor == '*'))
        foreach ($this->typeMaps as $Minors)
          foreach ($Minors as $Minor)
            return array_shift ($Minor);
      
      // Check if we have the major type available
      if (!isset ($this->typeMaps [$Major])) {
        $Major = '*';
        
        if (!isset ($this->typeMaps [$Major]))
          return null;
      }
      
      // Check for minor match
      if (isset ($this->typeMaps [$Major][$Minor]))
        return reset ($this->typeMaps [$Major][$Minor]);
      
      if ($Minor == '*')
        return @array_shift (reset ($this->typeMaps [$Major]));
      
      if (isset ($this->typeMaps [$Major]['*']))
        return reset ($this->typeMaps [$Major]['*']);
      
      return null;
    }
    // }}}
    
    // {{{ addAuthenticator
    /**
     * Register a new authenticator on this controller
     * 
     * @param ABI\Authenticator $Authenticator
     * 
     * @access public
     * @return bool  
     **/
    public function addAuthenticator (ABI\Authenticator $Authenticator) : bool {
      if (!in_array ($Authenticator, $this->Authenticators, true))
        $this->Authenticators [] = $Authenticator;
      
      return true;
    }
    // }}}
    
    // {{{ addAuthorizer
    /**
     * Register a new authorizer on this controller
     * 
     * @param ABI\Authorizer $Authorizer
     * 
     * @access public
     * @return bool
     **/
    public function addAuthorizer (ABI\Authorizer $Authorizer) : bool {
      if (!in_array ($Authorizer, $this->Authorizers, true))
        $this->Authorizers [] = $Authorizer;
      
      return true;
    }
    // }}}
    
    // {{{ httpHeaderParameters
    /**
     * Parse/Explode Parameters from a HTTP-Header
     * 
     * @param string $Data
     * 
     * @access public
     * @return array
     **/
    public static function httpHeaderParameters (string $Data) : array {
      // Create an empty set of parameters
      $Parameters = [ ];
      
      // Parse the input
      $Token = '!#$%&\'*+-.0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ^_`abcdefghijklmnopqrstuvwxyz|~';
      $Start = 0;
      $Mode = 0;
      $Len = strlen ($Data);
      $Attribute = null;
      
      for ($Pos = $Start; $Pos < $Len; $Pos++) {
        // Read attribute or token-value
        if ($Mode < 2) {
          // Check if the current character is a valid token-value
          if (strpos ($Token, $Data [$Pos]) !== false)
            continue;
          
          // Check if an attribute was ready
          if ($Mode == 0) {
            if ((($Data [$Pos] == ' ') || ($Data [$Pos] == ';')) && ($Pos == $Start++))
              continue;
            elseif ($Data [$Pos] != '=')
              return false;
            
            $Attribute = substr ($Data, $Start, $Pos - $Start);
            $Start = $Pos + 1;
            $Mode = 1;
            
            continue;
          }
          
          // Check if the token-value is a quoted value
          if (($Data [$Pos] == '"') && ($Pos == $Start)) {
            $Mode = 2;
            $Start++;
            
            continue;
          }
          
          // Check if the token-value is finished
          if ($Data [$Pos] != ';')
            return false;
          
          $Parameters [$Attribute] = substr ($Data, $Start, $Pos - $Start);
          $Mode = 0;
          $Start = $Pos + 1;
          
        
        // Read quoted value
        } elseif ($Mode == 2) {
          if ($Data [$Pos] != '"')
            continue;
          
          $Parameters [$Attribute] = substr ($Data, $Start, $Pos - $Start);
          $Mode = 0;
          $Start = $Pos + 1;
        }
      }
      
      // Process last unfinished value
      if ($Mode == 1)
        $Parameters [$Attribute] = substr ($Data, $Start);
      elseif ($Mode != 0)
        throw new \Exception ('Malformed header');
      
      // Return the result
      return $Parameters;
    }
    // }}}
    
    // {{{ handle
    /**
     * Try to process a request, if no request is given explicitly try to fetch one from SAPI
     * 
     * @param ABI\Request $theRequest (optional)
     * 
     * @access public
     * @return Events\Promise
     **/
    public function handle (ABI\Request $theRequest = null) : Events\Promise {
      // Make sure we have a request-object
      if (($theRequest === null) && (($theRequest = $this->getRequest ()) === null))
        return Events\Promise::reject ('Could not retrive request');
      
      // Make sure we have a root-element assigned
      if (!$this->rootElement)
        # TODO: This should be a rejection
        return $this->respondStatus ($theRequest, ABI\Response::STATUS_ERROR);
      
      // Find a suitable processor for the response
      $outputProcessor = null;
      
      foreach ($theRequest->getAcceptedContentTypes () as $mimeType)
        if ($outputProcessor = $this->getProcessor ($mimeType))
          break;
      
      if (!is_object ($outputProcessor))
        # TODO: This should be a rejection
        return $this->respondStatus ($theRequest, ABI\Response::STATUS_NO_FORMAT);
      
      // Try to authenticate the request
      return $this->authenticateRequest ($theRequest)->then (
        // Authentication was successfull
        function ($authenticatedUser) use ($theRequest, $outputProcessor) {
          // Forward the authenticated user to Request
          if ($authenticatedUser instanceof Entity\Card)
            $theRequest->setUser ($authenticatedUser);
          
          // Try to resolve to a resource
          return $this->resolveURI (
            $theRequest->getURI (),
            $theRequest
          )->then (
            // Try to authorize access to that resource
            function (ABI\Resource $resolvedResource = null, ABI\Collection $resolvedCollection = null, string $resolvedSegment = null)
            use ($theRequest, $outputProcessor) {
              // Try to authorize
              return $this->authorizeRequest ($theRequest, $resolvedResource, $resolvedCollection)->then (
                function () use ($theRequest, $resolvedResource, $resolvedCollection, $resolvedSegment, $outputProcessor) {
                  return $this->handleAuthorizedRequest ($theRequest, $resolvedResource, $resolvedCollection, $resolvedSegment, $outputProcessor);
                },
                function (\Throwable $errorMessage, ABI\Representation $errorRepresentation = null)
                use ($theRequest, $resolvedResource, $resolvedCollection) {
                  // Forward Representation of the error if there is one
                  if ($errorRepresentation)
                    # TODO: This should be a rejection
                    return $this->handleRepresentation ($theRequest, $resolvedResource, $resolvedCollection, $errorRepresentation, null, ABI\Response::STATUS_CLIENT_UNAUTHORIZED);
                  
                  // Bail out an error
                  if (defined ('\\QCREST_DEBUG'))
                    trigger_error ('Authorization failed');
                  
                  // Forward the result
                  # TODO: This should be a rejection
                  return $this->respondStatus ($theRequest, ABI\Response::STATUS_CLIENT_UNAUTHORIZED);
                }
              );
            },
            function () use ($theRequest) {
              # TODO: This should be a rejection
              return $this->respondStatus (
                $theRequest,
                ($theRequest->getMethod () == $theRequest::METHOD_OPTIONS ? ABI\Response::STATUS_OK : ABI\Response::STATUS_NOT_FOUND),
                [ ]
              );
            }
          );
        },
        
        // Authentication failed
        function (\Throwable $errorMessage, ABI\Representation $errorRepresentation = null)
        use ($theRequest, $outputProcessor) {
          // Forward Representation of the error if there is one
          if ($errorRepresentation)
            # TODO: This should be a rejection
            return $this->handleRepresentation ($theRequest, null, null, $errorRepresentation, $outputProcessor, ABI\Response::STATUS_CLIENT_UNAUTHENTICATED);
          
          // Bail out an error
          if (defined ('\\QCREST_DEBUG'))
            trigger_error ('Authentication failure');
          
          // Forward the result
          # TODO: This should be a rejection
          return $this->respondStatus ($theRequest, ABI\Response::STATUS_CLIENT_UNAUTHENTICATED);
        }
      );
    }
    // }}}
    
    // {{{ authenticateRequest
    /**
     * Try to authenticate a given request
     * 
     * @param ABI\Request $Request
     * 
     * @access private
     * @return Events\Promise A promise that resolves to a qcEntity_Card-Instance or NULL
     **/
    private function authenticateRequest (ABI\Request $Request) : Events\Promise {
      // Check if there are authenticators to process
      if (count ($this->Authenticators) == 0)
        return Events\Promise::resolve (null);
      
      // Call all authenticators
      $Authenticators = $this->Authenticators;
      
      foreach ($Authenticators as $Authenticator)
        $Promises [] = $Authenticator->authenticateRequest ($Request);
      
      return Events\Promise::all ($Promises)->then (
        function ($Results) {
          // Check if any user was found
          foreach ($Results as $User)
            if ($User instanceof Entity\Card)
              return $User;
          
          // ... or be a bit laisser faire
          return null;
        }
      );
    }
    // }}}
    
    // {{{ authorizeRequest
    /**
     * Try to authorize a request
     * 
     * @param ABI\Request $Request
     * @param ABI\Resource $Resource (optional)
     * @param ABI\Collection $Collection (optional)
     * 
     * @access private
     * @return Events\Promise
     **/
    private function authorizeRequest (ABI\Request $Request, ABI\Resource $Resource = null, ABI\Collection $Collection = null) : Events\Promise {
      // Check if there are authenticators to process
      if (count ($this->Authorizers) == 0)
        return Events\Promise::resolve (true);
      
      // Call all authorizers
      $Authorizers = $this->Authorizers;
      $Promises = [ ];
      
      foreach ($Authorizers as $Authorizer)
        $Promises [] = $Authorizer->authorizeRequest ($Request, $Collection ?? $Resource, $Resource);
      
      return Events\Promise::all ($Promises)->then (
        function () {
          return true;
        }
      );
    }
    // }}}
    
    // {{{ getAuthorizedMethods
    /**
     * Retrive authorized methods for a resource or collection
     * 
     * @param ABI\Resource $Resource
     * @param ABI\Collection $Collection (optional)
     * @param ABI\Request $Request (optional)
     *    
     * @access public
     * @return Events\Promise
     **/
    public function getAuthorizedMethods (ABI\Resource $Resource, ABI\Collection $Collection = null, ABI\Request $Request = null) : Events\Promise {
      // Check if there are authenticators to process
      if (count ($this->Authorizers) == 0)
        return Events\Promise::resolve ([
          ABI\Request::METHOD_GET,
          ABI\Request::METHOD_POST,
          ABI\Request::METHOD_PUT,
          ABI\Request::METHOD_PATCH,
          ABI\Request::METHOD_DELETE,
          ABI\Request::METHOD_OPTIONS,
          ABI\Request::METHOD_HEAD,
        ]);
      
      // Call all authorizers
      $Authorizers = $this->Authorizers;
      $Promises = [ ];
      
      foreach ($Authorizers as $Authorizer)
        $Promises [] = $Authorizer->getAuthorizedMethods ($Resource, $Collection, $Request);
      
      return Events\Promise::all ($Promises)->then (
        function ($Results) {
          // Merge grants
          $Grants = null;
          
          foreach ($Results as $Result)
            if ($Grants !== null) {
              foreach ($Grants as $k=>$Grant)
                if (!in_array ($Grant, $Result))
                  unset ($Grants [$k]);
            } else
              $Grants = $Result;
          
          return $Grants;
        }
      );
    }
    // }}}
    
    // {{{ resolveURI
    /**
     * Try to resolve a given URI to a REST-Resource or REST-Collection
     * 
     * @param string $URI The actual URI to resolve
     * @param ABI\Request $Request (optional)
     * 
     * @access private
     * @return Events\Promise
     **/
    private function resolveURI (string $URI, ABI\Request $Request = null) : Events\Promise {
      // Make sure we have a root assigned
      if ($this->rootElement === null)
        return Events\Promise::reject ('No root-element assigned');
      
      // Check if this is a request for our root
      $lURI = strlen ($URI);
      
      if (($lURI == 0) ||
          (($lURI == 1) && ($URI [0] == '/') && ($this->rootElement instanceof ABI\Collection)))
        return Events\Promise::resolve (
          ($this->rootElement instanceof ABI\Resource ? $this->rootElement : null),
          ($this->rootElement instanceof ABI\Collection ? $this->rootElement : null),
          null
        );
      
      // Translate the path
      if ($URI [0] == '/')
        $URI = substr ($URI, 1);
      
      $iPath = explode ('/', $URI);
      $lPath = count ($iPath);
      $Path = [ ];
      
      for ($i = 0; $i < $lPath; $i++)
        if (($i == 0) || ($i == $lPath - 1) || (strlen ($iPath [$i]) > 0))
          $Path [] = rawurldecode ($iPath [$i]);
      
      // Try to get root-collection
      if ($this->rootElement instanceof ABI\Resource) {
        $Promise = $this->rootElement->getChildCollection ();
        $Resource = $this->rootElement;
      } else {
        $Promise = Events\Promise::resolve ($this->rootElement);
        $Resource = null;
      }
      
      $collectionFunction =
        function (ABI\Collection $Collection)
        use ($Request, &$Path, &$Resource, &$collectionFunction) {
          // Check if there is nothing to look up
          if (count ($Path) == 0)
            return new Events\Promise\Solution ([ $Resource, $Collection ]);
          
          $Next = array_shift ($Path);
          
          if ((count ($Path) == 0) && (strlen ($Next) == 0))
            return new Events\Promise\Solution ([ $Resource, $Collection ]);
          
          // Try to lookup the next resource
          return $Collection->getChild ($Next, $Request)->then (
            function (ABI\Resource $nResource)
            use (&$Resource, &$Path, &$collectionFunction) {
              // Check if we are done
              if (count ($Path) == 0)
                return $nResource;
              
              // Change current resource
              $Resource = $nResource;
              
              // Try to access child-collection of that resource
              return $Resource->getChildCollection ()->then ($collectionFunction);
            },
            function ()
            use ($Request, &$Resource, $Collection, &$Path, $Next) {
              // Just push this error forward
              if ((count ($Path) != 0) || ($Request->getMethod () != $Request::METHOD_PUT))
                throw new Events\Promise\Solution (func_get_args ());
              
              // Resolve to collection with segment
              return new Events\Promise\Solution ([ $Resource, $Collection, $Next ]);
            }
          );
        };
      
      return $Promise->then ($collectionFunction);
    }
    // }}}
    
    // {{{ handleAuthorizedRequest
    /**
     * Handle a request that was previously authorized and resolved
     * 
     * @param ABI\Request $theRequest
     * @param ABI\Resource $resolvedResource
     * @param ABI\Collection $resolvedCollection
     * @param string $resolvedSegment
     * @param ABI\Processor $outputProcessor
     * 
     * @access private
     * @return Events\Promise
     **/
    private function handleAuthorizedRequest (ABI\Request $theRequest, ABI\Resource $resolvedResource = null, ABI\Collection $resolvedCollection = null, string $resolvedSegment = null, ABI\Processor $outputProcessor) : Events\Promise {
      // Retrive default headers just for convienience
      $defaultHeaders = $this->getDefaultHeaders ($theRequest, ($resolvedCollection ?? $resolvedResource));
      
      // Check if there is a request-body
      if (($inputType = $theRequest->getContentType ()) !== null) {
        // Make sure we have a processor for this
        if (!is_object ($inputProcessor = $this->getProcessor ($inputType))) {
          if (defined ('\\QCREST_DEBUG'))
            trigger_error ('No input-processor for content-type ' . $inputType);
          
          # TODO: This should be a rejection
          return $this->respondStatus ($theRequest, ABI\Response::STATUS_FORMAT_UNSUPPORTED, $defaultHeaders);
        }
      } else
        $inputProcessor = null;
      
      // Check if we should expect a request-body
      if (in_array ($theRequest->getMethod (), [ ABI\Request::METHOD_POST, ABI\Request::METHOD_PUT, ABI\Request::METHOD_PATCH ])) {
        // Make sure we have an input-processor
        if (!$inputProcessor) {
          if (defined ('\\QCREST_DEBUG'))
            trigger_error ('No input-processor for request found');
          
          # TODO: This should be a rejection
          return $this->respondStatus ($theRequest, ABI\Response::STATUS_CLIENT_ERROR, $defaultHeaders);
          
        // Make sure the request-body is present
        } elseif (($inputData = $theRequest->getContent ()) === null)
          # TODO: This should be a rejection
          return $this->respondStatus ($theRequest, ABI\Response::STATUS_FORMAT_MISSING, $defaultHeaders);
          
        // Try to parse the request-body
        elseif (!($inputRepresentation = $inputProcessor->processInput ($inputData, $inputType, $theRequest)))
          # TODO: This should be a rejection
          return $this->respondStatus ($theRequest, ABI\Response::STATUS_FORMAT_ERROR, $defaultHeaders);
        
      // ... or fail if there is content on the request
      } elseif (strlen ($theRequest->getContent ()) > 0) {
        if (defined ('\\QCREST_DEBUG'))
          trigger_error ('Content on request where none is expected');
        
        # TODO: This should be a rejection
        return $this->respondStatus ($theRequest, ABI\Response::STATUS_CLIENT_ERROR, $defaultHeaders);
        
      // Just make sure $inputRepresentation is set
      } else
        $inputRepresentation = null;
      
      // Check if the resolver found a collection
      if ($resolvedCollection !== null)
        return $this->handleCollectionRequest ($resolvedCollection, $theRequest, $outputProcessor, $resolvedResource, $inputRepresentation, $resolvedSegment);
      
      // Check if the resource found a resource
      return $this->handleResourceRequest ($resolvedResource, $theRequest, $outputProcessor, $inputRepresentation);
    }
    // }}}
    
    // {{{ handleResourceRequest
    /**
     * Handle a request on a normal resource
     * 
     * @param ABI\Resource $Resource
     * @param ABI\Request $Request
     * @param ABI\Processor $outputProcessor
     * @param ABI\Representation $Representation (optional)
     * 
     * @access private
     * @return Events\Promise
     **/
    private function handleResourceRequest (ABI\Resource $Resource, ABI\Request $Request, ABI\Processor $outputProcessor, ABI\Representation $Representation = null) : Events\Promise {
      // Retrive default headers for convienience
      $Headers = $this->getDefaultHeaders ($Request, $Resource);
      
      // Process the method
      switch ($Request->getMethod ()) {
        // Generate a normal representation of that resource
        case $Request::METHOD_GET:
        case $Request::METHOD_HEAD:
          // Make sure this is allowed
          if (($rc = $Resource->isReadable ($Request->getUser ())) !== true) {
            if (($rc === null) && ($Request->getUser () === null)) {
              if (defined ('\\QCREST_DEBUG'))
                trigger_error ('Resource is unsure if it is readable');
              
              # TODO: This should be a rejection
              return $this->respondStatus ($Request, ABI\Response::STATUS_CLIENT_UNAUTHENTICATED, null);
            }
            
            if (defined ('\\QCREST_DEBUG'))
              trigger_error ('Resource is not readable');
            
            # TODO: This should be a rejection
            return $this->respondStatus ($Request, ABI\Response::STATUS_NOT_ALLOWED, $Headers);
          }
          
          // Retrive the attributes
          return $Resource->getRepresentation ($Request)->then (
            function (ABI\Representation $Representation)
            use ($Resource, $Request, $Headers, $outputProcessor) {
              return $this->handleRepresentation ($Request, $Resource, null, $Representation, $outputProcessor, ABI\Response::STATUS_OK, $Headers);
            },
            function (\Throwable $errorMessage, ABI\Representation $Representation = null)
            use ($Request, $Resource, $outputProcessor, $Headers) {
              // Forward Representation of the error if there is one
              if ($Representation)
                # TODO: This should be a rejection
                return $this->handleRepresentation ($Request, $Resource, null, $Representation, $outputProcessor, ABI\Response::STATUS_ERROR, $Headers);
              
              // Forward the error
              # TODO: This should be a rejection
              return $this->respondStatus ($Request, ABI\Response::STATUS_ERROR, $Headers);
            }
          );
        
        // Check if a new sub-resource is requested
        case $Request::METHOD_POST:
          // Convert the request into a directory-request if possible
          return $Resource->getChildCollection ()->then (
            function (ABI\Collection $Collection) use ($Resource, $Request, $Representation, $outputProcessor) {
              return $this->handleCollectionRequest ($Collection, $Request, $outputProcessor, $Resource, $Representation);
            },
            function (\Throwable $errorMessage, ABI\Representation $Representation = null)
            use ($Request, $Resource, $outputProcessor, $Headers) {
              // Forward Representation of the error if there is one
              if ($Representation)
                # TODO: This should be a rejection
                return $this->handleRepresentation ($Request, $Resource, null, $Representation, $outputProcessor, ABI\Response::STATUS_NOT_ALLOWED, $Headers);
              
              // Bail out an error
              if (defined ('\\QCREST_DEBUG'))
                trigger_error ('No child-collection');
              
              // Forward the result
              # TODO: This should be a rejection
              return $this->respondStatus ($Request, ABI\Response::STATUS_NOT_ALLOWED, $Headers);
            }
          );
        
        // Change attributes
        case $Request::METHOD_PUT:
          // Make sure this is allowed  
          if (($rc = $Resource->isWritable ($Request->getUser ())) !== true) {
            if (($rc === null) && ($Request->getUser () === null)) {
              if (defined ('\\QCREST_DEBUG'))
                trigger_error ('Resource is unsure wheter it is writable');
              
              return $this->respondStatus ($Request, ABI\Response::STATUS_CLIENT_UNAUTHENTICATED);
            }
            
            if (defined ('\\QCREST_DEBUG'))
              trigger_error ('Resource is not writable');
            
            return $this->respondStatus ($Request, ABI\Response::STATUS_NOT_ALLOWED, $Headers);
          }
          
          return $Resource->setRepresentation ($Representation, $Request)->then (
            function (ABI\Representation $Representation) use ($Resource, $Request, $outputProcessor, $Headers) {
              // Check wheter to just pass the result (default)
              if (!$this->alwaysRepresentation || ($Resource->isReadable ($Request->getUser ()) !== true))
                return $this->respondStatus ($Request, ABI\Response::STATUS_STORED, $Headers);
              
              // Forward the representation
              return $this->handleRepresentation ($Request, $Resource, null, $Representation, $outputProcessor, ABI\Response::STATUS_OK, $Headers);
            },
            function (\Throwable $errorMessage, ABI\Representation $Representation = null)
            use ($Resource, $Request, $outputProcessor, $Headers) {
              // Use representation if there is a negative status on it
              if ($Representation)
                # TODO: This should be a rejection
                return $this->handleRepresentation ($Request, $Resource, null, $Representation, $outputProcessor, ABI\Response::STATUS_FORMAT_REJECTED, $Headers);
              
              // Push back an error
              # TODO: This should be a rejection
              return $this->respondStatus ($Request, ABI\Response::STATUS_FORMAT_REJECTED, $Headers);
            }
          );
        case $Request::METHOD_PATCH:
          // Make sure this is allowed
          if (($rc = $Resource->isWritable ($Request->getUser ())) !== true) {
            if (($rc === null) && ($Request->getUser () === null))
              return $this->respondStatus ($Request, ABI\Response::STATUS_CLIENT_UNAUTHENTICATED);
            
            if (defined ('\\QCREST_DEBUG'))
              trigger_error ('Resource is not writable (will not patch)');
            
            return $this->respondStatus ($Request, ABI\Response::STATUS_NOT_ALLOWED, $Headers);
          }
          
          // Retrive the attributes first
          return $Resource->getRepresentation ($Request)->then (
            function (ABI\Representation $currentRepresentation)
            use ($Resource, $Request, $Representation, $Headers, $outputProcessor) {
              // Update Representation
              $requireAttributes = false;
              
              foreach ($Representation as $Key=>$Value)
                if (!$requireAttributes || isset ($currentRepresentation [$Key])) {
                  $currentRepresentation [$Key] = $Value;
                  
                  unset ($Representation [$Key]);
                } else
                  return $this->respondStatus ($Request, ABI\Response::STATUS_FORMAT_ERROR, $Headers);
              
              // Try to update the resource's attributes
              return $Resource->setRepresentation ($currentRepresentation, $Request)->then (
                function (ABI\Representation $Representation)
                use ($Request, $Resource, $outputProcessor, $Headers) {
                  # TODO: Return representation here?
                  return $this->respondStatus ($Request, ABI\Response::STATUS_STORED, $Headers);
                  
                  // Check wheter to just pass the result (default)
                  if (!$this->alwaysRepresentation || ($Resource->isReadable ($Request->getUser ()) !== true))
                    return $this->respondStatus ($Request, ABI\Response::STATUS_STORED, $Headers);
                  
                  // Forward the representation
                  return $this->handleRepresentation ($Request, $Resource, null, $Representation, $outputProcessor, ABI\Response::STATUS_OK, $Headers);
                },
                function (\Throwable $errorMessage, ABI\Representation $Representation = null)
                use ($Request, $Resource, $outputProcessor, $Headers) {
                  // Use representation if there is a negative status on it
                  if ($Representation)
                    # TODO: This should be a rejection
                    return $this->handleRepresentation ($Request, $Resource, null, $Representation, $outputProcessor, ABI\Response::STATUS_FORMAT_REJECTED, $Headers);
                  
                  // Give a normal bad reply if representation does not work
                  # TODO: This should be a rejection
                  return $this->respondStatus ($Request, ABI\Response::STATUS_FORMAT_REJECTED, $Headers);
                }
              );
            },
            function (\Throwable $errorMessage, ABI\Representation $Represenation = null)
            use ($Request, $Resource, $outputProcessor, $Headers) {
              // Forward Representation of the error if there is one
              if ($Representation)
                # TODO: This should be a rejection
                return $this->handleRepresentation ($Request, $Resource, null, $Representation, $outputProcessor, ABI\Response::STATUS_ERROR, $Headers);
              
              // Forward the error
              # TODO: This should be a rejection
              return $this->respondStatus ($Request, ABI\Response::STATUS_ERROR, $Headers);
            }
          );
        
        // Remove this resource
        case $Request::METHOD_DELETE:
          // Make sure this is allowed
          if (($rc = $Resource->isRemovable ($Request->getUser ())) !== true) {
            if (($rc === null) && ($Request->getUser () === null))
              return $this->respondStatus ($Request, ABI\Response::STATUS_CLIENT_UNAUTHENTICATED);
            
            if (defined ('\\QCREST_DEBUG'))
              trigger_error ('Resource may not be removed');
            
            return $this->respondStatus ($Request, ABI\Response::STATUS_NOT_ALLOWED, $Headers);
          }
          
          // Try to remove the resource
          return $Resource->remove ()->then (
            function () use ($Request, $Headers) {
              return $this->respondStatus ($Request, ABI\Response::STATUS_REMOVED, $Headers);
            },
            function (\Throwable $errorMessage, ABI\Representation $Representation = null)
            use ($Request, $Resource, $outputProcessor, $Headers) {
              // Forward Representation of the error if there is one
              if ($Representation)
                # TODO: This should be a rejection
                return $this->handleRepresentation ($Request, $Resource, null, $Representation, $outputProcessor, ABI\Response::STATUS_ERROR, $Headers);

              // Forward the error
              # TODO: This should be a rejection
              return $this->respondStatus ($Request, ABI\Response::STATUS_ERROR, $Headers);
            }
          );
        // Output Meta-Information for this resource
        case $Request::METHOD_OPTIONS:
          // Try to get child-collection
          return $Resource->getChildCollection ()->then (
            function (ABI\Collection $Collection) use ($Request, $User, $Resource, $Headers) {
              if ($Collection->isWritable ($User))
                $Headers ['Access-Control-Allow-Methods'] = array_unique (array_merge ($this->getAllowedMethods ($Request, $Resource), $this->getAllowedMethods ($Request, $Collection)));
              
              return $this->respondStatus ($Request, ABI\Response::STATUS_OK, $Headers);
            },
            function () use ($Request, $Headers) {
              return $this->respondStatus ($Request, ABI\Response::STATUS_OK, $Headers);
            }
          );
      }
      
      # TODO: This should be a rejection
      return $this->respondStatus ($Request, ABI\Response::STATUS_UNSUPPORTED, $Headers);
    }
    // }}}
    
    // {{{ handleCollectionRequest
    /**
     * Process a request targeted at a directory-resource
     * 
     * @param ABI\Collection $Collection
     * @param ABI\Request $Request
     * @param ABI\Processor $outputProcessor
     * @param ABI\Resource $Resource (optional)
     * @param ABI\Representation $Representation (optional)
     * @param string $Segment (optional)
     * 
     * @access private
     * @return Events\Promise
     **/
    private function handleCollectionRequest (
      ABI\Collection $Collection,
      ABI\Request $Request,
      ABI\Processor $outputProcessor,
      ABI\Resource $Resource = null,
      ABI\Representation $Representation = null,
      string $Segment = null
    ) : Events\Promise {
      // Retrive default headers
      $Headers = $this->getDefaultHeaders ($Request, $Collection);
      
      // Retrive the requested method
      $Method = $Request->getMethod ();
      
      // Check if there was a segment left on the request
      if ($Segment !== null) {
        // Only allow segment on PUT
        if ($Method != $Request::METHOD_PUT)
          # TODO: This should be a rejection
          return $this->respondStatus ($Request, ABI\Response::STATUS_NOT_FOUND, $Headers);
        
        // Rewrite to POST
        $Method = $Request::METHOD_POST;
      }
      
      // Try to process the request
      switch ($Method) {
        // Retrive a listing of resources on this directory
        case $Request::METHOD_GET:
        case $Request::METHOD_HEAD:
          // Make sure we may list the contents
          $User = $Request->getUser ();
          
          if (($rc = $Collection->isBrowsable ($User)) !== true) {
            if (($rc === null) && ($User === null))
              # TODO: This should be a rejection
              return $this->respondStatus ($Request, ABI\Response::STATUS_CLIENT_UNAUTHENTICATED);
            
            if (defined ('\\QCREST_DEBUG'))
              trigger_error ('Collection is not browsable');
            
            # TODO: This should be a rejection
            return $this->respondStatus ($Request, ABI\Response::STATUS_NOT_ALLOWED, $Headers);
          }
          
          $Headers ['X-Resource-Type'] = 'Collection';
          $Headers ['X-Collection-Class'] = get_class ($Collection);
          
          // Save some time on HEAD-Requests
          if ($Method == $Request::METHOD_HEAD)
            return $this->respondStatus (
              $Request,
              ABI\Response::STATUS_OK,
              $Headers
            );
          
          // Handle pagination
          $rParams = $Request->getParameters ();
          $First = 0;
          $Last = null;
          
          if (isset ($rParams ['offset']) && ($rParams ['offset'] !== null))
            $First = (int)$rParams ['offset'];
          
          if (isset ($rParams ['limit']) && ($rParams ['limit'] !== null))
            $Last = $First + (int)$rParams ['limit'];
          
          // Handle sorting
          if (isset ($rParams ['sort']) && ($rParams ['sort'] !== null)) {
            $Sort = $rParams ['sort'];
            
            if (isset ($rParams ['order']) && (strcasecmp ($rParams ['order'], 'DESC') != 0))
              $Order = ABI\Collection\Extended::SORT_ORDER_ASCENDING;
            else
              $Order = ABI\Collection\Extended::SORT_ORDER_DESCENDING;
          } else
            $Sort = $Order = null;
          
          // Handle searching
          if (isset ($rParams ['search']) && (strlen ($rParams ['search']) > 0))
            $Search = strval ($rParams ['search']);
          else
            $Search = null;
          
          // Check if the collection supports extended queries
          if ($Collection instanceof ABI\Collection\Extended) {
            // Apply search-phrase
            if ($Search && $Collection->setSearchPhrase ($Search))
              $Search = null;
            
            // Apply sorting
            if ($Sort && $Collection->setSorting ($Sort, $Order))
              $Sort = $Order = null;
            
            // Apply offset/limit
            if (!$Sort && !$Search && ($First || $Last) && $Collection->setSlice ($First, ($Last !== null ? $Last - $First : null)))
              $First = $Last = null;
          }
          
          // Request the children of this resource
          return $Collection->getChildren ($Request)->then (
            function (array $Children, ABI\Representation $Representation = null)
            use ($Collection, $Request, $Resource, $outputProcessor, $Headers, $First, $Last, $Sort, $Search, $User, $Order) {
              // Prepare representation
              if (!$Representation)
                $Representation = new Representation;
              
              $Representation ['type'] = 'listing';
              
              // Determine the total number of children
              if ($Collection instanceof ABI\Collection\Extended)
                $Representation ['total'] = $Collection->getChildrenCount ();
              else
                $Representation ['total'] = count ($Children);
              
              // Make sure that collection-parameters are reset
              if ($Collection instanceof ABI\Collection\Extended)
                $Collection->resetParameters ();
              
              // Determine the base-URI
              $baseURI = $this->getURI ();  
              
              if (substr ($baseURI, -1, 1) == '/')
                $baseURI = substr ($baseURI, 0, -1);
              
              // Prepare the promise-queue
              $Promises = [ ];
              
              // Determine how to present children on the listing
              if (is_callable ([ $Collection, 'getChildFullRepresenation' ]))
                $Extend = $Collection->getChildFullRepresenation ();
              else
                $Extend = false;
              
              // Bail out a warning if we use pagination here
              if (($First > 0) || ($Last !== null))
                $Representation->addMeta ('X-Pagination-Performance-Warning', 'Using pagination without support on backend');
              
              // Append children to the listing
              $Representation ['idAttribute'] = $Collection->getNameAttribute ();
              $Representation ['items'] = $Items = [ ];
              $Pos = 0;
              $Last = ($Last === null ? count ($Children) : $Last);
              
              foreach ($Children as $Child) {
                // Check if we may skip the generation of this child
                if (!($Sort || $Search)) {
                  if ($Pos++ < $First)
                    continue;
                  elseif ($Pos > $Last)
                    break;
                }
                
                // Create basic attributes
                $Items [] = $Item = new \stdClass;
                $Item->_id = $Child->getName ();
                $Item->_href = $baseURI . $this->getEntityURI ($Child);
                $Item->_collection = $Child->hasChildCollection ();
                $Item->_permissions = new stdClass;
                $Item->_permissions->read = $Child->isReadable ($User);
                $Item->_permissions->write = $Child->isWritable ($User);
                $Item->_permissions->delete = $Child->isRemovable ($User);
                
                // Ask authorizers for permissions
                $Promises [] = $Request->getController ()->getAuthorizedMethods ($Child, null, $Request)->then (
                  function (array $Grants) use ($Item, $Request) {
                    // Patch resource rights
                    $Item->_permissions->read   = $Item->_permissions->read && in_array ($Request::METHOD_GET, $Grants);
                    $Item->_permissions->write  = $Item->_permissions->write && (in_array ($Request::METHOD_POST, $Grants) ||
                                                                                 in_array ($Request::METHOD_PUT, $Grants)  ||
                                                                                 in_array ($Request::METHOD_PATCH, $Grants));
                    $Item->_permissions->delete = $Item->_permissions->delete && in_array ($Request::METHOD_DELETE, $Grants);
                  },
                  function () { }
                );
                
                // Check permissions of containing collection 
                if ($Child->hasChildCollection ())
                  $Promises [] = $Child->getChildCollection ()->then (
                    function (ABI\Collection $Collection)
                    use ($Child, $Item, $Request, $User) {
                      // Patch in default rights
                      $Item->_permissions->collection = new \stdClass;
                      $Item->_permissions->collection->browse = $Collection->isBrowsable ($User);
                      $Item->_permissions->collection->write = $Collection->isWritable ($User);
                      $Item->_permissions->collection->delete = $Collection->isRemovable ($User);
                      
                      return $Request->getController ()->getAuthorizedMethods ($Child, $Collection, $Request)->then (
                        function ($Grants) use ($Item, $Request) {
                          // Patch collection rights
                          $Item->_permissions->collection->browse = $Item->_permissions->collection->browse && in_array ($Request::METHOD_GET, $Grants);
                          $Item->_permissions->collection->write  = $Item->_permissions->collection->write && (in_array ($Request::METHOD_POST, $Grants) ||
                                                                                                               in_array ($Request::METHOD_PUT, $Grants)  ||
                                                                                                               in_array ($Request::METHOD_PATCH, $Grants));
                          $Item->_permissions->collection->delete = $Item->_permissions->collection->delete && in_array ($Request::METHOD_DELETE, $Grants);
                        }
                      );
                    }
                  )->catch (
                    function () { }
                  );
                
                // Store the children on the representation
                // We do this more often as the callback-function (below) relies on this
                $Representation ['items'] = $Items;
                
                // Check wheter to expand the child
                if (!(($Aware = ($Child instanceof ABI\Collection\Representation)) || $Extend))
                  continue;
                
                // Expand the child
                if ($Aware)
                  $Promise = $Child->getCollectionRepresentation ($Request);
                else
                  $Promise = $Child->getRepresentation ($Request);
                
                $Promises [] = $Promise->then (
                  function (ABI\Representation $Representation) use ($Item) {
                    // Patch item on representation
                    foreach ($Representation as $Key=>$Value)
                      if (!in_array ($Key, [ '_id', '_href', '_collection', '_permissions' ]))
                        $Item->$Key = $Value;
                      else
                        trigger_error ('Skipping reserved key ' . $Key);
                  },
                  function () { }
                );
              }
              
              return \Events\Promise::all ($Promises)->then (
                function ()
                use ($Request, $Resource, $Representation, $Headers, $outputProcessor, $Collection, $First, $Last, $Search, $Sort, $Order) {
                  // Check if we have to apply anything
                  if ($Search || $Sort) {
                    // Access the items
                    $Items = $Representation ['items'];
                    
                    // Apply search-filter onto the result
                    if ($Search) {
                      $Representation->addMeta ('X-Search-Performance-Warning', 'Using search without support on backend');
                      
                      // Filter the items
                      foreach ($Items as $ID=>$Item) {
                        foreach ($Item as $Key=>$Value)
                          if (stripos ($Value, $Search) !== false)
                            continue (2);
                        
                        unset ($Items [$ID]);
                      }
                      
                      // Update the total-counter
                      $Representation ['total'] = count ($Items);
                    }
                    
                    // Apply sort-filter onto the result
                    if ($Sort) {
                      $Representation->addMeta ('X-Sort-Performance-Warning', 'Using sort without support on backend');
                      
                      // Generate an index
                      $Keys = [ ];
                      
                      foreach ($Items as $Item) {
                        if (isset ($Item->$Sort))
                          $Key = $Item->$Sort;
                        else
                          $Key = ' ';
                        
                        if (isset ($Keys [$Key]))
                          $Keys [$Key][] = $Item;
                        else
                          $Keys [$Key] = [ $Item ];
                      }
                      
                      // Sort the index
                      if ($Order == ABI\Collection\Extended::SORT_ORDER_DESCENDING)
                        krsort ($Keys);
                      else
                        ksort ($Keys);
                      
                      // Push back the result
                      $Items = [ ];
                      
                      foreach ($Keys as $Itms)
                        $Items = array_merge ($Items, $Itms);
                    }
                    
                    // Push back the items
                    if ($Last === null)
                      $Last = count ($Items);
                    
                    $Representation ['items'] = array_slice ($Items, $First, $Last - $First);
                  }
                  
                  // Raise the final callback
                  return $this->handleRepresentation (
                    $Request,
                    $Resource,
                    $Collection,
                    $Representation,
                    $outputProcessor,
                    ABI\Response::STATUS_OK,
                    $Headers
                  );
                }
              );
            },
            function (\Throwable $errorMessage, ABI\Representation $Representation = null)
            use ($Collection, $Request, $Resource, $outputProcessor, $Headers) {
              // Make sure that collection-parameters are reset
              if ($Collection instanceof ABI\Collection\Extended)
                $Collection->resetParameters ();
              
              // Forward Representation of the error if there is one
              if ($Representation)
                # TODO: This should be a rejection
                return $this->handleRepresentation ($Request, $Resource, $Collection, $Representation, $outputProcessor, ABI\Response::STATUS_ERROR, $Headers);
              
              // Bail out an error
              if (defined ('QCREST_DEBUG'))
                trigger_error ('Failed to retrive the children');
              
              // Forward the error
              # TODO: This should be a rejection
              return $this->respondStatus ($Request, ABI\Response::STATUS_ERROR, $Headers);
            }
          );
        
        // Create a new resource on this directory
        case $Request::METHOD_POST:
          // Make sure this is allowed
          if (($rc = $Collection->isWritable ($Request->getUser ())) !== true) {
            if (($rc === null) && ($Request->getUser () === null)) {
              if (defined ('QCREST_DEBUG'))
                trigger_error ('Collection is unsure if it is writable');
              
              # TODO: This should be a rejection
              return $this->respondStatus ($Request, ABI\Response::STATUS_CLIENT_UNAUTHENTICATED);
            }
            
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Collection is not writable');
            
            # TODO: This should be a rejection
            return $this->respondStatus ($Request, ABI\Response::STATUS_NOT_ALLOWED, $Headers);
          }
          
          return $Collection->createChild ($Representation, $Segment, $Request)->then (
            // Child was created
            function (ABI\Resource $Child, ABI\Representation $Representation = null)
            use ($Request, $Collection, $outputProcessor, $Headers) {
              // Create URI for newly created child
              $Headers ['Location'] = $URI = $this->getURI ($Child);
              
              // Process the response
              if ($Representation)
                return $this->handleRepresentation ($Request, $Child, null, $Representation, $outputProcessor, ABI\Response::STATUS_CREATED, $Headers);
              
              // Check wheter to just pass the result (default)
              if (!$this->alwaysRepresentation || ($Child->isReadable ($Request->getUser ()) !== true))
                return $this->respondStatus ($Request, ABI\Response::STATUS_STORED, $Headers);
              
              return $Child->getRepresentation ($Request)->then (
                function (ABI\Representation $Representation)
                use ($Request, $Child, $Headers, $outputProcesso) {
                  return $this->handleRepresentation ($Request, $Child, null, $Representation, $outputProcessor, ABI\Response::STATUS_CREATED, $Headers);
                },
                function () use ($Request, $Headers) {
                  return $this->respondStatus ($Request, ABI\Response::STATUS_CREATED, $Headers);
                }
              );
            },
            
            // Failed to create child
            function (\Throwable $errorMessage, ABI\Representation $Representation = null)
            use ($Request, $Resource, $Collection, $outputProcessor, $Headers) {
              // Forward Representation of the error if there is one
              if ($Representation)
                # TODO: This should be a rejection
                return $this->handleRepresentation ($Request, $Resource, $Collection, $Representation, $outputProcessor, ABI\Response::STATUS_FORMAT_REJECTED, $Headers);
              
              // Bail out an error in debug-mode
              if (defined ('QCREST_DEBUG'))
                trigger_error ('Failed to create child');
              
              // Forward the error
              # TODO: This should be a rejection
              return $this->respondStatus ($Request, ABI\Response::STATUS_FORMAT_REJECTED, $Headers);
            }
          );
        
        // Replace all resources on this directory (PUT) with new ones or just add a new set (PATCH)
        case $Request::METHOD_PUT:
          // Tell later code that we want to remove items
          $Removals = [ ];
          
        case $Request::METHOD_PATCH:
          // Make sure this is allowed
          if (($rc = $Collection->isWritable ($Request->getUser ())) !== true) {
            if (($rc === null) && ($Request->getUser () === null)) {
              if (defined ('QCREST_DEBUG'))
                trigger_error ('Collection is unsure if it is writable');
              
              # TODO: This should be a rejection
              return $this->respondStatus ($Request, ABI\Response::STATUS_CLIENT_UNAUTHENTICATED);
            }
            
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Collection is not writable and contents may not be replaced (PUT) or patched (PATCH)');
            
            # TODO: This should be a rejection
            return $this->respondStatus ($Request, ABI\Response::STATUS_NOT_ALLOWED, $Headers);
          }
          
          // Just check if we are in patch-mode ;-)
          if (!isset ($Removals)) {
            $Removals = null;
            
            if ($Collection instanceof ABI\Collection\Extended)
              $Collection->setNames (array_keys ($Representation->toArray ()));
          }
          
          // Request the children of this resource
          return $Collection->getChildren ($Request)->then (
            function (array $Children)
            use ($Removals, $Request, $Collection, $Representation, $Headers, $outputProcessor) {
              // Process existing children
              $Promises = [ ];
              $lastError = null;
              
              foreach ($Children as $Child)
                // Check if the child is referenced on input-attributes
                if (isset ($Representation [$Name = $Child->getName ()])) {
                  // Derive representation
                  $childRepresentation = new Representation (is_object ($Representation [$Name]) ? get_object_vars ($Representation [$Name]) : $Representation [$Name]);
                  
                  // Remove from queue
                  unset ($Representation [$Name]);
                  
                  // Check if we are PATCHing and should *really* PATCH
                  if (($Removals === null) && (!defined ('QCREST_PATCH_ON_COLLECTION_PATCHES_RESOURCES') || QCREST_PATCH_ON_COLLECTION_PATCHES_RESOURCES))
                    $Promises [] = $Child->getRepresentation ($Request)->then (
                      function (ABI\Representation $currentRepresentation)
                      use ($Child, $childRepresentation, $Request) {
                        // Update Representation
                        $requireAttributes = false;
                        
                        foreach ($childRepresentation as $Key=>$Value)
                          if (!$requireAttributes || isset ($currentRepresentation [$Key]))
                            $currentRepresentation [$Key] = $Value;
                          else
                            throw new exception ('Missing attribute ' . $Key);
                        
                        // Forward the update
                        return $Child->setRepresentation ($currentRepresentation, $Request);
                      }
                    )->catch (
                      function () use (&$lastError) {
                        $lastError = ABI\Response::STATUS_FORMAT_REJECTED;
                      }
                    );
                  else
                    $Promises [] = $Child->setRepresentation ($childRepresentation, $Request)->catch (
                      function () use (&$lastError) {
                        $lastError = ABI\Response::STATUS_FORMAT_REJECTED;
                      }
                    );
                
                // Enqueue it for removal (chilren will only be removed if the request is of method PUT)
                } elseif ($Removals !== null)
                  $Promises [] = $Child->remove ()->catch (
                    function () use (&$lastError) {
                      $lastError = ABI\Response::STATUS_ERROR;
                    }
                  );
              
              // Create pending children
              foreach ($Representation as $Name=>$childAttributes)
                $Promises [] = $Collection->createChild (
                  new Representation (is_object ($childAttributes) ? get_object_vars ($childAttributes) : $childAttributes),
                  $Name,
                  $Request
                )->catch (
                  function () use (&$lastError) {
                    $lastError = ABI\Response::STATUS_FORMAT_REJECTED;
                  }
                );
              
              // Wait for all tasks to finish
              return Events\Promise::all ($Promises)->finally (
                function ()
                use ($Request, $Headers, &$lastError) {
                  if ($lastError === null)
                    $lastError = ABI\Response::STATUS_STORED;
                  
                  # TODO: What if something failed here?
                  return $this->respondStatus ($Request, $lastError, $Headers);
                }
              );
            },
            function (\Throwable $errorMessage, ABI\Representation $Representation = null)
            use ($Request, $Resource, $Collection, $outputProcessor, $Headers) {
              // Forward Representation of the error if there is one
              if ($Representation)
                # TODO: This should be a rejection
                return $this->handleRepresentation ($Request, $Resource, $Collection, $Representation, $outputProcessor, ABI\Response::STATUS_ERROR, $Headers);
              
              // Bail out an error in debug-mode
              if (defined ('\\QCREST_DEBUG'))
                trigger_error ('Failed to retrive the children');
              
              // Forward the error
              # TODO: This should be a rejection
              return $this->respondStatus ($Request, ABI\Response::STATUS_ERROR, $Headers);
            }
          );
        
        // Delete the entire collection
        case $Request::METHOD_DELETE:
          // Make sure this is allowed
          if (($rc = $Collection->isRemovable ($Request->getUser ())) !== true) {
            if (($rc === null) && ($Request->getUser () === null))
              # TODO: This should be a rejection
              return $this->respondStatus ($Request, ABI\Response::STATUS_CLIENT_UNAUTHENTICATED);
            
            if (defined ('\\QCREST_DEBUG'))
              trigger_error ('Collection may not be removed');
            
            # TODO: This should be a rejection
            return $this->respondStatus ($Request, ABI\Response::STATUS_NOT_ALLOWED, $Headers);
          }
          
          return $Collection->remove ()->then (
            function ()
            use ($Request, $Headers) {
              return $this->respondStatus ($Request, ABI\Response::STATUS_OK, $Headers);
            },
            function (\Throwable $errorMessage, ABI\Representation $Representation = null)
            use ($Request, $Resource, $Collection, $outputProcessor, $Headers) {
              // Forward Representation of the error if there is one
              if ($Representation)
                # TODO: This should be a rejection
                return $this->handleRepresentation ($Request, $Resource, $Collection, $Representation, $outputProcessor, ABI\Response::STATUS_ERROR, $Headers);
              
              // Forward the error
              # TODO: This should be a rejection
              return $this->respondStatus ($Request, ABI\Response::STATUS_ERROR, $Headers);
            }
          );
        // Output Meta-Information for this resource
        case $Request::METHOD_OPTIONS:
          // Return the status
          return $this->respondStatus (
            $Request,
            ABI\Response::STATUS_OK,
            $Headers
          );
      }
      
      # TODO: This should be a rejection
      return $this->respondStatus ($Request, ABI\Response::STATUS_UNSUPPORTED, $Headers);
    }
    // }}}
    
    // {{{ handleRepresentation
    /**
     * Process Representation and generate output
     * 
     * @param ABI\Request $Request
     * @param ABI\Resource $Resource (optional)
     * @param ABI\Collection $Collection (optional)
     * @param ABI\Representation $Representation
     * @param ABI\Processor $outputProcessor (optional)
     * @param enum $Status
     * @param array $Meta (optional)
     * 
     * @access private
     * @return Events\Promise
     **/
    private function handleRepresentation (
      ABI\Request $Request,
      ABI\Resource $Resource = null,
      ABI\Collection $Collection = null,
      ABI\Representation $Representation,
      ABI\Processor $outputProcessor = null,
      $Status,
      array $Meta = null
    ) : Events\Promise {
      // Check if the representation overrides something
      if (($newStatus = $Representation->getStatus ()) !== null)
        $Status = $newStatus;
      
      // Make sure meta is an array
      if (!is_array ($Meta))
        $Meta = $Representation->getMeta ();
      else
        $Meta = array_merge ($Meta, $Representation->getMeta ());
      
      // Remove any redirects if unwanted
      if (isset ($Meta ['Location']) && !$Representation->allowRedirect ())
        unset ($Meta ['Location']);
      
      // Append allowed methods
      if (!isset ($Meta ['Access-Control-Allow-Methods']))
        $Meta ['Access-Control-Allow-Methods'] = implode (', ', $this->getAllowedMethods ($Request, ($Collection ? $Collection : $Resource)));
      
      // Just pass the status if the representation is empty
      if ((count ($Representation) == 0) || ($Request->getMethod () == $Request::METHOD_HEAD))
        return $this->respondStatus ($Request, $Status, $Meta);
      
      // Make sure there is an output-processor
      if (count ($outputPreferences = $Representation->getPreferedOutputTypes ()) > 0) {
        $outputCandidates = [ ];
        
        foreach ($outputPreferences as $outputPreference)
          if ($outputCandidate = $this->getProcessor ($outputPreference))
            $outputCandidates [] = $outputCandidate;
        
        foreach ($Request->getAcceptedContentTypes () as $mimeType)
          if (($outputCandidate = $this->getProcessor ($mimeType)) &&
              in_array ($outputCandidate, $outputCandidates, true)) {
            $outputProcessor = $outputCandidate;
            
            break;
          }
      }
      
      if (!$outputProcessor) {
        foreach ($Request->getAcceptedContentTypes () as $mimeType)
          if ($outputProcessor = $this->getProcessor ($mimeType))
            break;
        
        if (!is_object ($outputProcessor))
          return $this->respondStatus ($Request, ABI\Response::STATUS_NO_FORMAT, $Headers);
      }
      
      // Process the output
      return $outputProcessor->processOutput ($Resource, $Representation, $Request, $this)->then (
        function (Response $Response) use ($Resource, $Status, $Meta) {
          // Update status
          $Response->setStatus ($Status);
          
          // Update meta
          if (!isset ($Meta ['X-Resource-Type']))
            $Meta ['X-Resource-Type'] = 'Resource';
          
          if ($Resource)
            $Meta ['X-Resource-Class'] = get_class ($Resource);
          
          foreach ($Meta as $Key=>$Value)
            $Response->setMeta ($Key, $Value);
          
          // Return the response
          return $this->sendResponse ($Response);
        },
        function () use ($Request) {
          if (defined ('\\QCREST_DEBUG'))
            trigger_error ('Output-Processor failed');
          
          # TODO: This should be a rejection
          return $this->respondStatus ($Request, ABI\Response::STATUS_ERROR);
        }
      );
    }
    // }}}
    
    // {{{ sendResponse
    /**
     * Write out a response-object and raise the callback for handle()
     * 
     * @param ABI\Response $Response
     * 
     * @access private
     * @return Events\Promise
     **/
    private function sendResponse (ABI\Response $Response) : Events\Promise {
      // Append some meta for unauthenticated status
      if ($Response->getStatus () == ABI\Response::STATUS_CLIENT_UNAUTHENTICATED) {
        if ($Schemes = $Response->getMeta ('WWW-Authenticate'))
          $Schemes = (is_array ($Schemes) ? $Schemes : [ $Schemes ]);
        else
          $Schemes = [ ];
        
        foreach ($this->Authenticators as $Authenticator)
          foreach ($Authenticator->getSchemes () as $aScheme) 
            if (isset ($aScheme ['scheme']))
              $Schemes [] = $aScheme ['scheme'] . ' realm="' . (isset ($aScheme ['realm']) ? $aScheme ['realm'] : get_class ($Authenticator)) . '"';
        
        $Response->setMeta ('WWW-Authenticate', $Schemes);
      }
      
      // Process the session
      if (($Request = $Response->getRequest ()) && $Request->hasSession ())
        return $Request->getSession ()->then (
          function (ABI\Session $Session) use ($Response) {
            // Add the session to the response
            $Session->addToResponse ($Response);
            
            // Store the session
            return $Session->store ();
          }
        )->catch (function () { })->then (
          function () use ($Response) {
            // Forward the response and store the session
            return $this->setResponse ($Response);
          }
        );
      
      // Forward the response
      return $this->setResponse ($Response);
    }
    // }}}
    
    // {{{ respondStatus
    /**
     * Finish a request with a simple status
     * 
     * @param ABI\Request $Request
     * @param enum $Status
     * @param array $Meta (optional)
     * 
     * @access private
     * @return Events\Promise
     **/
    private function respondStatus (ABI\Request $Request, $Status, array $Meta = null) : Events\Promise {
      // Make sure meta is valid
      if ($Meta === null)
        $Meta = [ ];
      
      return $this->sendResponse (new Response ($Request, $Status, null, null, $Meta));
    }
    // }}}
    
    // {{{ getAllowedMethods
    /**
     * Retrive a set of allowed Verbs for a given Resource (Resource or collection)
     * 
     * @param ABI\Request $Request
     * @param ABI\Entity $Resource (optional)
     * 
     * @access private
     * @return array
     **/
    private function getAllowedMethods (ABI\Request $Request, ABI\Entity $Resource = null) : array {
      // Setup result
      $Methods = [ 'OPTIONS' ];
      
      // Try to retrive a user for the request
      $User = $Request->getUser ();
      
      // Process allowed methods of a collection
      if ($Resource instanceof ABI\Collection) {
        if ($Resource->isBrowsable ($User) === true)
          $Methods [] = 'GET';
        
        if ($Resource->isWritable ($User) === true) {
          $Methods [] = 'POST'; 
          $Methods [] = 'PUT';  
          $Methods [] = 'PATCH';
        }
        
        if ($Resource->isRemovable ($User) === true)
          $Methods [] = 'DELETE';
      }
      
      // Process allowed methods of a resource
      if ($Resource instanceof ABI\Resource) {
        if ($Resource->isReadable ($User) === true)
          $Methods [] = 'GET';
        
        if ($Resource->isWritable ($User) === true) {
          $Methods [] = 'PUT';  
          $Methods [] = 'PATCH';
        }
        
        if ($Resource->isRemovable ($User) === true)
          $Methods [] = 'DELETE';
      }
      
      // Return the result
      return array_unique ($Methods);
    }
    // }}}
    
    // {{{ getDefaultHeaders
    /**
     * Generate a set of default headers for a resource or collection
     * 
     * @param ABI\Request $Request
     * @param ABI\Entity $Resource
     * 
     * @access private
     * @return array
     **/
    private function getDefaultHeaders (ABI\Request $Request, ABI\Entity $Resource) : array {
      return [
        'Access-Control-Allow-Methods' => $this->getAllowedMethods ($Request, $Resource),
      ];
    }
    // }}}
  }
