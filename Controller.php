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
  
  // Don't display PHP-Errors by default
  ini_set ('display_errors', 'Off');
  
  abstract class qcREST_Controller implements qcREST_Interface_Controller {
    /* REST-Resource to use as Root */
    private $Root = null;
    
    /* Registered Authenticators */
    private $Authenticators = array ();
    
    /* Registered Input/Output-Processors */
    private $Processors = array ();
    
    /* Mapping of mime-types to processors */
    private $typeMaps = array ();
    
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
        return array_shift (reset ($this->typeMaps [$Major]));
      
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
    
    // {{{ getURI
    /**
     * Retrive the URI of this controller
     * 
     * @access public
     * @return string
     **/
    abstract public function getURI ();
    // }}}
    
    // {{{ getRequest
    /**
     * Generate a Request-Object for a pending request
     * 
     * @access public
     * @return qcREST_Interface_Request
     **/
    abstract public function getRequest ();
    // }}}
    
    // {{{ setResponse
    /**
     * Write out a response for a previous request
     * 
     * @param qcREST_Interface_Response $Response
     * @param callable $Callback (optional) A callback to raise once the operation was completed
     * @param mixed $Private (optional) Any private data to pass to the callback
     * 
     * The callback will be raised once the operation was finished in the form of
     * 
     *   function (qcREST_Interface_Controller $Self, qcREST_Interface_Response $Response, bool $Status, mixed $Private) { }
     * 
     * @access public
     * @return bool
     **/
    abstract public function setResponse (qcREST_Interface_Response $Response, callable $Callback = null, $Private = null);
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
     * @return bool
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
        return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_SERVER_ERROR, null, $Callback, $Private);
      
      // Try to authenticate the request
      return $this->authenticateRequest ($Request, function (qcREST_Interface_Controller $Self, qcREST_Interface_Request $Request, $Status, qcVCard_Entity $User = null) use ($Callback, $Private) {
        // Stop if authentication failed
        if ($Status === false)
          return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_UNAUTHORIZED, null, $Callback, $Private);
        
        // Forward the authenticated user to Request
        if ($User !== null)
          $Request->setUser ($User);
        
        // Try to resolve to a resource
        return $this->resolveURI ($Request->getURI (), function (qcREST_Interface_Controller $Self, qcREST_Interface_Resource $Resource = null, qcREST_Interface_Collection $Collection = null) use ($Request, $Callback, $Private) {
          // Check if there is a request-body
          if (($cType = $Request->getContentType ()) !== null) {
            // Make sure we have a processor for this
            if (!is_object ($inputProcessor = $this->getProcessor ($cType)))
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_UNSUPPORTED, null, $Callback, $Private);
          } else
            $inputProcessor = null;
          
          // Find a suitable processor for the response
          $outputProcessor = null;
          
          foreach ($Request->getAcceptedContentTypes () as $Mimetype)
            if ($outputProcessor = $this->getProcessor ($Mimetype))
              break;
          
          if (!is_object ($outputProcessor))
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NO_FORMAT, null, $Callback, $Private);
          
          // Check if we should expect a request-body
          if (in_array ($Request->getMethod (), array (qcREST_Interface_Request::METHOD_POST, qcREST_Interface_Request::METHOD_PUT, qcREST_Interface_Request::METHOD_PATCH))) {
            // Make sure we have an input-processor
            if (!$inputProcessor)
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_ERROR, null, $Callback, $Private);
            
            // Make sure the request-body is present
            elseif (($Input = $Request->getContent ()) === null)
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_MISSING, null, $Callback, $Private);
            
            // Try to parse the request-body
            elseif (!($Representation = $inputProcessor->processInput ($Input)))
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_ERROR, null, $Callback, $Private);
          
          // ... or fail if there is content on the request
          } elseif ($Request->getContent () !== null)
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_ERROR, null, $Callback, $Private);
          
          // Just make sure $Representation is set
          else
            $Representation = null;
          
          // Check if the resolver found a collection
          if ($Collection !== null) {
            // Check if the resolver reached the final point
            if ($Resource !== null)
              return $this->handleCollectionRequest ($Resource, $Collection, $Request, $Representation, $outputProcessor, $Callback, $Private);
            
            # TODO: Handle ($Collection !== null) && ($Resource === null)
          }
          
          // Check if the resource found a resource
          if ($Resource !== null)
            return $this->handleResourceRequest ($Resource, $Request, $Representation, $outputProcessor, $Callback, $Private);
          
          // Return if the resolver did not find anything
          return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_FOUND, null, $Callback, $Private);
        }, null, $Request); // resolveURI()
      }); // authenticateRequest()
    }
    // }}}
    
    // {{{ resolveURI
    /**
     * Try to resolve a given URI to a REST-Resource or REST-Collection
     * 
     * @param string $URI The actual URI to resolve
     * @param callable $Callback A callback to raise once the URI was resolved
     * @param mixed $Private (optional) Any private data to pass to the callback
     * 
     * The callback will be raised in the form of
     * 
     *   function (qcREST_Interface_Controller $Self, qcREST_Interface_Resource $Resource = null, qcREST_Interface_Collection $Collection = null, mixed $Private = null) { }
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
    private function resolveURI ($URI, callable $Callback, $Private = null, qcREST_Interface_Request $Request = null) {
      // Make sure we have a root assigned
      if ($this->Root === null)
        return call_user_func ($Callback, $this, null, null, $Private);
      
      // Check if this is a request for our root
      if (strlen ($URI) == 0)
        return call_user_func ($Callback, $this, $this->Root, null, $Private);
      
      $iPath = explode ('/', substr ($URI, 1));
      $lPath = count ($iPath);
      $Path = array ();
      
      for ($i = 0; $i < $lPath; $i++)
        if (($i == 0) || ($i == $lPath - 1) || (strlen ($iPath [$i]) > 0))
          $Path [] = rawurldecode ($iPath [$i]);
      
      $rFunc = null;
      $rFunc = function ($Self, $P1, $P2) use ($Callback, $Private, $Request, &$Path, &$rFunc) {
        // We got a child-collection
        if ($P1 instanceof qcREST_Interface_Collection) {
          // Check wheter to lookup a further child
          if ((count ($Path) == 1) && (strlen ($Path [0]) == 0))
            return call_user_func ($Callback, $this, $Self, $P1, $Private);
          
          // Try to lookup the next child
          return $P1->getChild (array_shift ($Path), $rFunc, null, $Request);
          
        // A child of a collection was returned
        } elseif ($P2 instanceof qcREST_Interface_Resource) {
          // Check if we reached the end
          if (count ($Path) == 0)
            return call_user_func ($Callback, $this, $P2, null, $Private);
          
          // Try to retrive the child-collection of this resource
          return $P2->getChildCollection ($rFunc);
          
        // A child could not be returned
        } elseif ($Self instanceof qcREST_Interface_Collection) {
          // Map the name for better readability
          $Name = &$P1;
          
          // Check wheter we should create a new child by that name
          # TODO: Create intermediate children if count($Path)>0   
          if ((count ($Path) != 0) || !($Request->getMethod () == $Request::METHOD_PUT))
            return call_user_func ($Callback, $this, null, null, $Private);
            # return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_FOUND, null, $Callback, $Private);
          
          // Try to create the child
          # TODO
          #return $Self->createChild ($Representation, $Name, function (qcREST_Interface_Collection $Self, $Name = null, qcREST_Interface_Resource $Child =
          #  // Check if Child count not be created or attributes were rejected
          #  if (!$Child)
          #    return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, null, $Callback, $Private);
          #  
          #  # TODO: Respond with STATUS_CREATED and Location
          #  return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CREATED, null, $Callback, $Private);
          #});
          return call_user_func ($Callback, $this, null, $Self, $Private);
        
        // A collection was not available
        } else {
          # TODO?
          #if ((count ($Path) > 0) && ($Request->getMethod () == $Request::METHOD_PUT))
          #  return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          
          # return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_FOUND, null, $Callback, $Private);
          return call_user_func ($Callback, $this, null, null, $Private);
        }
      }; 
      
      return $this->Root->getChildCollection ($rFunc);
    }
    // }}}
    
    // {{{ authenticateRequest
    /**
     * Try to authenticate a given request
     * 
     * @param qcREST_Interface_Request $Request
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * The callback will be raised in the form of
     * 
     *   function (qcREST_Interface_Controller $Self, qcREST_Interface_Request $Request, bool $Status, qcVCard_Entity $User = null, mixed $Private = null) { }
     * 
     * $Status indicated wheter the request should be processed or not,
     * $User may contain an user-entity that was identified for the request
     * 
     * @access private
     * @return void
     **/
    private function authenticateRequest (qcREST_Interface_Request $Request, callable $Callback, $Private = null) {
      // Check if there are authenticators to process
      if (count ($this->Authenticators) == 0)
        return call_user_func ($Callback, $this, $Request, true, null, $Private);
      
      $Authenticators = $this->Authenticators;
      $Handler = null;
      $Handler = function (qcREST_Interface_Authenticator $Self = null, qcREST_Interface_Request $Request = null, $Status = null, qcVCard_Entity $User = null) use ($Request, $Callback, $Private, &$Handler, &$Authenticators) {
        // Check the result
        if (($Self !== null) && ($Status !== null))
          return call_user_func ($Callback, $this, $Request, !!$Status, $User, $Private);
        
        // Check if we are done
        if (count ($Authenticators) == 0)
          return call_user_func ($Callback, $this, $Request, true, null, $Private);
        
        // Move to next authenticator
        $Next = array_shift ($Authenticators);
        
        return $Next->authenticateRequest ($Request, $Handler);
      };
      
      call_user_func ($Handler);
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
     * @return bool
     **/
    private function handleResourceRequest (qcREST_Interface_Resource $Resource, qcREST_Interface_Request $Request, qcREST_Interface_Representation $Representation = null, qcREST_Interface_Processor $outputProcessor, callable $Callback, $Private = null) {
      switch ($Request->getMethod ()) {
        // Generate a normal representation of that resource
        case $Request::METHOD_GET:
          // Make sure this is allowed
          if (!$Resource->isReadable ($Request->getUser ())) {
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Resource is not readable');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          }
          
          // Retrive the attributes
          return $Resource->getRepresentation (function (qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $Representation = null) use ($Request, $outputProcessor, $Callback, $Private) {
            // Check if the request was successfull
            if ($Representation === null) {
              trigger_error ('Could not retrive attributes from resource');
              
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, null, $Callback, $Private);
            }
            
            return $this->handleRepresentation ($Request, $Resource, $Representation, $outputProcessor, qcREST_Interface_Response::STATUS_OK, null, $Callback, $Private);
          });
        
        // Check if a new sub-resource is requested
        case $Request::METHOD_POST:
          // Convert the request into a directory-request if possible
          return $Resource->getChildCollection (function (qcREST_Interface_Resource $Self, qcREST_Interface_Collection $Collection = null) use ($Request, $Representation, $outputProcessor, $Callback, $Private) {
            if (!$Collection) {
              if (defined ('QCREST_DEBUG'))
                trigger_error ('No child-collection');
              
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
            }
            
            return $this->handleCollectionRequest ($Self, $Collection, $Request, $Representation, $outputProcessor, $Callback, $Private);
          });
        
        // Change attributes
        case $Request::METHOD_PUT:
          // Make sure this is allowed  
          if (!$Resource->isWritable ($Request->getUser ())) {
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Resource is not writable');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          }
          
          return $Resource->setRepresentation ($Representation, function (qcREST_Interface_Resource $Self, qcREST_Interface_Representation $Representation, $Status) use ($Request, $Callback, $Private) {
            // Check if the operation was successfull
            if (!$Status)
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, null, $Callback, $Private);
            
            # TODO: Return representation here?
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_OK, null, $Callback, $Private);
          });
          
        case $Request::METHOD_PATCH:
          // Make sure this is allowed
          if (!$Resource->isWritable ($Request->getUser ())) {
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Resource is not writable');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          }
          
          // Retrive the attributes first
          return $Resource->getRepresentation (function (qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $currentRepresentation = null) use ($Request, $Representation, $Callback, $Private) {
            // Check if the attributes were retrived
            if ($currentRepresentation === null) {
              trigger_error ('Could not retrive current attribute-set');
              
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, null, $Callback, $Private);
            }
            
            // Update Representation
            $requireAttributes = false;
            
            foreach ($Representation as $Key=>$Value)
              if (!$requireAttributes || isset ($currentRepresentation [$Key])) {
                $currentRepresentation [$Key] = $Value;
                unset ($Representation [$Key]);
              } else
                return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_ERROR, null, $Callback, $Private);
            
            // Try to update the resource's attributes
            return $Resource->setRepresentation ($currentRepresentation, function (qcREST_Interface_Resource $Self, qcREST_Interface_Representation $Representation, $Status) use ($Request, $Callback, $Private) {
              // Check if the operation was successfull
              if (!$Status)
                return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, null, $Callback, $Private);
              
              # TODO: Return representation here?
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_STORED, null, $Callback, $Private);
            });
          });
        
        // Remove this resource
        case $Request::METHOD_DELETE:
          // Make sure this is allowed
          if (!$Resource->isRemovable ($Request->getUser ())) {
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Resource may not be removed');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          }
          
          return $Resource->remove (function (qcREST_Interface_Resource $Self, $Status) use ($Request, $Callback, $Private) {
            if (!$Status)
              trigger_error ('Resource could not be removed');
            
            return $this->respondStatus ($Request, ($Status ? qcREST_Interface_Response::STATUS_REMOVED : qcREST_Interface_Response::STATUS_ERROR), null, $Callback, $Private);
          });
      }
      
      # TODO: Unsupported methods here
      #   METHOD_HEAD
      #   METHOD_OPTIONS
      
      return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_UNSUPPORTED, null, $Callback, $Private);
    }
    // }}}
    
    // {{{ handleCollectionRequest
    /**
     * Process a request targeted at a directory-resource
     * 
     * @param qcREST_Interface_Collection $Collection
     * @param qcREST_Interface_Request $Request
     * @param qcREST_Interface_Representation $Representation (optional)
     * @param qcREST_Interface_Processor $outputProcessor
     * @param callable $Callback
     * @param mixed $Private (optional)
     * 
     * @access private
     * @return bool
     **/
    private function handleCollectionRequest (qcREST_Interface_Resource $Resource, qcREST_Interface_Collection $Collection, qcREST_Interface_Request $Request, qcREST_Interface_Representation $Representation = null, qcREST_Interface_Processor $outputProcessor, callable $Callback, $Private = null) {
      switch ($Request->getMethod ()) {
        // Retrive a listing of resources on this directory
        case $Request::METHOD_GET:
          // Make sure we may list the contents
          if (($rc = $Collection->isBrowsable ($Request->getUser ())) !== true) {
            if (($rc === null) && ($Request->getUser () === null))
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_UNAUTHENTICATED, null, $Callback, $Private);
            
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Collection is not browsable');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          }
          
          // Prepare representation
          $Representation = new qcREST_Representation (array (
            'type' => 'listing',
          ));
          
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
          
          if (($First > 0) || ($Last !== null))
            $Representation->addMeta ('X-Pagination-Performance-Warning', 'Using pagination without support on backend');
          
          // Request the children of this resource
          return $Collection->getChildren (function (qcREST_Interface_Collection $Collection, array $Children = null) use ($Request, $Resource, $outputProcessor, $Callback, $Private, $First, $Last, $Search, $Sort, $Order, $Representation) {
            // Check if the call was successfull
            if ($Children !== null) {
              // Determine the total number of children
              if ($Collection instanceof qcREST_Interface_Collection_Extended)
                $Representation ['total'] = $Collection->getChildrenCount ();
              else
                $Representation ['total'] = count ($Children);
            } else {
              // Make sure that collection-parameters are reset
              if ($Collection instanceof qcREST_Interface_Collection_Extended)
                $Collection->resetParameters ();
              
              // Bail out an error
              trigger_error ('Failed to retrive the children');
              
              // Callback our parent
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, null, $Callback, $Private);
            }
            
            // Make sure that collection-parameters are reset
            if ($Collection instanceof qcREST_Interface_Collection_Extended)
              $Collection->resetParameters ();
            
            // Determine the base-URI
            $baseURI = $this->getURI ();  
            $reqURI = $Request->getURI ();
            
            if (($baseURI [strlen ($baseURI) - 1] == '/') && ($reqURI [0] == '/'))
              $baseURI .= substr ($reqURI, 1);
            else
              $baseURI .= $reqURI;
            
            // Prepare finalizer
            $Calls = 1;
     
            $Finalize = function () use (&$Calls, $Request, $Resource, $Representation, $outputProcessor, $Collection, $Callback, $Private, $First, $Last, $Search, $Sort, $Order) {
              // Check if there are calls pending
              if (--$Calls > 0)
                return;
              
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
                $Representation ['items'] = array_slice ($Items, $First, $Last - $First);
              }
              
              // Raise the final callback
              return $this->handleRepresentation (
                $Request,
                $Resource,
                $Representation,
                $outputProcessor,
                qcREST_Interface_Response::STATUS_OK,
                array (
                  'X-Resource-Type' => 'Collection',
                  'X-Collection-Class' => get_class ($Collection),
                ),
                $Callback, $Private
              );
            };
            
            // Determine how to present children on the listing
            if (is_callable (array ($Collection, 'getChildFullRepresenation')))
              $Extend = $Collection->getChildFullRepresenation ();
            else
              $Extend = false;
            
            // Append children to the listing
            $Name = $Collection->getNameAttribute ();
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
              
              // Create basic structures
              $Items [] = $Item = new stdClass;
              $Item->$Name = $Child->getName ();
              $Item->uri = $baseURI . rawurlencode ($Item->$Name);
              $Item->isCollection = $Child->hasChildCollection ();
              
              // Check wheter to expand the child
              if (!(($Aware = ($Child instanceof qcRest_Interface_Collection_Representation)) || $Extend))
                continue;
              
              // Expand the child
              $Calls++;
              call_user_func (
                array ($Child, ($Aware ? 'getCollectionRepresentation' : 'getRepresentation')),
                function (qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $rRepresentation = null) use ($Item, $Name, $Finalize, $Representation) {
                  // Make sure we don't overwrite special keys
                  unset ($rRepresentation [$Name], $rRepresentation ['uri'], $rRepresentation ['isCollection']);
                  
                  // Merge the attributes
                  if ($rRepresentation !== null)
                    foreach ($rRepresentation as $Key=>$Value)
                      $Item->$Key = $Value;
                  
                  // Try to finalize
                  call_user_func ($Finalize);
                }
              );
            }
            
            // Store the children on the representation
            $Representation ['items'] = $Items;
            
            // Try to finalize
            return call_user_func ($Finalize);
          }, null, $Request);
        
        // Create a new resource on this directory
        case $Request::METHOD_POST:
          // Make sure this is allowed
          if (!$Collection->isWritable ($Request->getUser ())) {
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Collection is not writable');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          }
          
          return $Collection->createChild ($Representation, null, function (qcREST_Interface_Collection $Self, $Name = null, qcREST_Interface_Resource $Child = null, qcREST_Interface_Representation $Representation = null) use ($outputProcessor, $Callback, $Private, $Request, $Resource) {
            // Check if a new child was created
            if (!$Child) {
              if ($Representation)
                return $this->handleRepresentation ($Request, $Resource, $Representation, $outputProcessor, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, null, $Callback, $Private);
              
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, null, $Callback, $Private);
            }
            
            // Create URI for newly created child
            $URI = $this->getURI ();
            $reqURI = $Request->getURI ();   
            
            if ($reqURI [strlen ($reqURI) - 1] != '/')
              $reqURI .= '/';
            
            if (($URI [strlen ($URI) - 1] == '/') && ($reqURI [0] == '/'))
              $URI .= substr ($reqURI, 1);
            else
              $URI .= $reqURI;
            
            $URI .= rawurlencode ($Child->getName ());
            
            // Process the response
            if ($Representation)
              return $this->handleRepresentation ($Request, $Child, $Representation, $outputProcessor, qcREST_Interface_Response::STATUS_CREATED, array ('Location' => $URI), $Callback, $Private);
            
            return $Child->getRepresentation (function (qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $Representation = null) use ($outputProcessor, $Request, $URI, $Callback, $Private) {
              // Check if we retrive a representation for this
              if (!$Representation)
                return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CREATED, array ('Location' => $URI), $Callback, $Private);
              
              return $this->handleRepresentation ($Request, $Resource, $Representation, $outputProcessor, qcREST_Interface_Response::STATUS_CREATED, array ('Location' => $URI), $Callback, $Private);
            });
            
            # return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CREATED, array ('Location' => $URI), $Callback, $Private);
          }, null, $Request);
        
        // Replace all resources on this directory (PUT) with new ones or just add a new set (PATCH)
        case $Request::METHOD_PUT:
          // Tell later code that we want to remove items
          $Removals = array ();
          
        case $Request::METHOD_PATCH:
          // Make sure this is allowed
          if (!$Collection->isWritable ($Request->getUser ())) {
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Collection is not writable and contents may not be replaced (PUT) or patched (PATCH)');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          }
          
          // Just check if we are in patch-mode ;-)
          if (!isset ($Removals))
            $Removals = null;
          
          // Request the children of this resource
          return $Collection->getChildren (function (qcREST_Interface_Collection $Collection, array $Children = null) use ($Removals, $Request, $Resource, $Representation, $outputProcessor, $Callback, $Private) {
            // Check if the call was successfull 
            if ($Children === null) {
              trigger_error ('Failed to retrive the children');
              
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, null, $Callback, $Private);
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
            $func = function ($Self = null, $P1 = null, $P2 = null, $P3 = null) use ($Request, $outputProcessor, $Resource, &$Create, &$Updates, &$Removals, $Representation, &$func, $Callback, $Private) {
              // Check if we are returning
              if ($Self) {
                // Check if we tried to create a child
                if ($Self === $this) {
                  $Name = $P1;
                  $Child = $P2;
                  $cRepresentation = $P3;
                  
                  if (!$Child)
                    return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, null, $Callback, $Private);
                
                // Check if we tried to update a child-resource
                } elseif (is_array ($P1)) {
                  $setAttributes = &$P1;
                  $Status = &$P2;
                  
                  if (!$Status)
                    return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, null, $Callback, $Private);
                  
                // Treat anything else as removal
                } else {
                  $Status = &$P1;
                  
                  if (!$Status)
                    return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, null, $Callback, $Private);
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
                  return $Child->getRepresentation (function (qcREST_Interface_Resource $Child, qcREST_Interface_Representation $currentRepresentation = null) use ($func, $childRepresentation) {
                    // Check if the current representation could be retrived
                    if ($currentRepresentation === null)
                      return call_user_func ($func, $Child, $childRepresentation, false);
                    
                    // Update Representation   
                    $requireAttributes = false;
                    
                    foreach ($childRepresentation as $Key=>$Value)
                      if (!$requireAttributes || isset ($currentRepresentation [$Key])) {
                        $currentRepresentation [$Key] = $Value;
                        trigger_error ('Merge ' . $Key . ' with ' . $Value);
                        unset ($childRepresentation [$Key]);
                      } else
                        return call_user_func ($func, $Child, $currentRepresentation, false);
                    
                    // Forward the update
                    return $Child->setRepresentation ($currentRepresentation, $func);
                  });
                
                // Treat the update as a complete Representation
                return $Child->setRepresentation ($childRepresentation, $func);
              }
              
              // Try to create pending children
              foreach ($Create as $Name=>$childAttributes) {
                unset ($Create [$Name]);
                
                return $Resource->createChild (new qcREST_Representation ($childAttributes), $Name, $func, null, $Request);
              }
              
              // Try to remove removals
              if ($Removals !== null)
                foreach ($Removals as $Name=>$Child) {
                  unset ($Removals [$Name]);
                  
                  return $Child->remove ($func);
                }
              
              // If we get here, we were finished
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_STORED, null, $Callback, $Private);
            };
            
            // Dispatch to update-function
            return call_user_func ($func);
          }, null, $Request);
        
        // Delete the entire collection
        case $Request::METHOD_DELETE:
          // Make sure this is allowed
          if (!$Collection->isRemovable ($Request->getUser ())) {
            if (defined ('QCREST_DEBUG'))
              trigger_error ('Collection may not be removed');
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          }
          
          return $Collection->remove (function (qcREST_Interface_Resource $Self, $Status) use ($Request, $Callback, $Private) {
            return $this->respondStatus ($Request, ($Status ? qcREST_Interface_Response::STATUS_OK : qcREST_Interface_Response::STATUS_ERROR), null, $Callback, $Private);
          });
      }
      
      # TODO: Unsupported methods here
      #   METHOD_HEAD
      #   METHOD_OPTIONS
      
      return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_UNSUPPORTED, null, $Callback, $Private);
    }
    // }}}
    
    // {{{ handleRepresentation
    /**
     * Process Representation and generate output
     * 
     * @param qcREST_Interface_Request $Request
     * @param qcREST_Interface_Resource $Resource
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
        $Meta = array_merge ($Representation->getMeta (), $Meta);
      
      // Remove any redirects if unwanted
      if (isset ($Meta ['Location']) && !$Representation->allowRedirect ())
        unset ($Meta ['Location']);
      
      // Just pass the status if the representation is empty
      if (count ($Representation) == 0)
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
      return $this->setResponse ($Response, function (qcREST_Interface_Controller $Self, qcREST_Interface_Response $Response, $Status) use ($Callback, $Private) {
        call_user_func ($Callback, $this, $Response->getRequest (), $Response, $Status, $Private);
      });
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
      
      // Append some meta for unauthenticated status
      if ($Status == qcREST_Interface_Response::STATUS_CLIENT_UNAUTHENTICATED) {
        if (isset ($Meta ['WWW-Authenticate']))
          $Schemes = (is_array ($Meta ['WWW-Authenticate']) ? $Meta ['WWW-Authenticate'] : array ($Meta ['WWW-Authenticate']));
        else
          $Schemes = array ();
        
        foreach ($this->Authenticators as $Authenticator)
          foreach ($Authenticator->getSchemes () as $aScheme) 
            if (isset ($aScheme ['scheme']))
              $Schemes [] = $aScheme ['scheme'] . ' realm="' . (isset ($aScheme ['realm']) ? $aScheme ['realm'] : get_class ($Authenticator)) . '"';
        
        $Meta ['WWW-Authenticate'] = $Schemes;
      }
      
      return $this->sendResponse (new qcREST_Response ($Request, $Status, '', '', $Meta), $Callback, $Private);
    }
    // }}}
  }

?>