(function (root) {
  // Make sure qcREST is setup in root
  root.qcREST = root.qcREST || { };
  
  if (!root.qcREST.Emitter)
    throw 'Missing support for Emitters';
  
  // {{{ extend
  /**
   * Enhance a given object by the attributes of other objects if they are not defined yet
   * 
   * @param object destination
   * @param object ...
   * 
   * @access private
   * @return object
   **/
  function extend (destination) {
    for (let i = 1; i < arguments.length; i++) {
      if (typeof arguments [i] != 'object')
        continue;
      
      for (let key in arguments [i])
        if (typeof destination [key] == 'undefined')
          destination [key] = arguments [i][key];
    }
    
    return destination;
  }
  // }}}
  
  // {{{ clone
  /**
   * Create a copy of a given object
   * 
   * @param object obj
   * 
   * @access private
   * @return object
   **/
  function clone (obj) {
    if (obj === null)
      return null;
    
    let result = extend ((typeof obj.constructor == 'function' ? new obj.constructor : { }), obj);
    
    for (let key in result)
      if (typeof result [key] == 'object')
        result [key] = clone (result [key]);
    
    return result;
  }
  // }}}
  
  // {{{ match
  /**
   * Check if two variables are equal (not identical)
   * 
   * @param mixed a
   * @param mixed b
   * 
   * @access private
   * @return bool
   **/
  function match (a, b) {
    // Compare the types
    if (typeof a != typeof b)
      return false;
    
    // Compare values
    if (typeof a != 'object')
      return (a === b);
    
    for (let key in a)
      if (!(key in b))
        return false;
      else if (b [key] !== a [key])
        return false;
    
    return true;
  }
  // }}}
  
  // {{{ validate
  /**
   * Make sure a given resource is valid
   * 
   * @param qcREST.Resource resource
   * @param object attributes (optional)
   * @param object options (optional)
   * 
   * @access private
   * @return bool
   **/
  function validate (resource, attributes, options) {
    // Check if there is anything to validate
    if ((options && !options.validate) || !resource.validate)
      return true;
    
    // Create set of attributes to validate
    attributes = extend ({ }, resource.$attributes, attributes);
    
    // Try to validate these attribute
    let error;
    
    try {
      error = resource.validate (attributes, options);
      
      if (!error)
        return true;
    } catch (e) {
      error = e;
    }
    
    // Tigger invalid-event
    resource.trigger ('invalid', resource, error, extend ({ 'validationError' : error }, options));
    
    return false;
  }
  // }}}
  
  root.qcREST.Resource = root.qcREST.Emitter.extend ({
    /* Root-URL for resources of this type */
    'urlRoot' : null,
    
    /* URL of this resource */
    '$url' : null,
    
    /* Collection of this resource */
    '$collection' : null,
    
    /* JSON-Attributes of this resource */
    '$attributes' : null,
    
    /* List of changed JSON-Attributes on this resource */
    '$changedAttributes' : null,
    
    /* Previous JSON-Attributes of this resource */
    '$previousAttributes' : null,
    
    /* This resource is locked during updates */
    '$locked' : false,
    
    /* Attribute carrying ID of this resource */
    'idAttribute' : '_id',
      
    // {{{ __construct
    /**
     * Create a new REST-Resource
     * 
     * @param object attributes
     * @param object options
     * 
     * @access friendly
     * @return void
     **/
    'constructor' : function Resource (attributes, options) {
      // Sanatize input-variables
      attributes = (typeof attributes == 'object' ? attributes : { });
      options = (typeof options == 'object' ? options : { });
      
      if (arguments.length > 0)
        arguments [0] = attributes;
      
      if (arguments.length > 1)
        arguments [1] = options;
      
      // Call pre-initialize
      this.preinitialize.apply (this, arguments);
      
      // Assign default variables
      // TODO: Backbone assigns unique ID here
      this.$url = options.url || null;
      this.$collection = options.collection || null;
      
      // Prepare attributes
      if (options.parse)
        attributes = this.parse (attributes, options);
      
      // Patch in default attributes
      attributes = extend ({ }, attributes, this.defaults ());
      
      // Assign the attributes
      this.set (attributes, extend ({ 'track-changes' : false }, options));
      
      // Call initialize-callback
      this.initialize.apply (this, arguments);
      
      // Clean up callbacks
      delete (this.preinitialize);
      delete (this.initialize);
      delete (this.urlRoot);
    },
    // }}}
    
    // {{{ Resource.preinitialize
    /**
     * Callback to be raised before any initialization is applied to a new resource
     * 
     * @param object attributes
     * @param object options
     * 
     * @access friendly
     * @return void
     **/
    'preinitialize' : function (attributes, options) { },
    // }}}
    
    // {{{ Resource.initialize
    /**
     * Callback to be raised after basic initialization of a new resource
     * 
     * @param object attributes
     * @param object options
     * 
     * @access friendly
     * @return void
     **/
    'initialize' : function (attributes, options) { },
    // }}}
    
    // {{{ Resource.parse
    /**
     * Parse/Pre-Process a set of attributes before applying to resource
     * 
     * @param object attributes
     * @param object options
     * 
     * @access public
     * @return object
    **/
    'parse' : function (attributes, options) {
      return attributes;
    },
    // }}}
    
    // {{{ Resource.defaults
    /**
     * Retrive a default set of attributes for a resource
     * 
     * @access public
     * @return object
     **/
    'defaults' : function () { },
    // }}}
    
    // {{{ Resource.toJSON
    /**
     * Return a copy of all attributes of this resource
     * 
     * @access public
     * @return object
     **/
    'toJSON' : function () {
      return clone (this.$attributes);
    },
    // }}}
    
    // {{{ Resource.matches
    /**
     * Check if this resource matches a given set of attributes
     * 
     * @param object attributes
     * 
     * @access public
     * @return bool
     **/
    'matches' : function (attributes) {
      return match (attributes, this.$attributes);
    },
    // }}}
    
    // {{{ Resource.keys
    /**
     * Retrive all attribute-keys we have
     * 
     * @access public
     * @return array
     **/
    'keys' : function () {
      return Object.keys (this.$attributes);
    },
    // }}}
    
    // {{{ Resource.has
    /**
     * Check if a attribute is known on this resource
     * 
     * @param string attribute
     * 
     * @access public
     * @return bool
     **/
    'has' : function (attribute) {
      return (typeof this.$attributes [attribute] != 'undefined');
    },
    // }}}
    
    // {{{ Resource.get
    /**
     * Retrive an attribute from this resource
     * 
     * @param string attribute
     * 
     * @access public
     * @return mixed
     **/
    'get' : function (attribute) {
      return this.$attributes [attribute];
    },
    // }}}
    
    // {{{ getID
    /**
     * Retrive the value of the ID-Attribute of this resource
     * 
     * @access public
     * @return mixed
     **/
    'getID' : function () {
      // Try to determine id-attribute
      let idAttribute = this.idAttribute;
      
      // Ask collection if it's aware of our ID-Attribute
      if (!idAttribute && this.$collection)
        idAttribute = this.$collection.getIDAttribute ();
      
      // Fallback to hardcoded default
      if (!idAttribute || !this.has (idAttribute))
        idAttribute = 'id';
      
      // Forward the call to our getter
      return this.get (idAttribute);
    },
    // }}}
    
    // {{{ escape
    /**
     * Retrive an attribute from this resource in escaped form
     * 
     * @param string attribute
     * 
     * @access public
     * @return string
     **/
    'escape' : function (attribute) {
      let elem = document.createElement ('div');
      
      elem.innerText = this.get (attribute);
      
      return elem.innerHTML;
    },
    // }}}
    
    // {{{ set
    /**
     * Change a single or a set of attributes of this resource
     * 
     * @param object attributes
     * @param object options (optional)
     * 
     * - OR -
     * 
     * @param string attribute
     * @param mixed value
     * @param object options (optional)
     * 
     * @access public
     * @return qcREST.Resource
     **/
    'set' : function (attributes, options) {
      // Check if there is anything to do
      if (attributes === null)
        return this;
      
      // Check if we were called as set (key, value, options)
      if (typeof attributes != 'object') {
        // Make sure we have sufficient data
        if (arguments.length < 2)
          return this;
        
        // Rewrite attributes
        let attribute = arguments [0];
        
        (attributes = { })[attribute] = arguments [1];
        
        // Rewrite options
        if (arguments.length > 2)
          options = arguments [2];
        else
          options = { };
      
      // Sanatize parameters
      } else {
        attributes = attributes || { };
        options = options || { };
      }
      
      // Validate attributes
      if (!validate (this, attributes, options))
        return null;
      
      // Keep a copy of last attributes
      let locked = !!this.$locked;
      
      if (!locked) {
        if (this.$previousAttributes === null)
          this.$previousAttributes = clone (this.$attributes);
        
        if (this.$changedAttributes === null)
          this.$changedAttributes = { };
        
        this.$locked = true;
      }
      
      let changes = [ ];
      
      if (this.$attributes === null)
        this.$attributes = { };
      
      for (let attribute in attributes) {
        // Track changes
        if (this.$previousAttributes && match (attributes [attribute], this.$previousAttributes [attribute]))
          delete (this.$changedAttributes [attribute]);
        else
          this.$changedAttributes [attribute] = attributes [attribute];
        
        if (!match (attributes [attribute], this.$attributes [attribute]))
          changes.push (attribute);
        else
          continue;
        
        // Commit the change
        if ((attributes [attribute] !== undefined) && !options.unset)
          this.$attributes [attribute] = attributes [attribute];
        else
          delete (this.$attributes [attribute]);
      }
      
      // Trigger events
      if (!options.silent)
        for (let attribute of changes)
          this.trigger ('change:' + attribute, this, this.$attributes [attribute], options);
      
      if (!locked) {
        this.$locked = false;
        
        if (!options.silent)
          this.trigger ('change', this, options);
      }
      
      return this;
    },
    // }}}
    
    // {{{ Resource.unset
    /**
     * Remove an attribute from this resource
     * 
     * @param string attribute
     * @param object options
     * 
     * @access public
     * @return Resource
     **/
    'unset' : function (attribute, options) {
      return this.set (attribute, undefined, options);
    },
    // }}}
    
    // {{{ clear
    /**
     * Clear/Remove all attributes of this resource
     * 
     * @access public
     * @return void
     **/
    'clear' : function (options) {
      let attributes = { };
      
      for (let key in this.$attributes)
        attributes [key] = undefined;
      
      return this.set (attributes, options);
    },
    // }}}
    
    // {{{ hasChanged
    /**
     * Check if this resource or one of its attributes was changed locally
     * 
     * @param string attribute (optional)
     * 
     * @access public
     * @return bool
     **/
    'hasChanged' : function (attribute) {
      if (!attribute)
        return (Object.keys (this.$changedAttributes).length > 0);
      
      return (attribute in this.$changedAttributes);
    },
    // }}}
    
    // {{{ changedAttributes
    /**
     * Retrive a set of changed attributes
     * 
     * @param object diff (optional) Changes to compare with
     * 
     * @remark Compared with Backbone, we return an empty object
     *         if there were no changes while Backbone returns false
     * 
     * @access public
     * @return object
     **/
    'changedAttributes' : function (diff) {
      // Check wheter to return all changed attributes
      if (!diff)
        return clone (this.$changedAttributes);
      
      // Get attributes to compare with
      let attributes = (this.locked ? this.$previousAttributes : this.$attributes);
      let result = { };
      
      for (let key in diff)
        if (!match (diff [key], attributes [key]))
          result [key] = diff [key];
      
      // Return the result
      return result;
    },
    // }}}
    
    // {{{ previous
    /**
     * Retrive the previous value of an attribute that was changed
     * 
     * @param string attribute
     * 
     * @remark Compared with backbone we return undefined if the
     *         attribute does not exists or wasn't changed yet
     *         while backbone returns null.
     * 
     * @access public
     * @return mixed
     **/
    'previous' : function (attribute) {
      if (!this.$previousAttributes)
        return undefined;
      
      return this.$previousAttributes [attribute];
    },
    // }}}
    
    // {{{ previousAttributes
    /**
     * Retrive previous attributes of this resource
     * 
     * @access public
     * @return object
     **/
    'previousAttributes' : function () {
      if (!this.$previousAttributes)
        return undefined;
       
      return clone (this.$previousAttributes);
    },
    // }}}
    
    // {{{ clone
    /**
     * Create a copy of this resource
     * 
     * @access public
     * @return Resource
     **/
    'clone' : function () {
      return new this.constructor (this.$attributes);
    },
    // }}}
    
    // {{{ fetch
    /**
     * Fetch a fresh copy of this resource
     * 
     * @param object options (optional)
     * 
     * @access public
     * @return Promise
     **/
    'fetch' : function (options) {
      // Parse response by default
      options = extend ({ 'parse' : true }, options);
      
      // Request the resource
      let self = this;
      
      return qcREST.request (
        (options.url ? options.url : this.url ()),
        'GET'
      ).then (
        function (result) {
          // Store URL from options if there was one
          if (options.url)
            self.$url = options.url;
          
          // Try to assign the attributes
          if (!self.set ((options.parse ? self.parse (result.body) : result.body), options))
            throw 'Failed to set attributes';
          
          // Trigger sync-event
          self.trigger ('sync', self, result.body, options);
          
          // Forward the result
          return result;
        }
      );
    },
    // }}}
    
    // {{{ save
    /**
     * Save this resource to server / persistance-layer
     * 
     * @param object options (optional)
     * 
     * - OR -
     * 
     * @param object attributes (optional)
     * @param object options (optional)
     * 
     * - OR -
     * 
     * @param string key (optional)
     * @param mixed value (optional)
     * @param object options (optional)
     * 
     * @access public
     * @return Promise
     **/
    'save' : function (key, value, options) {
      // Process parameters
      let attributes = { },
          self = this;
      
      if ((value === undefined) && (options === undefined))
        options = key;
      else if (typeof key == 'object') {
        attributes = key;
        options = value;
      } else
        attributes [key] = value;
      
      // Patch option-values in
      options = extend ({ 'validate' : true, 'parse' : true }, options);
      
      // Check wheter to assign attributes before they are submitted to server
      let assignBefore = !!options.wait;
      
      if (attributes && assignBefore) {
        if (!this.set (attributes, options))
          return Promise.reject ('Failed to assign attributes');
      } else if (!validate (this, attributes, options))
        return Promise.reject ('Failed to validate attributes');
      
      // TODO: Silently assign attributes here if we weren't assigning before?
      
      attributes = extend ({ }, (this.isNew () || !options.patch ? this.$attributes : null), options.attrs, attributes);
      
      return qcREST.request (
        (options.url ? options.url : this.url ()),
        (this.isNew () ? 'POST' : (options.patch ? 'PATCH' : 'PUT')),
        {
          'Content-Type' : 'application/json'
        },
        JSON.stringify (attributes)
      ).then (
        function (result) {
          // Check if we received a new URL
          if (self.isNew () && result.headers.location) {
            self.$url = result.headers.location;
            
            if (self.idAttribute && !self.has (self.idAttribute))
              self.set (self.idAttribute, self.$url.substr (self.$url.lastIndexOf ('/') + 1));
          }
          
          // Push attributes from server
          if (result.body !== '')
            attributes = extend ({ }, attributes, (options.parse ? self.parse (result.body) : result.body));
          
          if (attributes && !assignBefore && !self.set (attributes, options))
            throw 'Failed to assign attributes';
          
          if (self.$changedAttributes !== null)
            for (let key in attributes)
              delete (self.$changedAttributes [key]);
          
          // Trigger sync-event
          self.trigger ('sync', self, result.body, options);
          
          // Forward the result
          return result;
        }
      );
    },
    // }}}
    
    // {{{ destroy
    /**
     * Destroy this resource at our persistance-layer
     * 
     * @param object options (optional)
     * 
     * @access public
     * @return Promise
     **/
    'destroy' : function (options) {
      // Check if we can do anything
      if (!(options && options.url) && this.isNew ())
        return Promise.resolve ({ });
      
      // Do the request
      let self = this;
      
      return qcREST.request (
        (options && options.url ? options.url : this.url ()),
        'DELETE'
      ).then (
        function (result) {
          // Invalidate our URL
          self.$url = null;
          
          // Trigger events
          self.trigger ('destroy', self, self.$collection, options);
          self.trigger ('sync', self, result.body, options);
          
          // Forward the result
          return result;
        }
      );
    },
    // }}}
    
    // {{{ url
    /**
     * Retrive a meaningful URL for this resource
     * 
     * @access public
     * @return string
     **/
    'url' : function () {
      // Check for an explicit URL
      if (this.$url !== null)
        return this.$url;
      
      let base = (this.$collection !== null ? this.$collection.url () : null) || this.__proto__.urlRoot;
      
      if (!base)
        return null;
      
      if (this.isNew ())
        return base;
      
      if (base.substr (base.length - 1, 1) != '/')
        base += '/';
      
      return base + encodeURIComponent (this.getID ());
    },
    // }}}
    
    // {{{ isNew
    /**
     * Check if this resource is about to be created newly
     * 
     * @access public
     * @return bool
     **/
    'isNew' : function () {
      return (this.idAttribute && !this.has (this.idAttribute)) || (this.$url === null);
    },
    // }}}
    
    // {{{ isValid
    /**
     * Check if this resource is valid
     * 
     * @access public
     * @return bool
     **/
    'isValid' : function (options) {
      return validate (this, { }, extend ({ 'validate' : true }, options));
    }
    // }}}
  });
  
  // Hide the resource behind a nice proxy
  root.qcREST.Resource = new Proxy (root.qcREST.Resource, {
    'construct' : function (target, args) {
      let obj = Object.create (target.prototype);
      
      target.apply (obj, args);
      
      return new Proxy (obj, {
        'get' : function (obj, attribute) {
          // TODO: clone() breaks the proxy
          
          if (typeof obj [attribute] != 'undefined')
            return obj [attribute];
          
          return obj.get (attribute);
        },
        'set' : function (obj, attribute, value) {
          if (typeof obj [attribute] != 'undefined')
            return (obj [attribute] = value);
          
          return obj.set (attribute, value, { });
        }
      });
    },
    'get' : function (obj, attribute) {
      if ((attribute == 'constructor') && !obj.hasOwnProperty ('constructor'))
        return obj;
      
      return obj [attribute];
    }
  });
})(self);
