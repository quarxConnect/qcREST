<?PHP

  /**
   * qcREST - Controller
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
  
  require_once ('qcREST/Interface/Controller.php');
  require_once ('qcREST/Interface/Collection/Extended.php');
  require_once ('qcREST/Response.php');
  require_once ('qcREST/Representation.php');
  require_once ('qcEvents/Promise.php');
  
  abstract class qcREST_Controller implements qcREST_Interface_Controller {
    /* REST-Resource to use as Root */
    private $Root = null;
    
    /* Registered Authenticators */
    private $Authenticators = array ();
    
    /* Registered Authorizers */
    private $Authorizers = array ();
    
    /* Registered Input/Output-Processors */
    private $Processors = array ();
    
    /* Registered request-handlers */
    private $requestHandlerResource = array ();
    private $requestHandlerCollection = array ();
    
    /* Mapping of mime-types to processors */
    private $typeMaps = array ();
    
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
     * @param qcREST_Interface_Resource $Root
     * 
     * @access public
     * @return bool
     **/
    public function setRootElement (qcREST_Interface_Resource $Root) {
      $this->Root = $Root;
      
      return true;
    }
    // }}}
    
    // {{{ getEntityURI
    /**
     * Retrive the URI for a given entity
     * 
     * @param qcREST_Interface_Entity $Resource (optional)
     * @param bool $Absolute (optional)
     * 
     * @access public
     * @return string
     **/
    public function getEntityURI (qcREST_Interface_Entity $Resource = null, $Absolute = false) {
      if (!$Resource)
        return '/';
      
      $Path = '';
      $Full = false;
      
      do {
        if ($Resource instanceof qcREST_Interface_Resource) {
          if ((strlen ($Path) > 0) && ($Path [0] != '/') && ($Resource instanceof qcREST_Interface_Collection)) {
            $Path = '/' . $Path;
          
            if ($Full = (($Resource = $Resource->getResource ()) === $this->Root))
              break;
          } else {
            $Path = $Resource->getName () . $Path;
            $Resource = $Resource->getCollection ();
          }
        } elseif ($Resource instanceof qcREST_Interface_Collection) {
          $Path = '/' . $Path;
          
          if ($Full = (($Resource = $Resource->getResource ()) === $this->Root))
            break;
        }
      } while ($Resource);
      
      if (!$Full)
        trigger_error ('Path may be incomplete: ' . $Path);
      
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
     * @param qcREST_Interface_Processor $Processor
     * @param array $Mimetypes (optional) Restrict the processor for these  types
     * 
     * @access public
     * @return bool
     **/
    public function addProcessor (qcREST_Interface_Processor $Processor, array $Mimetypes = null) {
      // Make sure we have a set of mime-types
      if (!is_array ($Mimetypes) || (count ($Mimetypes) == 0))
        $Mimetypes = $Processor->getSupportedContentTypes ();
      
      // Process all mime-types
      $haveMime = false;
      
      foreach ($Mimetypes as $Mimetype) {
        // Make sure the Mimetype is well-formeed
        if (($p = strpos ($Mimetype, '/')) === false)
          continue;
        
        // Split up the mime-type
        $Major = substr ($Mimetype, 0, $p);
        $Minor = substr ($Mimetype, $p + 1);
        $haveMime = true;
        
        // Add to our collection
        if (!isset ($this->typeMaps [$Major]))
          $this->typeMaps [$Major] = array ($Minor => array ($Processor));
        elseif (!isset ($this->typeMaps [$Major][$Minor]))
          $this->typeMaps [$Major][$Minor] = array ($Processor);
        elseif (!in_array ($Processor, $this->typeMaps [$Major][$Minor], true))
          $this->typeMaps [$Major][$Minor][] = $Processor;
      }
      
      // Add the processor to our collection
      if ($haveMime && !in_array ($Processor, $this->Processors, true))
        $this->Processors [] = $Processor;
      
      return $haveMime;
    }
    // }}}
    
    // {{{ getProcessor
    /**
     * Retrive a processor for a given MIME-Type
     * 
     * @param string $Mimetype
     * 
     * @access protected
     * @return qcREST_Interface_Processor
     **/
    protected function getProcessor ($Mimetype) {
      // Make sure the Mimetype is well-formeed
      if (($p = strpos ($Mimetype, '/')) === false)
        return false;
      
      // Split up the mime-type
      $Major = substr ($Mimetype, 0, $p);
      $Minor = substr ($Mimetype, $p + 1);
      
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
    }
    // }}}
    
    // {{{ addAuthenticator
    /**
     * Register a new authenticator on this controller
     * 
     * @param qcREST_Interface_Authenticator $Authenticator
     * 
     * @access public
     * @return bool  
     **/
    public function addAuthenticator (qcREST_Interface_Authenticator $Authenticator) {
      if (!in_array ($Authenticator, $this->Authenticators, true))
        $this->Authenticators [] = $Authenticator;
      
      return true;
    }
    // }}}
    
    // {{{ addAuthorizer
    /**
     * Register a new authorizer on this controller
     * 
     * @param qcREST_Interface_Authorizer $Authorizer
     * 
     * @access public
     * @return bool
     **/
    public function addAuthorizer (qcREST_Interface_Authorizer $Authorizer) {
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
    public static function httpHeaderParameters ($Data) {
      // Create an empty set of parameters
      $Parameters = array ();
      
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
        return false;
      
      // Return the result
      return $Parameters;
    }
    // }}}
    
    // {{{ handle
    /**
     * Try to process a request, if no request is given explicitly try to fetch one from SAPI
     * 
     * @param callable $Callback
     * @param mixed $Private (optional)
     * @param qcREST_Interface_Request $Request (optional)
     * 
     * The callback will be raised in the form of:
     * 
     *   function (qcREST_Interface_Controller $Self, qcREST_Interface_Request $Request = null, qcREST_Interface_Response $Response = null, bool $Status, mixed $Private = null) { }
     * 
     * @access public
     * @return void
     **/
    public function handle (callable $Callback, $Private = null, qcREST_Interface_Request $Request = null) {
      // Make sure we have a request-object
      if (($Request === null) && ((($Request = $this->getRequest ()) === null) || !($Request instanceof qcREST_Interface_Request))) {
        trigger_error ('Could not retrive request');
        
        call_user_func ($Callback, $this, null, null, $Private);
        
        return false;
      }
      
      // Make sure we have a root-element assigned
      if (!$this->Root)
        return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, null, $Callback, $Private);
      
      // Try to authenticate the request
      return $this->authenticateRequest ($Request)->then (
        // Authentication was successfull
        function ($User) use ($Request) {
          // Forward the authenticated user to Request
          if ($User instanceof qcEntity_Card)
            $Request->setUser ($User);
          
          return $User;
        },
        
        // Authentication failed
        function () use ($Request, $Callback, $Private) {
          // Bail out an error
          if (defined ('QCREST_DEBUG'))
            trigger_error ('Authentication failure');
          
          // Forward the result
          return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_UNAUTHENTICATED, null, $Callback, $Private);
        }
      
      // Proceed to resolve URI
      )->then (function () use ($Request, $Callback, $Private) {
        // Try to resolve to a resource
        return $this->resolveURI (
          $Request->getURI (),
          $Request,
          function (qcREST_Interface_Controller $Self, qcREST_Interface_Resource $Resource = null, qcREST_Interface_Collection $Collection = null, $Segment = null)
          use ($Request, $Callback, $Private) {
            return $this->authorizeRequest ($Request, $Resource, $Collection)->then (
              // Authorization was successfull
              function () use ($Request, $Resource, $Collection, $Segment, $Callback, $Private) {
                // Retrive default headers just for convienience
                if ($Collection || $Resource)
                  $Headers = $this->getDefaultHeaders ($Request, ($Collection ? $Collection : $Resource));
                else
                  $Headers = array ();

                // Check if there is a request-body
                if (($cType = $Request->getContentType ()) !== null) {
                  // Make sure we have a processor for this
                  if (!is_object ($inputProcessor = $this->getProcessor ($cType))) {
                    if (defined ('QCREST_DEBUG'))
                      trigger_error ('No input-processor for content-type ' . $cType);

                    return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_UNSUPPORTED, $Headers, $Callback, $Private);
                  }
                } else
                  $inputProcessor = null;

                // Find a suitable processor for the response
                $outputProcessor = null;

                foreach ($Request->getAcceptedContentTypes () as $Mimetype)
                  if ($outputProcessor = $this->getProcessor ($Mimetype))
                    break;

                if (!is_object ($outputProcessor))
                  return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NO_FORMAT, $Headers, $Callback, $Private);

                // Check if we should expect a request-body
                if (in_array ($Request->getMethod (), array (qcREST_Interface_Request::METHOD_POST, qcREST_Interface_Request::METHOD_PUT, qcREST_Interface_Request::METHOD_PATCH))) {
                  // Make sure we have an input-processor
                  if (!$inputProcessor) {
                    if (defined ('QCREST_DEBUG'))
                      trigger_error ('No input-processor for request found');

                    return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_ERROR, $Headers, $Callback, $Private);
                  }

                  // Make sure the request-body is present
                  elseif (($Input = $Request->getContent ()) === null)
                    return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_MISSING, $Headers, $Callback, $Private);

                  // Try to parse the request-body
                  elseif (!($Representation = $inputProcessor->processInput ($Input, $cType, $Request)))
                    return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_ERROR, $Headers, $Callback, $Private);

                // ... or fail if there is content on the request
                } elseif (strlen ($Request->getContent ()) > 0) {
                  if (defined ('QCREST_DEBUG'))
                    trigger_error ('Content on request where none is expected');

                  return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_ERROR, $Headers, $Callback, $Private);

                // Just make sure $Representation is set
                } else
                  $Representation = null;

                // Check if the resolver found a collection
                if ($Collection !== null)
                  return $this->handleCollectionRequest ($Resource, $Collection, $Request, $Representation, $outputProcessor, $Segment, $Callback, $Private);

                // Check if the resource found a resource
                if ($Resource !== null)
                  return $this->handleResourceRequest ($Resource, $Request, $Representation, $outputProcessor, $Callback, $Private);

                // Return if the resolver did not find anything
                return $this->respondStatus (
                  $Request,
                  ($Request->getMethod () == $Request::METHOD_OPTIONS ? qcREST_Interface_Response::STATUS_OK : qcREST_Interface_Response::STATUS_NOT_FOUND),
                  $Headers,
                  $Callback,
                  $Private
                );
              },
              
              // Authorization failed
              function () use ($Request, $Callback, $Private) {
                // Bail out an error
                if (defined ('QCREST_DEBUG'))
                  trigger_error ('Authorization failed');
                
                // Forward the result
                return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_UNAUTHORIZED, null, $Callback, $Private);
              }
            );
          }
        ); // resolveURI()
      }); // authenticateRequest()
    }
    // }}}
    
    // {{{ resolveURI
    /**
     * Try to resolve a given URI to a REST-Resource or REST-Collection
     * 
     * @param string $URI The actual URI to resolve
     * @param qcREST_Interface_Request $Request (optional)
     * @param callable $Callback A callback to raise once the URI was resolved
     * @param mixed $Private (optional) Any private data to pass to the callback
     * 
     * The callback will be raised in the form of
     * 
     *   function (qcREST_Interface_Controller $Self, qcREST_Interface_Resource $Resource = null, qcREST_Interface_Collection $Collection = null, string $Segment = null, mixed $Private = null) { }
     * 
     * After a successfull resolve $Resource will be filled with a valid REST-Resource.
     * If the URI points to a collection $Collection will be filled either.
     * If the URI could NOT be resolved completly, but the last point from the
     * successfull resolve allows to created subsequent elements, $Collection will be
     * filled with the last collection.
     * 
     * @access private
     * @return void
     **/
    private function resolveURI ($URI, qcREST_Interface_Request $Request = null, callable $Callback, $Private = null) {
      // Make sure we have a root assigned
      if ($this->Root === null)
        return call_user_func ($Callback, $this, null, null, null, $Private);
      
      // Check if this is a request for our root
      if (strlen ($URI) == 0)
        return call_user_func ($Callback, $this, $this->Root, null, null, $Private);
      
      $iPath = explode ('/', substr ($URI, 1));
      $lPath = count ($iPath);
      $Path = array ();
      
      for ($i = 0; $i < $lPath; $i++)
        if (($i == 0) || ($i == $lPath - 1) || (strlen ($iPath [$i]) > 0))
          $Path [] = rawurldecode ($iPath [$i]);
      
      $rFunc = null;
      $rFunc = function ($Self, $Result, $Data = null) use ($Callback, $Private, $Request, &$Path, &$rFunc) {
        // We got a child-collection
        if (($Self instanceof qcREST_Interface_Resource) && ($Result instanceof qcREST_Interface_Collection) && ($Data === null)) {
          // Check wheter to lookup a further child
          if ((count ($Path) == 1) && (strlen ($Path [0]) == 0))
            return call_user_func ($Callback, $this, $Self, $Result, null, $Private);
          
          // Try to lookup the next child
          $Next = array_shift ($Path);
          
          return qcEvents_Promise::ensure ($Result->getChild ($Next, $Request))->then (
            function ($Child) use ($rFunc, $Result, $Self, $Next) {
              $rFunc ($Result, $Child, array ($Self, $Next));
            },
            function () use ($rFunc, $Result, $Self, $Next) {
              $rFunc ($Result, null, array ($Self, $Next));
            }
          );
          
        // A child of a collection was returned
        } elseif ($Result instanceof qcREST_Interface_Resource) {
          // Check if we reached the end
          if (count ($Path) == 0)
            return call_user_func ($Callback, $this, $Result, null, null, $Private);
          
          // Try to retrive the child-collection of this resource
          return qcEvents_Promise::ensure ($Result->getChildCollection ())->then (
            function (qcREST_Interface_Collection $Collection) use ($Result, $rFunc) { $rFunc ($Result, $Collection); },
            function () use ($Result, $rFunc) { $rFunc ($Result, null); }
          );
          
        // A child could not be returned
        } elseif ($Self instanceof qcREST_Interface_Collection) {
          // Check wheter we should create a new child by that name
          # TODO: Create intermediate children if count($Path)>0   
          if ((count ($Path) != 0) || !($Request->getMethod () == $Request::METHOD_PUT))
            return call_user_func ($Callback, $this, null, null, null, $Private);
          
          return call_user_func ($Callback, $this, $Data [0], $Self, $Data [1], $Private);
        
        // A collection was not available
        } else {
          # TODO?
          #if ((count ($Path) > 0) && ($Request->getMethod () == $Request::METHOD_PUT))
          #  return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          
          # return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_FOUND, null, $Callback, $Private);
          return call_user_func ($Callback, $this, null, null, null, $Private);
        }
      }; 
      
      return qcEvents_Promise::ensure ($this->Root->getChildCollection ())->then (
        function (qcREST_Interface_Collection $Collection) use ($rFunc) { $rFunc ($this->Root, $Collection); },
        function () use ($rFunc) { $rFunc ($this->Root, null); }
      );
    }
    // }}}
    
    // {{{ authenticateRequest
    /**
     * Try to authenticate a given request
     * 
     * @param qcREST_Interface_Request $Request
     * 
     * @access private
     * @return qcEvents_Promise A promise that resolves to a qcEntity_Card-Instance or NULL
     **/
    private function authenticateRequest (qcREST_Interface_Request $Request) : qcEvents_Promise {
      // Check if there are authenticators to process
      if (count ($this->Authenticators) == 0)
        return qcEvents_Promise::resolve (null);
      
      // Call all authenticators
      $Authenticators = $this->Authenticators;
      
      foreach ($Authenticators as $Authenticator)
        $Promises [] = $Authenticator->authenticateRequest ($Request);
      
      return qcEvents_Promise::all ($Promises)->then (function ($Results) {
        // Check if any user was found
        foreach ($Results as $User)
          if ($User instanceof qcEntity_Card)
            return $User;
        
        // ... or be a bit laisser faire
        return null;
      });
    }
    // }}}
    
    // {{{ authorizeRequest
    /**
     * Try to authorize a request
     * 
     * @param qcREST_Interface_Request $Request
     * @param qcREST_Interface_Resource $Resource (optional)
     * @param qcREST_Interface_Collection $Collection (optional)
     * 
     * @access private
     * @return qcEvents_Promise
     **/
    private function authorizeRequest (qcREST_Interface_Request $Request, qcREST_Interface_Resource $Resource = null, qcREST_Interface_Collection $Collection = null) : qcEvents_Promise {
      // Check if there are authenticators to process
      if (count ($this->Authorizers) == 0)
        return qcEvents_Promise::resolve (true);
      
      // Call all authorizers
      $Authorizers = $this->Authorizers;
      $Promises = array ();
      
      foreach ($Authorizers as $Authorizer)
        $Promises [] = $Authorizer->authorizeRequest ($Request, $Resource, $Collection);
      
      return qcEvents_Promise::all ($Promises)->then (function () { return true; });
    }
    // }}}
    
    // {{{ getAuthorizedMethods
    /**
     * Retrive authorized methods for a resource or collection
     * 
     * @param qcREST_Interface_Resource $Resource
     * @param qcREST_Interface_Collection $Collection (optional)
     * @param qcREST_Interface_Request $Request (optional)
     *    
     * @access public
     * @return qcEvents_Promise
     **/
    public function getAuthorizedMethods (qcREST_Interface_Resource $Resource, qcREST_Interface_Collection $Collection = null, qcREST_Interface_Request $Request = null) : qcEvents_Promise {
      // Check if there are authenticators to process
      if (count ($this->Authorizers) == 0)
        return qcEvents_Promise::resolve (array (qcREST_Interface_Request::METHOD_GET, qcREST_Interface_Request::METHOD_POST, qcREST_Interface_Request::METHOD_PUT, qcREST_Interface_Request::METHOD_PATCH, qcREST_Interface_Request::METHOD_DELETE, qcREST_Interface_Request::METHOD_OPTIONS, qcREST_Interface_Request::METHOD_HEAD));
      
      // Call all authorizers
      $Authorizers = $this->Authorizers;
      $Promises = array ();
      
      foreach ($Authorizers as $Authorizer)
        $Promises [] = $Authorizer->getAuthorizedMethods ($Resource, $Collection, $Request);
      
      return qcEvents_Promise::all ($Promises)->then (function ($Results) {
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
      });
    }
    // }}}
    
    // {{{ handleResourceRequest
    /**
     * Handle a request on a normal resource
     * 
     * @param qcREST_Interface_Resource $Resource
     * @param qcREST_Interface_Request $Request
     * @param qcREST_Interface_Representation $Representation (optional)
     * @param qcREST_Interface_Processor $outputProcessor
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * @access private
     * @return void
     **/
    private function handleResourceRequest (qcREST_Interface_Resource $Resource, qcREST_Interface_Request $Request, qcREST_Interface_Representation $Representation = null, qcREST_Interface_Processor $outputProcessor, callable $Callback, $Private = null) {
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
              if (defined ('QCREST_DEBUG'))
                trigger_error ('Resource is unsure if it is readable');
              
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_UNAUTHENTICATED, null, $Callback, $Private);
            }
            
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Resource is not readable');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, $Headers, $Callback, $Private);
          }
          
          // Retrive the attributes
          return qcEvents_Promise::ensure ($Resource->getRepresentation ($Request))->then (
            function (qcREST_Interface_Representation $Representation)
            use ($Resource, $Request, $Headers, $outputProcessor, $Callback, $Private) {
              return $this->handleRepresentation ($Request, $Resource, null, $Representation, $outputProcessor, qcREST_Interface_Response::STATUS_OK, $Headers, $Callback, $Private);
            },
            function () use ($Request, $Headers, $Callback, $Private) {
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, $Headers, $Callback, $Private);
            }
          );
        
        // Check if a new sub-resource is requested
        case $Request::METHOD_POST:
          // Convert the request into a directory-request if possible
          return qcEvents_Promise::ensure ($Resource->getChildCollection ())->then (
            function (qcREST_Interface_Collection $Collection) use ($Resource, $Request, $Representation, $outputProcessor, $Callback, $Private) {
              return $this->handleCollectionRequest ($Resource, $Collection, $Request, $Representation, $outputProcessor, null, $Callback, $Private);
            },
            function () use ($Request, $Headers, $Callback, $Private) {
              // Bail out an error
              if (defined ('QCREST_DEBUG'))
                trigger_error ('No child-collection');
              
              // Forward the result
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, $Headers, $Callback, $Private);
            }
          );
        
        // Change attributes
        case $Request::METHOD_PUT:
          // Make sure this is allowed  
          if (($rc = $Resource->isWritable ($Request->getUser ())) !== true) {
            if (($rc === null) && ($Request->getUser () === null)) {
              if (defined ('QCREST_DEBUG'))
                trigger_error ('Resource is unsure wheter it is writable');
              
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_UNAUTHENTICATED, null, $Callback, $Private);
            }
            
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Resource is not writable');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, $Headers, $Callback, $Private);
          }
          
          return qcEvents_Promise::ensure ($Resource->setRepresentation ($Representation, $Request))->then (
            function (qcREST_Interface_Representation $Representation) use ($Resource, $Request, $outputProcessor, $Headers, $Callback, $Private) {
              // Check wheter to just pass the result (default)
              if (!$this->alwaysRepresentation || ($Resource->isReadable ($Request->getUser ()) !== true))
                return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_STORED, $Headers, $Callback, $Private);
              
              // Forward the representation
              return $this->handleRepresentation ($Request, $Resource, null, $Representation, $outputProcessor, qcREST_Interface_Response::STATUS_OK, $Headers, $Callback, $Private);
            },
            function ($Representation = null) use ($Resource, $Request, $outputProcessor, $Headers, $Callback, $Private) {
              // Use representation if there is a negative status on it
              if ($Representation instanceof qcREST_Interface_Representation)
                return $this->handleRepresentation ($Request, $Resource, null, $Representation, $outputProcessor, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, $Headers, $Callback, $Private);
              
              // Push back an error
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, $Headers, $Callback, $Private);
            }
          );
        case $Request::METHOD_PATCH:
          // Make sure this is allowed
          if (($rc = $Resource->isWritable ($Request->getUser ())) !== true) {
            if (($rc === null) && ($Request->getUser () === null))
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_UNAUTHENTICATED, null, $Callback, $Private);
            
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Resource is not writable (will not patch)');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, $Headers, $Callback, $Private);
          }
          
          // Retrive the attributes first
          return qcEvents_Promise::ensure ($Resource->getRepresentation ($Request))->then (
            function (qcREST_Interface_Representation $currentRepresentation)
            use ($Resource, $Request, $Representation, $Headers, $outputProcessor, $Callback, $Private) {
              // Update Representation
              $requireAttributes = false;
              
              foreach ($Representation as $Key=>$Value)
                if (!$requireAttributes || isset ($currentRepresentation [$Key])) {
                  $currentRepresentation [$Key] = $Value;
                  
                  unset ($Representation [$Key]);
                } else
                  return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_ERROR, $Headers, $Callback, $Private);
              
              // Try to update the resource's attributes
              return qcEvents_Promise::ensure ($Resource->setRepresentation ($currentRepresentation, $Request))->then (
                function (qcREST_Interface_Representation $Representation)
                use ($Request, $Resource, $outputProcessor, $Headers, $Callback, $Private) {
                  # TODO: Return representation here?
                  return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_STORED, $Headers, $Callback, $Private);
                  
                  // Check wheter to just pass the result (default)
                  if (!$this->alwaysRepresentation || ($Resource->isReadable ($Request->getUser ()) !== true))
                    return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_STORED, $Headers, $Callback, $Private);
                  
                  // Forward the representation
                  return $this->handleRepresentation ($Request, $Resource, null, $Representation, $outputProcessor, qcREST_Interface_Response::STATUS_OK, $Headers, $Callback, $Private);
                },
                function ($Representation = null) use ($Request, $Resource, $outputProcessor, $Headers, $Callback, $Private) {
                  // Use representation if there is a negative status on it
                  if ($Representation instanceof qcREST_Interface_Representation)
                    return $this->handleRepresentation ($Request, $Resource, null, $Representation, $outputProcessor, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, $Headers, $Callback, $Private);
                  
                  // Give a normal bad reply if representation does not work
                  return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, $Headers, $Callback, $Private);
                }
              );
            },
            function () use ($Request, $Headers, $Callback, $Private) {
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, $Headers, $Callback, $Private);
            }
          );
        
        // Remove this resource
        case $Request::METHOD_DELETE:
          // Make sure this is allowed
          if (($rc = $Resource->isRemovable ($Request->getUser ())) !== true) {
            if (($rc === null) && ($Request->getUser () === null))
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_UNAUTHENTICATED, null, $Callback, $Private);
            
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Resource may not be removed');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, $Headers, $Callback, $Private);
          }
          
          // Try to remove the resource
          return qcEvents_Promise::ensure ($Resource->remove ())->then (
            function () use ($Request, $Headers, $Callback, $Private) {
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_REMOVED, $Headers, $Callback, $Private);
            },
            function () use ($Request, $Headers, $Callback, $Private) {
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, $Headers, $Callback, $Private);
            }
          );
        // Output Meta-Information for this resource
        case $Request::METHOD_OPTIONS:
          // Try to get child-collection
          return qcEvents_Promise::ensure ($Resource->getChildCollection ())->then (
            function (qcREST_Interface_Collection $Collection) use ($Request, $User, $Resource, $Headers, $Callback, $Private) {
              if ($Collection->isWritable ($User))
                $Headers ['Access-Control-Allow-Methods'] = array_unique (array_merge ($this->getAllowedMethods ($Request, $Resource), $this->getAllowedMethods ($Request, $Collection)));
              
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_OK, $Headers, $Callback, $Private);
            },
            function () use ($Request, $Headers, $Callback, $Private) {
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_OK, $Headers, $Callback, $Private);
            }
          );
      }
      
      return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_UNSUPPORTED, $Headers, $Callback, $Private);
    }
    // }}}
    
    // {{{ handleCollectionRequest
    /**
     * Process a request targeted at a directory-resource
     * 
     * @param qcREST_Interface_Resource $Resource
     * @param qcREST_Interface_Collection $Collection
     * @param qcREST_Interface_Request $Request
     * @param qcREST_Interface_Representation $Representation (optional)
     * @param qcREST_Interface_Processor $outputProcessor
     * @param string $Segment (optional)
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * @access private
     * @return bool
     **/
    private function handleCollectionRequest (qcREST_Interface_Resource $Resource, qcREST_Interface_Collection $Collection, qcREST_Interface_Request $Request, qcREST_Interface_Representation $Representation = null, qcREST_Interface_Processor $outputProcessor, $Segment = null, callable $Callback, $Private = null) {
      // Retrive default headers
      $Headers = $this->getDefaultHeaders ($Request, $Collection);
      
      // Retrive the requested method
      $Method = $Request->getMethod ();
      
      // Check if there was a segment left on the request
      if ($Segment !== null) {
        // Only allow segment on PUT
        if ($Method != $Request::METHOD_PUT)
          return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_FOUND, $Headers, $Callback, $Private);
        
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
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_UNAUTHENTICATED, null, $Callback, $Private);
            
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Collection is not browsable');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, $Headers, $Callback, $Private);
          }
          
          $Headers ['X-Resource-Type'] = 'Collection';
          $Headers ['X-Collection-Class'] = get_class ($Collection);
          
          // Save some time on HEAD-Requests
          if ($Method == $Request::METHOD_HEAD)
            return $this->respondStatus (
              $Request,
              qcREST_Interface_Response::STATUS_OK,
              $Headers,
              $Callback,
              $Private
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
              $Order = qcREST_Interface_Collection_Extended::SORT_ORDER_ASCENDING;
            else
              $Order = qcREST_Interface_Collection_Extended::SORT_ORDER_DESCENDING;
          } else
            $Sort = $Order = null;
          
          // Handle searching
          if (isset ($rParams ['search']) && (strlen ($rParams ['search']) > 0))
            $Search = strval ($rParams ['search']);
          else
            $Search = null;
          
          // Check if the collection supports extended queries
          if ($Collection instanceof qcREST_Interface_Collection_Extended) {
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
          return qcEvents_Promise::ensure ($Collection->getChildren ($Request))->then (
            function (array $Children = null, qcREST_Interface_Representation $Representation = null)
            use ($Collection, $Request, $Resource, $outputProcessor, $Headers, $First, $Last, $Sort, $Search, $User, $Order, $Callback, $Private) {
              // Prepare representation
              if (!$Representation)
                $Representation = new qcREST_Representation;
              
              $Representation ['type'] = 'listing';
              
              // Determine the total number of children
              if ($Collection instanceof qcREST_Interface_Collection_Extended)
                $Representation ['total'] = $Collection->getChildrenCount ();
              else
                $Representation ['total'] = count ($Children);
              
              // Make sure that collection-parameters are reset
              if ($Collection instanceof qcREST_Interface_Collection_Extended)
                $Collection->resetParameters ();
              
              // Determine the base-URI
              $baseURI = $this->getURI ();  
              
              if (substr ($baseURI, -1, 1) == '/')
                $baseURI = substr ($baseURI, 0, -1);
              
              // Prepare the promise-queue
              $Promises = array ();
              
              // Determine how to present children on the listing
              if (is_callable (array ($Collection, 'getChildFullRepresenation')))
                $Extend = $Collection->getChildFullRepresenation ();
              else
                $Extend = false;
              
              // Bail out a warning if we use pagination here
              if (($First > 0) || ($Last !== null))
                $Representation->addMeta ('X-Pagination-Performance-Warning', 'Using pagination without support on backend');
              
              // Append children to the listing
              $Representation ['idAttribute'] = $Collection->getNameAttribute ();
              $Items = array ();
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
                $Items [] = $Item = new stdClass;
                $Item->_id = $Child->getName ();
                $Item->_href = $baseURI . $this->getEntityURI ($Child);
                $Item->_collection = $Child->hasChildCollection ();
                $Item->_permissions = new stdClass;
                $Item->_permissions->read = $Child->isReadable ($User);
                $Item->_permissions->write = $Child->isWritable ($User);
                $Item->_permissions->delete = $Child->isRemovable ($User);
                
                // Ask authorizers for permissions
                $Promises [] = $Request->getController ()->getAuthorizedMethods ($Resource, null, $Request)->then (
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
                  $Promises [] = qcEvents_Promise::ensure ($Resource->getChildCollection ())->then (
                    function (qcREST_Interface_Collection $Collection)
                    use ($Resource, $Item, $Request, $User) {
                      // Patch in default rights
                      $Item->_permissions->collection = new stdClass;
                      $Item->_permissions->collection->browse = $Collection->isBrowsable ($User);
                      $Item->_permissions->collection->write = $Collection->isWritable ($User);
                      $Item->_permissions->collection->delete = $Collection->isRemovable ($User);
                      
                      return $Request->getController ()->getAuthorizedMethods ($Resource, $Collection, $Request)->then (
                        function ($Grants) use ($Item, $Request) {
                          // Patch collection rights
                          $Item->_permissions->collection->browse = $Item->_permissions->collection->browse && in_array ($Request::METHOD_GET, $Grants);
                          $Item->_permissions->collection->write  = $Item->_permissions->collection->write && (in_array ($Request::METHOD_POST, $Grants) ||
                                                                                                               in_array ($Request::METHOD_PUT, $Grants)  ||
                                                                                                               in_array ($Request::METHOD_PATCH, $Grants));
                          $Item->_permissions->collection->delete = $Item->_permissions->collection->delete && in_array ($Request::METHOD_DELETE, $Grants);
                        },
                        function () { }
                      );
                    },
                    function () { }
                  );
                
                // Store the children on the representation
                // We do this more often as the callback-function (below) relies on this
                $Representation ['items'] = $Items;
                
                // Check wheter to expand the child
                if (!(($Aware = ($Child instanceof qcREST_Interface_Collection_Representation)) || $Extend))
                  continue;
                
                // Expand the child
                if ($Aware)
                  $Promise = $Child->getCollectionRepresentation ($Request);
                else
                  $Promise = $Child->getRepresentation ($Request);
                
                $Promises [] = qcEvents_Promise::ensure ($Promise)->then (
                  function (qcREST_Interface_Representation $Representation) use ($Item) {
                    // Patch item on representation
                    foreach ($Representation as $Key=>$Value)
                      if (!in_array ($Key, array ('_id', '_href', '_collection', '_permissions')))
                        $Item->$Key = $Value;
                      else
                        trigger_error ('Skipping reserved key ' . $Key);
                  },
                  function () { }
                );
              }
              
              return qcEvents_Promise::all ($Promises)->finally (
                function ()
                use ($Request, $Resource, $Representation, $Headers, $outputProcessor, $Collection, $Callback, $Private, $First, $Last, $Search, $Sort, $Order) {
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
                      $Keys = array ();
                      
                      foreach ($Items as $Item) {
                        if (isset ($Item->$Sort))
                          $Key = $Item->$Sort;
                        else
                          $Key = ' ';
                        
                        if (isset ($Keys [$Key]))
                          $Keys [$Key][] = $Item;
                        else
                          $Keys [$Key] = array ($Item);
                      }
                      
                      // Sort the index
                      if ($Order == qcREST_Interface_Collection_Extended::SORT_ORDER_DESCENDING)
                        krsort ($Keys);
                      else
                        ksort ($Keys);
                      
                      // Push back the result
                      $Items = array ();
                      
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
                    qcREST_Interface_Response::STATUS_OK,
                    $Headers,
                    $Callback, $Private
                  );
                }
              );
            },
            function ($Representation) use ($Collection, $Request, $Resource, $outputProcessor, $Headers, $Callback, $Private) {
              // Make sure Representation is valid
              if (!($Representation instanceof qcREST_Interface_Representation))
                $Representation = null;
              
              // Make sure that collection-parameters are reset
              if ($Collection instanceof qcREST_Interface_Collection_Extended)
                $Collection->resetParameters ();
              
              // Bail out an error
              if (defined ('QCREST_DEBUG'))
                trigger_error ('Failed to retrive the children');
              
              // Callback our parent
              if ($Representation)
                return $this->handleRepresentation ($Request, $Resource, $Collection, $Representation, $outputProcessor, qcREST_Interface_Response::STATUS_ERROR, $Headers, $Callback, $Private);
              
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, $Headers, $Callback, $Private);
            }
          );
        
        // Create a new resource on this directory
        case $Request::METHOD_POST:
          // Make sure this is allowed
          if (($rc = $Collection->isWritable ($Request->getUser ())) !== true) {
            if (($rc === null) && ($Request->getUser () === null)) {
              if (defined ('QCREST_DEBUG'))
                trigger_error ('Collection is unsure if it is writable');
              
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_UNAUTHENTICATED, null, $Callback, $Private);
            }
            
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Collection is not writable');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, $Headers, $Callback, $Private);
          }
          
          return qcEvents_Promise::ensure ($Collection->createChild ($Representation, $Segment, $Request))->then (
            function (qcREST_Interface_Resource $Child, qcREST_Interface_Representation $Representation = null)
            use ($Request, $Resource, $Collection, $outputProcessor, $Headers, $Callback, $Private) {
              // Create URI for newly created child
              $Headers ['Location'] = $URI = $this->getURI ($Child);
              
              // Process the response
              if ($Representation)
                return $this->handleRepresentation ($Request, $Child, null, $Representation, $outputProcessor, qcREST_Interface_Response::STATUS_CREATED, $Headers, $Callback, $Private);
              
              // Check wheter to just pass the result (default)
              if (!$this->alwaysRepresentation || ($Child->isReadable ($Request->getUser ()) !== true))
                return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_STORED, $Headers, $Callback, $Private);
              
              return qcEvents_Promise::ensure ($Child->getRepresentation ($Request))->then (
                function (qcREST_Interface_Representation $Representation)
                use ($Request, $Resource, $Headers, $outputProcessor, $Callback, $Private) {
                  return $this->handleRepresentation ($Request, $Resource, null, $Representation, $outputProcessor, qcREST_Interface_Response::STATUS_CREATED, $Headers, $Callback, $Private);
                },
                function () use ($Request, $Headers, $Callback, $Private) {
                  return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CREATED, $Headers, $Callback, $Private);
                }
              );
            },
            function ($Representation = null) use ($Request, $Resource, $Collection, $outputProcessor, $Headers, $Callback, $Private) {
              // Bail out an error in debug-mode
              if (defined ('QCREST_DEBUG'))
                trigger_error ('Failed to create child');
              
              // Forward the response
              if ($Representation instanceof qcREST_Interface_Representation)
                return $this->handleRepresentation ($Request, $Resource, $Collection, $Representation, $outputProcessor, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, $Headers, $Callback, $Private);
              
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, $Headers, $Callback, $Private);
            }
          );
        
        // Replace all resources on this directory (PUT) with new ones or just add a new set (PATCH)
        case $Request::METHOD_PUT:
          // Tell later code that we want to remove items
          $Removals = array ();
          
        case $Request::METHOD_PATCH:
          // Make sure this is allowed
          if (($rc = $Collection->isWritable ($Request->getUser ())) !== true) {
            if (($rc === null) && ($Request->getUser () === null)) {
              if (defined ('QCREST_DEBUG'))
                trigger_error ('Collection is unsure if it is writable');
              
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_UNAUTHENTICATED, null, $Callback, $Private);
            }
            
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Collection is not writable and contents may not be replaced (PUT) or patched (PATCH)');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, $Headers, $Callback, $Private);
          }
          
          // Just check if we are in patch-mode ;-)
          if (!isset ($Removals)) {
            $Removals = null;
            
            if ($Collection instanceof qcREST_Interface_Collection_Extended)
              $Collection->setNames (array_keys ($Representation->toArray ()));
          }
          
          // Request the children of this resource
          return $Collection->getChildren (
            function (qcREST_Interface_Collection $Collection, array $Children = null)
            use ($Removals, $Request, $Resource, $Representation, $Headers, $outputProcessor, $Callback, $Private) {
              // Check if the call was successfull 
              if ($Children === null) {
                trigger_error ('Failed to retrive the children');
                
                return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, $Headers, $Callback, $Private);
              }
              
              // Split children up into updates and removals
              $Create = $Representation;
              $Updates = array ();
              
              foreach ($Children as $Child)
                // Check if the child is referenced on input-attributes
                if (isset ($Representation [$Name = $Child->getName ()])) {
                  // Mark the child as updated
                  $Updates [$Name] = $Child;
                  
                // Enqueue it for removal (chilren will only be removed if the request is of method PUT)
                } elseif ($Removals !== null)
                  $Removals [] = $Child;
              
              $func = null;
              $func = function ($Self = null, $P1 = null, $P2 = null) use ($Request, $outputProcessor, $Resource, &$Create, &$Updates, &$Removals, $Representation, $Headers, &$func, $Callback, $Private) {
                // Check if we are returning
                if ($Self) {
                  // Check if we tried to create a child
                  if ($Self === $this) {
                    $Child = $P1;
                    $cRepresentation = $P2;
                    
                    if (!$Child)
                      return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, $Headers, $Callback, $Private);
                  
                  // Check if we tried to update a child-resource
                  } elseif (is_array ($P1)) {
                    $setAttributes = &$P1;
                    $Status = &$P2;
                    
                    if (!$Status)
                      return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, $Headers, $Callback, $Private);
                    
                  // Treat anything else as removal
                  } else {
                    $Status = &$P1;
                    
                    if (!$Status)
                      return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, $Headers, $Callback, $Private);
                  }
                }
                
                // Try to update pending children
                foreach ($Updates as $Name=>$Child) {
                  // Create a child-representation for the update
                  $childRepresentation = new qcREST_Representation (is_object ($Representation [$Name]) ? get_object_vars ($Representation [$Name]) : $Representation [$Name]);
                  
                  // Remove from queue
                  unset ($Updates [$Name], $Create [$Name]);
                  
                  // Check if we are PATCHing and should *really* PATCH
                  if (($Removals === null) && (!defined ('QCREST_PATCH_ON_COLLECTION_PATCHES_RESOURCES') || QCREST_PATCH_ON_COLLECTION_PATCHES_RESOURCES))
                    return qcEvents_Promise::ensure ($Child->getRepresentation ($Request))->then (
                      function (qcREST_Interface_Representation $currentRepresentation)
                      use ($Child, $func, $childRepresentation, $Request) {
                        // Update Representation   
                        $requireAttributes = false;
                        
                        foreach ($childRepresentation as $Key=>$Value)
                          if (!$requireAttributes || isset ($currentRepresentation [$Key])) {
                            $currentRepresentation [$Key] = $Value;
                            unset ($childRepresentation [$Key]);
                          } else
                            return call_user_func ($func, $Child, $currentRepresentation, false);
                        
                        // Forward the update
                        return qcEvents_Promise::ensure ($Child->setRepresentation ($currentRepresentation, $Request))->then (
                          function (qcREST_Interface_Representation $childRepresentation)
                          use ($func, $Child) {
                            return call_user_func ($func, $Child, $childRepresentation, true);
                          },
                          function () use ($func, $Child, $childRepresentation) {
                            return call_user_func ($func, $Child, $childRepresentation, false);
                          }
                        );
                      },
                      function () use ($func, $Child, $childRepresentation) {
                        return call_user_func ($func, $Child, $childRepresentation, false);
                      }
                    );
                  
                  // Treat the update as a complete Representation
                  return qcEvents_Promise::ensure ($Child->setRepresentation ($childRepresentation, $Request))->then (
                    function (qcREST_Interface_Representation $childRepresentation)
                    use ($func, $Child) {
                      return call_user_func ($func, $Child, $childRepresentation, true);
                    },
                    function () use ($func, $Child, $childRepresentation) {
                      return call_user_func ($func, $Child, $childRepresentation, false);
                    }
                  );
                }
                
                // Try to create pending children
                foreach ($Create as $Name=>$childAttributes) {
                  unset ($Create [$Name]);
                  
                  return qcEvents_Promise::ensure ($Resource->createChild (new qcREST_Representation ($childAttributes), $Name, $Request))->then (
                    function (qcREST_Interface_Resource $Child, qcREST_Interface_Representation $Representation = null)
                    use ($Resource, $func) {
                      $func ($Resource, $Child, $Representation);
                    },
                    function () use ($Resource, $func) { $func ($Resource, null, null); }
                  );
                }
                
                // Try to remove removals
                if ($Removals !== null)
                  foreach ($Removals as $Name=>$Child) {
                    unset ($Removals [$Name]);
                    
                    return qcEvents_Promise::ensure ($Child->remove ())->then (
                      function () use ($Child, $func) { $func ($Child, true); },
                      function () use ($Child, $func) { $func ($Child, false); }
                    );
                  }
                
                // If we get here, we were finished
                # TODO: Honor alwaysRepresentation here?!
                return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_STORED, $Headers, $Callback, $Private);
              };
              
              // Dispatch to update-function
              return call_user_func ($func);
            }, null,
            $Request
          );
        
        // Delete the entire collection
        case $Request::METHOD_DELETE:
          // Make sure this is allowed
          if (($rc = $Collection->isRemovable ($Request->getUser ())) !== true) {
            if (($rc === null) && ($Request->getUser () === null))
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_UNAUTHENTICATED, null, $Callback, $Private);
            
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Collection may not be removed');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, $Headers, $Callback, $Private);
          }
          
          return qcEvents_Promise::ensure ($Collection->remove ())->then (
            function () use ($Request, $Headers, $Callback, $Private) {
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_OK, $Headers, $Callback, $Private);
            },
            function () use ($Request, $Headers, $Callback, $Private) {
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, $Headers, $Callback, $Private);
            }
          );
        // Output Meta-Information for this resource
        case $Request::METHOD_OPTIONS:
          // Return the status
          return $this->respondStatus (
            $Request,
            qcREST_Interface_Response::STATUS_OK,
            $Headers,
            $Callback,
            $Private
          );
      }
      
      return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_UNSUPPORTED, $Headers, $Callback, $Private);
    }
    // }}}
    
    // {{{ handleRepresentation
    /**
     * Process Representation and generate output
     * 
     * @param qcREST_Interface_Request $Request
     * @param qcREST_Interface_Resource $Resource
     * @param qcREST_Interface_Collection $Collection (optional)
     * @param qcREST_Interface_Representation $Representation
     * @param qcREST_Interface_Processor $outputProcessor
     * @param enum $Status
     * @param array $Meta (optional)
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * @access private
     * @return void
     **/
    private function handleRepresentation (
      qcREST_Interface_Request $Request,
      qcREST_Interface_Resource $Resource,
      qcREST_Interface_Collection $Collection = null,
      qcREST_Interface_Representation $Representation,
      qcREST_Interface_Processor $outputProcessor,
      $Status,
      array $Meta = null,
      callable $Callback, $Private = null
    ) {
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
        return $this->respondStatus ($Request, $Status, $Meta, $Callback, $Private);
      
      // Process the output
      return $outputProcessor->processOutput (
        function (qcREST_Interface_Processor $Processor, $Output, $OutputType, qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $Representation, qcREST_Interface_Request $Request, qcREST_Interface_Controller $Controller) use ($Callback, $Private, $Status, $Meta) {
          // Check if the processor returned an error
          if ($Output === false) {
            trigger_error ('Output-Processor failed');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, null, $Callback, $Private);
          }
          
          // Create a response-object
          if (!isset ($Meta ['X-Resource-Type']))
            $Meta ['X-Resource-Type'] = 'Resource';
          
          $Meta ['X-Resource-Class'] = get_class ($Resource);
          
          $Response = new qcREST_Response ($Request, $Status, $Output, $OutputType, $Meta);
          
          // Return the response
          return $this->sendResponse ($Response, $Callback, $Private);
        }, null,
        $Resource, $Representation, $Request, $this
      );
    }
    // }}}
    
    // {{{ sendResponse
    /**
     * Write out a response-object and raise the callback for handle()
     * 
     * @param qcREST_Interface_Response $Response
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * @access private
     * @return void
     **/
    private function sendResponse (qcREST_Interface_Response $Response, callable $Callback, $Private = null) {
      // Append some meta for unauthenticated status
      if ($Response->getStatus () == qcREST_Interface_Response::STATUS_CLIENT_UNAUTHENTICATED) {
        if ($Schemes = $Response->getMeta ('WWW-Authenticate'))
          $Schemes = (is_array ($Schemes) ? $Schemes : array ($Schemes));
        else
          $Schemes = array ();
        
        foreach ($this->Authenticators as $Authenticator)
          foreach ($Authenticator->getSchemes () as $aScheme) 
            if (isset ($aScheme ['scheme']))
              $Schemes [] = $aScheme ['scheme'] . ' realm="' . (isset ($aScheme ['realm']) ? $aScheme ['realm'] : get_class ($Authenticator)) . '"';
        
        $Response->setMeta ('WWW-Authenticate', $Schemes);
      }
      
      // Process the session
      if (($Request = $Response->getRequest ()) && $Request->hasSession ())
        return qcEvents_Promise::ensure ($Request->getSession ())->then (
          function (qcREST_Interface_Session $Session) use ($Response) {
            // Add the session to the response
            $Session->addToResponse ($Response);
            
            // Store the session
            return $Session->store ();
          }
        )->finally (
          function () use ($Response, $Callback, $Private) {
            // Forward the response and store the session
            return $this->setResponse (
              $Response,
              function (qcREST_Interface_Controller $Self, qcREST_Interface_Response $Response, $Status)
              use ($Callback, $Private) {
                call_user_func ($Callback, $this, $Request, $Response, $Status, $Private);
              }
            );
          }
        );
      
      // Forward the response
      return $this->setResponse (
        $Response,
        function (qcREST_Interface_Controller $Self, qcREST_Interface_Response $Response, $Status)
        use ($Request, $Callback, $Private) {
          call_user_func ($Callback, $this, $Request, $Response, $Status, $Private);
        }
      );
    }
    // }}}
    
    // {{{ respondStatus
    /**
     * Finish a request with a simple status
     * 
     * @param qcREST_Interface_Request $Request
     * @param enum $Status
     * @param array $Meta (optional)
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * @access private
     * @return void
     **/
    private function respondStatus (qcREST_Interface_Request $Request, $Status, array $Meta = null, callable $Callback, $Private = null) {
      // Make sure meta is valid
      if ($Meta === null)
        $Meta = array ();
      
      return $this->sendResponse (new qcREST_Response ($Request, $Status, null, null, $Meta), $Callback, $Private);
    }
    // }}}
    
    // {{{ getAllowedMethods
    /**
     * Retrive a set of allowed Verbs for a given Resource (Resource or collection)
     * 
     * @param qcREST_Interface_Request $Request
     * @param qcREST_Interface_Entity $Resource
     * 
     * @access private
     * @return array
     **/
    private function getAllowedMethods (qcREST_Interface_Request $Request, qcREST_Interface_Entity $Resource) {
      // Setup result
      $Methods = array ('OPTIONS');
      
      // Try to retrive a user for the request
      $User = $Request->getUser ();
      
      // Process allowed methods of a collection
      if ($Resource instanceof qcREST_Interface_Collection) {
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
      if ($Resource instanceof qcREST_Interface_Resource) {
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
     * @param qcREST_Interface_Request $Request
     * @param qcREST_Interface_Entity $Resource
     * 
     * @access private
     * @return array
     **/
    private function getDefaultHeaders (qcREST_Interface_Request $Request, qcREST_Interface_Entity $Resource) {
      return array (
        'Access-Control-Allow-Methods' => $this->getAllowedMethods ($Request, $Resource),
      );
    }
    // }}}
  }

?>