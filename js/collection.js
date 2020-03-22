(function (root) {
  // Make sure qcREST is setup in root
  root.qcREST = root.qcREST || { };

  if (!root.qcREST.Emitter)
    throw 'Missing support for Emitters';
  
  let onResourceEvents = function (event, resource, collection, options) {
    // Make sure the resource is valid
    if (!(resource instanceof root.qcREST.Resource)) {
      console.debug ('Event for non-resource received');
      
      return;
    }
    
    if (event == 'destroy')
      this.remove (resource);
    else if (event == 'change') {
      // TODO: Check if id was changed
    } else {
      // console.debug ('Discard resource-event', event);
      
      return;
    }
    
    // Propagate the event
    this.trigger.apply (this, arguments);
  };
  
  // {{{ qcREST.Collection
  /**
   * Constructor for REST-Collection
   * 
   * @access friendly
   * @return void
   **/
  root.qcREST.Collection = root.qcREST.Emitter.extend ({
    /* URL of this collection */
    '$url' : null,
    
    /* Constructor for new entities */
    '$resourceClass' : null,
    
    /* Override id-attribute for resource-class */
    '$resourceIDAttribute' : null,
    
    /* Set of resources on this collection */
    '$resources' : [ ],
    
    /* Map IDs to their resources */
    '$idMap' : { },
    
    // {{{ __construct
    /**
     * Create a new REST-Collection
     * 
     * @param Array entities (optional) UNUSED
     * @param object options (optional)
     * 
     * Attributes for options:
     *   url   - URL of this REST-Collection
     *   model - Class/Constructor for new models on this collection
     * 
     * @access friendly
     * @return void
     **/
    'constructor' : function Collection (entities = undefined, options = undefined) {
      // Call pre-initialize
      this.preinitialize.apply (this, arguments);
      
      // Setup ourself
      let self = this;
      
      this.$url = (options && options.url ? options.url : null);
      this.$resourceClass = (options && options.model ? options.model : this.$resourceClass || root.qcREST.Resource);
      this.$resources = [ ];
      this.$idMap = { };
      
      Object.defineProperty (
        this,
        'model',
        {
          'get' : function () {
            return self.$resourceClass;
          }
        }
      );
      
      Object.defineProperty (
        this,
        'models',
        {
          'get' : function () {
            return self.$resources;
          }
        }
      );
      
      // Call initialize-callback
      this.initialize.apply (this, arguments);
      
      // Clean up callbacks
      delete (this.preinitialize);
      delete (this.initialize);
    },
    // }}}
    
    // {{{ Collection.preinitialize
    /**
     * Callback to be raised before any initialization is applied to a new collection
     * 
     * @param Array entities (optional)
     * @param object options (optional)
     * 
     * @access friendly
     * @return void
     **/
    'preinitialize' : function (entities = undefined, options = undefined) { },
    // }}}
    
    // {{{ Collection.initialize
    /**
     * Callback to be raised after basic initialization of a new collection
     * 
     * @param Array entities (optional)
     * @param object options (optional)
     * 
     * @access friendly
     * @return void
     **/
    'initialize' : function (entities = undefined, options = undefined) { },
    // }}}
    
    // {{{ Collection.parse
    /**
     * Pre-process a response from REST
     * 
     * @param mixed input
     * 
     * @access public
     * @return Array
     **/
    'parse' : function (input) {
      // Always pass arrays to result
      if (Array.isArray (input))
        return input;
      
      // Only process objects
      if (typeof input != 'object')
        return undefined;
      
      // Try to find an array on input
      let output = undefined;
      
      for (let key in input)
        if (!Array.isArray (input [key]))
          continue;
        else if (output !== undefined)
          throw 'Multiple arrays on input, please consider using an own parse-implementation';
        else
          output = input [key];
      
      // Forward the output
      return output;
    },
    // }}}
    
    // {{{ modelId
    /**
     * Retrive the ID of a given model/resources
     * 
     * @param object resource
     * 
     * @compat Backbone
     * @access public
     * @return string
     **/
    'modelId' : function (resource) {
      let attribute = this.getIDAttribute ();
      
      if (typeof resource.get == 'function')
        return resource.get (attribute);
      
      return resource [attribute];
    },
    // }}}
    
    // {{{ at
    /**
     * Retrive an resoruce at a given position
     * 
     * @param int index
     * 
     * @access public
     * @return qcREST.Resource
     **/
    'at' : function (index) {
      return this.$resources [index];
    },
    // }}}
    
    // {{{ Collection.get
    /**
     * Try to get a resource from this collection by id
     * 
     * @param string id
     * 
     * @access public
     * @return qcREST.Resource
     **/
    'get' : function (id) {
      return this.$idMap [id];
    },
    // }}}
    
    // {{{ Collection.url
    /**
     * Retrive a meaningful URL for this collection
     * 
     * @access public
     * @return string
     **/
    'url' : function () {
      return this.$url;
    },
    // }}}
    
    // {{{ Collection.getIDAttribute
    /**
     * Retrive the general ID-Attribute for entities
     * 
     * @access public
     * @return string
     **/
    'getIDAttribute' : function () {
      return this.$resourceIDAttribute || this.$resourceClass.prototype.idAttribute || 'id';
    },
    // }}}
    
    // {{{ Collection.fetch
    /**
     * Refresh entities on this collection
     * 
     * @param object options (optional)
     * 
     * Supported options:
     *   parse   - TODO
     *   reset   - TODO
     *   success - TODO
     *   error   - TODO
     * 
     * @access public
     * @return Promise
     **/
    'fetch' : function (options = undefined) {
      let self = this;
      
      options = ((typeof options == 'object') && options ? options : { });
      
      // Fire initial event
      if (!options.silent)
        self.trigger ('read', self, options);
      
      return root.qcREST.request (self.url ()).then (
        function (result) {
          let entities = self.parse (result.body),
              added = [ ],
              updated = [ ],
              removed = [ ],
              idMap = { };
          
          // Make sure we have an array of entities
          if (!Array.isArray (entities))
            throw 'Expected an array as return from REST-Request/parse-function';
          
          for (let entity of entities) {
            // Skip unknown id-attributes
            let id = self.modelId (entity);
            
            if (!id) {
              console.debug ('Entity is missing id-attribute', entity);
              
              continue;
            }
            
            let resource = self.get (id);
            
            // Make sure we have found a resource
            if (!resource) {
              // Create a new resource
              resource = new self.$resourceClass (entity, { 'url' : self.url () + id, 'collection' : self });
              
              // Watch events on this resource
              resource.on ('all', onResourceEvents.bind (self));
              
              // Push resource to collections
              added.push (resource);
              self.$resources.push (resource);
            
            // Update an existing resource
            } else {
              resource.set (resource);
              // TODO: Check if there was really an update
              updated.push (resource);
            }
            
            // Mark resource as processed
            idMap [id] = resource;
          }
          
          // Filter entities for removed entities
          self.$resources = self.$resources.filter (
            function (resource) {
              let id = resource.getID ();
              
              if (id && (id in idMap))
                return true;
              
              // TODO: Remove all-event
              removed.push (resource);
              
              return false;
            }
          );
          
          self.$idMap = idMap;
          
          // Generate result
          options.changes = {
            'added' : added,
            'updated' : updated,
            'removed' : removed,
          };
          
          // Check wheter to fire events
          if (!options.silent) {
            // Signal that we are fireing a batch of events
            if (added.length + removed.length > 1)
              self.trigger ('batch', self, options);
            
            // Fire events for added entities
            for (let resource of added)
              self.trigger ('add', resource, self, options);
            
            // Fire events for removed entities
            for (let resource of removed)
              self.trigger ('remove', resource, self, options);
            
            // Fire final events
            self.trigger ('update', self, options);
            self.trigger ('sync', self, result, options);
          }
          
          // Forward the result
          return options.changes;
        },
        function (e) {
          // Trigger event for this
          if (!options.silent)
            self.trigger ('error', self, e, options);
          
          // Forward the error
          throw e;
        }
      );
    },
    // }}}
    
    // {{{ toJSON
    /**
     * Convert this collection into a JSON-Object
     * 
     * @param object options (optional)
     * 
     * @access public
     * @return Array
     **/
    'toJSON' : function (options = undefined) {
      let result = [ ];
      
      for (let resource of this.$resources)
        result.push (resource.toJSON (options));
      
      return result;
    },
    // }}}
    
    // {{{ add
    /**
     * Add a set of resources to this collection
     * 
     * @param Array resources
     * @param object options (optional)
     * 
     * @access public
     * @return Array
     **/
    'add' : function (resources, options = undefined) {
      // Sanatize options
      options = ((typeof options == 'object') && options ? options : { });
      
      // Make sure we have a set of resources
      if (!Array.isArray (resources))
        resources = [ resources ];
      
      // Process all resources
      let added = [ ],
          merged = [ ],
          changed = [ ];
      
      for (let resource of resources) {
        // Retrive the id of that resource
        let id = this.modelId (resource);
        
        // Make sure the resource is of the right type
        if (!(resource instanceof root.qcREST.Resource))
          resource = new this.$resourceClass (resource, { 'collection' : this });
        
        // Check for an existing resource on this collection
        let resourceInstance = (id ? this.get (id) : null);
        
        // Add a new resource
        if (!resourceInstance) {
          // TODO options.at - Index where to add
          this.$resources.push (resource);
          
          if (id !== undefined)
            this.$idMap [id] = resource;
          
          // Make sure the resource points to a collection
          if (!resource.$collection)
            resource.$collection = this;
          
          // Watch events on this resource
          resource.on ('all', onResourceEvents.bind (this));
          
          // Push to collection
          added.push (resource);
          
        // Check wheter to merge
        } else if (options.merge) {
          // Merge attributes
          resourceInstance.set (resource.toJSON (), options);
          
          // Push to collection
          merged.push (resourceInstance);
        } else
          continue;
        
        // Push to total collection
        changed.push (resourceInstance);
      }
      
      // Trigger final events
      if (!options.silent && ((added.length > 0) || (merged.length > 0))) {
        options.changes = {
          'added' : added,
          'removed' : [ ],
          'merged' : merged
        };
        
        for (let resource of added)
          this.trigger ('add', resource, this, options);
        
        this.trigger ('update', this, options);
      }
      
      // Return all affected resources
      return changed;
    },
    // }}}
    
    // {{{ remove
    /**
     * Remove one or more resource from this collection
     * 
     * @param mixed resources
     * @param object options (optional)
     * 
     * @access public
     * @return Array
     **/
    'remove' : function (resources, options = undefined) {
      options = ((typeof options == 'object') && options ? options : { });
      
      // Make sure this is an array
      if (!Array.isArray (resources))
        resources = [ resources ];
      
      // Signal that we may fire a batch of events
      if (!options.silent && (resources.length > 1))
        this.trigger ('batch', this, options);
      
      // Try to remove all resources
      let removed = [ ];
      
      for (let resource of resources) {
        // Retrive the id of that resource
        let id = this.modelId (resource),
            instance;
        
        // Try to find resource by that id
        if (id && (instance = this.get (id)))
          delete this.$idMap [id];
        
        // Try to remove by instance
        else
          instance = resource;
        
        // Find index on resources
        let index = this.$resources.indexOf (instance);
        
        if (index < 0) {
          console.debug ('Failed to find instance on resources', instance);
          
          continue;
        }
        
        // Remove from resources
        this.$resources.splice (index, 1);
        
        // Push to removed instances
        removed.push (instance);
        
        // Raise an event if neccessary
        if (!options.silent) {
          options.index = index;
          this.trigger ('remove', instance, this, options);
          delete options.index;
        }
      }
      
      // Raise an update-event
      if (!options.silent && (resources.length > 1)) {
        options.changes = {
          'added' : [ ],
          'removed' : removed,
          'merged' : [ ]
        };
        
        this.trigger ('update', this, options);
        delete options.changes;
      }
      
      return removed;
    },
    // }}}
    
    // {{{ sort
    /**
     * Re-Sort this collection
     * 
     * @param object options (optional)
     * 
     * @access public
     * @return void
     **/
    'sort' : function (options) {
      options = options || { };
      
      let comparator = options.comparator || this.comparator;
      
      if (typeof comparator == 'function') {
        this.$resources.sort (comparator);
        
        if (!options.silent)
          this.trigger ('sort', this, options);
      } else if (typeof comparator == 'string')
        this.sortBy (comparator, options);
      
      return this;
    },
    // }}}
    
    // {{{ sortBy
    /**
     * Sort this collection by a given attribute
     * 
     * @param string attribute
     * @param object options (optional)
     * 
     * @æccess public
     * @return void
     **/
    'sortBy' : function (attribute, options) {
      options = options || { };
      
      this.$resources.sort (
        function (a, b) {
          if (typeof options.comparator == 'function')
            return options.comparator.apply (this, [ a, b ]);
          
          return a.get (attribute) < b.get (attribute);
        }
      );
      
      if (!options.silent)
        this.trigger ('sort', this, options);
      
      return this;
    }
    // }}}
  });
  
  // {{{ Collection.@@iterator
  /**
   * Iterate over cookies on this collection
   * 
   * @access friendly
   * @return void
   **/
  root.qcREST.Collection.prototype [Symbol.iterator] = function* () {
    for (let resource of this.$resources)
      yield resource;
  };
  // }}}

})(self);
