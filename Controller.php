<?PHP

  require_once ('qcREST/Interface/Controller.php');
  require_once ('qcREST/Response.php');
  require_once ('qcREST/Representation.php');
  
  ini_set ('display_errors', 'Off');
  
  abstract class qcREST_Controller implements qcREST_Interface_Controller {
    private $Root = null;
    private $Processors = array ();
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
      
      // Try to parse the request-body
      if ($inputProcessor) {
        // Check if we should expect a request-body
        if (!in_array ($Request->getMethod (), array (qcREST_Interface_Request::METHOD_POST, qcREST_Interface_Request::METHOD_PUT, qcREST_Interface_Request::METHOD_PATCH)))
          return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_ERROR, null, $Callback, $Private);
        
        // Make sure the request-body is present
        elseif (($Input = $Request->getContent ()) === null)
          return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_MISSING, null, $Callback, $Private);
        
        // Try to parse the request-body
        elseif (!($Representation = $inputProcessor->processInput ($Input)))
          return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_ERROR, null, $Callback, $Private);
      } elseif ($Request->getContent () !== null)
        return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CLIENT_ERROR, null, $Callback, $Private);
      
      else
        $Representation = null;
      
      // Make sure we have a root-element assigned
      if (!$this->Root)
        return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_SERVER_ERROR, null, $Callback, $Private);
      
      // Try to resolve the URI
      $URI = $Request->getURI ();
      
      if (strlen ($URI) == 0)
        return $this->handleResourceRequest ($this->Root, $Request, $Representation, $outputProcessor, $Callback, $Private);
      
      $iPath = explode ('/', substr ($URI, 1));
      $lPath = count ($iPath);
      $Path = array ();
      
      for ($i = 0; $i < $lPath; $i++)
        if (($i == 0) || ($i == $lPath - 1) || (strlen ($iPath [$i]) > 0))
          $Path [] = $iPath [$i];
      
      $rFunc = null;
      $rFunc = function ($Self, $P1, $P2) use ($Request, $Representation, $outputProcessor, $Callback, $Private, &$Path, &$rFunc) {
        // We got a child-collection
        if ($P1 instanceof qcREST_Interface_Collection) {
          // Check wheter to lookup a child
          if ((count ($Path) == 1) && (strlen ($Path [0]) == 0))
            return $this->handleCollectionRequest ($Self, $P1, $Request, $Representation, $outputProcessor, $Callback, $Private);
          
          // Try to lookup the next child
          return $P1->getChild (array_shift ($Path), $rFunc);
          
          // Resolve further
          $Next = array_shift ($Path);
          return $Child->getChild ($Next, $rFunc);
          
        // A child of a collection was returned
        } elseif ($P2 instanceof qcREST_Interface_Resource) {
          // Check if we reached the end
          if (count ($Path) == 0)
            return $this->handleResourceRequest ($P2, $Request, $Representation, $outputProcessor, $Callback, $Private);
          
          // Try to retrive the child-collection of this resource
          return $P2->getChildCollection ($rFunc);
        
        // A child could not be returned
        } elseif ($Self instanceof qcREST_Interface_Collection) {
          // Map the name for better readability
          $Name = &$P1;
          
          // Check wheter we should create a new child by that name
          # TODO: Create intermediate children if count($Path)>0   
          if ((count ($Path) != 0) || !($Request->getMethod () == $Request::METHOD_PUT))
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_FOUND, null, $Callback, $Private);
          
          // Try to create the child
          return $Self->createChild ($Representation, $Name, function (qcREST_Interface_Collection $Self, $Name = null, qcREST_Interface_Resource $Child = null) use ($Request, $Callback, $Private) {
            // Check if Child count not be created or attributes were rejected
            if (!$Child)
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, null, $Callback, $Private);
            
            # TODO: Respond with STATUS_CREATED and Location
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CREATED, null, $Callback, $Private);
          });
        
        // A collection was not available
        } else {
          if ((count ($Path) > 0) && ($Request->getMethod () == $Request::METHOD_PUT))
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          
          return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_FOUND, null, $Callback, $Private);
        }
      };
      
      return $this->Root->getChildCollection ($rFunc);
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
          if (!$Resource->isReadable ())
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          
          // Retrive the attributes
          return $Resource->getRepresentation (function (qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $Representation = null) use ($Request, $outputProcessor, $Callback, $Private) {
            // Check if the request was successfull
            if ($Representation === null) {
              trigger_error ('Could not retrive attributes from resource');
              
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, null, $Callback, $Private);
            }
            
            # TODO: Process attributes?
            
            // Try to generate output
            return $outputProcessor->processOutput (function (qcREST_Interface_Processor $Processor, $Output, $OutputType, qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $Representation, qcREST_Interface_Request $Request, qcREST_Interface_Controller $Controller) use ($Callback, $Private) {
              // Check if the processor returned an error
              if ($Output === false) {
                trigger_error ('Output-Processor failed');
                
                return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, null, $Callback, $Private);
              }
              
              // Create a response-object
              $Meta = array (
                'X-Resource-Type' => 'Resource',
                'X-Resource-Class' => get_class ($Resource),
              );
              
              $Response = new qcREST_Response ($Request, qcREST_Interface_Response::STATUS_OK, $Output, $OutputType, $Meta);
              
              // Return the response
              return $this->sendResponse ($Response, $Callback, $Private);
            }, null, $Resource, $Representation, $Request, $this);
          });
        
        // Check if a new sub-resource is requested
        case $Request::METHOD_POST:
          // Convert the request into a directory-request if possible
          return $Resource->getChildCollection (function (qcREST_Interface_Resource $Self, qcREST_Interface_Collection $Collection = null) use ($Request, $Representation, $outputProcessor, $Callback, $Private) {
            if (!$Collection)
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
            
            return $this->handleCollectionRequest ($Self, $Collection, $Request, $Representation, $outputProcessor, $Callback, $Private);
          });
        
        // Change attributes
        case $Request::METHOD_PUT:
          // Make sure this is allowed  
          if (!$Resource->isWritable ())
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          
          return $Resource->setRepresentation ($Representation, function (qcREST_Interface_Resource $Self, qcREST_Interface_Representation $Representation, $Status) use ($Request, $Callback, $Private) {
            // Check if the operation was successfull
            if (!$Status)
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, null, $Callback, $Private);
            
            # TODO: Return representation here?
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_OK, null, $Callback, $Private);
          });
          
        case $Request::METHOD_PATCH:
          // Make sure this is allowed
          if (!$Resource->isWritable ())
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          
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
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_OK, null, $Callback, $Private);
            });
          });
        
        // Remove this resource
        case $Request::METHOD_DELETE:
          // Make sure this is allowed
          if (!$Resource->isRemovable ())
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          
          return $Resource->remove (function (qcREST_Interface_Resource $Self, $Status) use ($Request, $Callback, $Private) {
            if (!$Status)
              trigger_error ('Resource could not be removed');
            
            return $this->respondStatus ($Request, ($Status ? qcREST_Interface_Response::STATUS_OK : qcREST_Interface_Response::STATUS_ERROR), null, $Callback, $Private);
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
          if (!$Collection->isBrowsable ())
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          
          // Request the children of this resource
          return $Collection->getChildren (function (qcREST_Interface_Collection $Collection, array $Children = null) use ($Request, $Resource, $outputProcessor, $Callback, $Private) {
            // Check if the call was successfull
            if ($Children === null) {
              trigger_error ('Failed to retrive the children');
              
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, null, $Callback, $Private);
            }
            
            // Create a listing
            $Attributes = array (
              'type' => 'listing',
              'items' => array (),
            );
            
            $baseURI = $this->getURI ();
            $reqURI = $Request->getURI ();
            
            if (($baseURI [strlen ($baseURI) - 1] == '/') && ($reqURI [0] == '/'))
              $baseURI .= substr ($reqURI, 1);
            else
              $baseURI .= $reqURI;
            
            $Attrs = 1;
            $finalHandler = function () use ($Resource, &$Attributes, $Request, $outputProcessor, $Callback, $Private) {
              return $outputProcessor->processOutput (
                function (qcREST_Interface_Processor $Processor, $Output, $OutputType, $Collection, qcREST_Interface_Representation $Representation, qcREST_Interface_Request $Request, qcREST_Interface_Controller $Controller) use ($Callback, $Private) {
                  // Check if the processor returned an error
                  if ($Output === false) {
                    trigger_error ('Output-Processor failed');
                    
                    return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_ERROR, null, $Callback, $Private);
                  }
                  
                  // Create a response-object
                  $Meta = array (
                    'X-Resource-Type' => 'Collection',
                    'X-Resource-Class' => get_class ($Collection),
                  );
                  
                  $Response = new qcREST_Response ($Request, qcREST_Interface_Response::STATUS_OK, $Output, $OutputType, $Meta);
                  
                  // Return the response
                  return $this->sendResponse ($Response, $Callback, $Private);
                }, null, $Resource, new qcREST_Representation ($Attributes), $Request, $this
              );
            };
            
            $attrHandler = function (qcREST_Interface_Resource $Resource, qcREST_Interface_Representation $Representation = null, $Item) use ($finalHandler, &$Attrs) {
              // Make sure we don't overwrite special keys
              unset ($Representation ['name'], $Representation ['uri'], $Representation ['isCollection']);
              
              // Merge the attributes
              if ($Representation !== null)
                foreach ($Representation as $Key=>$Value)
                  $Item->$Key = $Value;
              
              // Check if this is the last callback
              if (--$Attrs < 1)
                call_user_func ($finalHandler);
            };
            
            foreach ($Children as $Child) {
              $Attributes ['items'][] = $Item = new stdClass;
              $Item->name = $Child->getName ();
              $Item->uri = $baseURI . rawurlencode ($Item->name);
              $Item->isCollection = $Child->hasChildCollection ();
              
              if ($Child instanceof qcRest_Interface_Collection_Representation) {
                $Attrs++;
                $Child->getCollectionRepresentation ($attrHandler, $Item);
              }
            }
            
            if ($Attrs == 1)
              return call_user_func ($finalHandler);
            
            $Attrs--;
            
            return;
          });
        
        // Create a new resource on this directory
        case $Request::METHOD_POST:
          // Make sure this is allowed
          if (!$Collection->isWritable ())
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          
          return $Collection->createChild ($Representation, null, function (qcREST_Interface_Collection $Self, $Name = null, qcREST_Interface_Resource $Child = null, qcREST_Interface_Representation $Representation = null) use ($Callback, $Private, $Request) {
            if (!$Child)
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_FORMAT_REJECTED, null, $Callback, $Private);
            
            $URI = $this->getURI ();
            $reqURI = $Request->getURI ();   
            
            if ($reqURI [strlen ($reqURI) - 1] != '/')
              $reqURI .= '/';
            
            if (($URI [strlen ($URI) - 1] == '/') && ($reqURI [0] == '/'))
              $URI .= substr ($reqURI, 1);
            else
              $URI .= $reqURI;
            
            $URI .= rawurlencode ($Child->getName ());
            
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_CREATED, array ('Location' => $URI), $Callback, $Private);
          });
        
        // Replace all resources on this directory (PUT) with new ones or just add a new set (PATCH)
        case $Request::METHOD_PUT:
          // Tell later code that we want to remove items
          $Removals = array ();
          
        case $Request::METHOD_PATCH:
          // Make sure this is allowed
          if (!$Collection->isWritable ())
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          
          // Just check if we are in patch-mode ;-)
          if (!isset ($Removals))
            $Removals = null;
          
          // Request the children of this resource
          return $Collection->getChildren (function (qcREST_Interface_Collection $Collection, array $Children = null) use ($Removals, $Request, $outputProcessor, $Callback, $Private) {
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
                
                // Remove from to-be-created
                unset ($Create [$Name]);
                
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
              
              // Try to create pending children
              foreach ($Create as $Name=>$childAttributes) {
                unset ($Create [$Name]);
                
                return $Resource->createChild (new qcREST_Representation ($childAttributes), $Name, $func);
              }
              
              // Try to update pending children
              foreach ($Updates as $Name=>$Child) {
                unset ($Updates [$Name]);
                
                return $Child->setRepresentation (new qcREST_Representation ($Representation [$Name]), $func);
              }
              
              // Try to remove removals
              foreach ($Removals as $Name=>$Child) {
                unset ($Removals [$Name]);
                
                return $Child->remove ($func);
              }
              
              // If we get here, we were finished
              return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_OK, null, $Callback, $Private);
            };
            
            // Dispatch to update-function
            return call_user_func ($func);
          });
        
        // Delete the entire collection
        case $Request::METHOD_DELETE:
          // Make sure this is allowed
          if (!$Collection->isRemovable ())
            return $this->respondStatus ($Request, qcREST_Interface_Response::STATUS_NOT_ALLOWED, null, $Callback, $Private);
          
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
      return $this->sendResponse (new qcREST_Response ($Request, $Status, '', '', is_array ($Meta) ? $Meta : array ()), $Callback, $Private);
    }
    // }}}
  }

?>