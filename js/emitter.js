(function (root) {
  // Make sure qcREST is setup in root
  root.qcREST = root.qcREST || { };
  
  if (!root.qcREST.Extendable)
    throw 'Missing support for Extenabdles';
  
  // {{{ qcREST.Emitter
  root.qcREST.Emitter = root.qcREST.Extendable.extend ({
    // {{{ __construct
    /**
     * Create a new event-emitter
     * 
     * @remark This function is empty for documentation-purposes
     * 
     * @access friendly
     * @return void
     **/
    'constructor' : function Emitter () { },
    // }}}
    
    'on' : function (eventName, callback, context = null) {
      // Catch senseless calls
      if (!eventName || (typeof callback != 'function'))
        return;
      
      // Split up event-names
      let events = eventName.split (' ');
      
      // Make sure we have a dictionary for events
      if (typeof this.$events != 'object')
        this.$events = { };
      
      // Register all callbacks
      for (let event of events) {
        // Make sure we have a collection for this event
        if ((typeof this.$events [event] != 'object') ||
            !Array.isArray (this.$events [event]))
          this.$events [event] = [ ];
        
        // Push to collection
        this.$events [event].push ([ callback, context ]);
      }
    },
    'once' : function (eventName, callback, context = null) {
      // Check for a dictionary of events
      if (typeof eventName == 'object') {
        for (let event in eventName)
          this.once (event, eventName [event], callback);
        
        return;
      }
      
      // Bind single event (or multiple events on one string)
      let self = this,
        cb = function () {
          // Forward the event
          callback.apply (context, arguments);
          
          // Unregister the event
          self.off (eventName, cb, context);
        };
      
      return this.on (eventName, cb, context);
    },
    'off' : function (eventName = null, callback = null, context = null) {
      // Make sure we have a dictionary for events
      if (typeof this.$events != 'object')
        return;
      
      // Try to safe some time
      if ((eventName === null) && (callback === null) && (context === null)) {
        delete (this.$events);
        
        return;
      }
      
      // Split up event-names
      let events;
      
      if ((eventName === null) || (eventName === undefined))
        events = Object.keys (this.$events);
      else
        events = eventName.split (' ');
      
      for (let event of events) {
        // Check if this event is known at all
        if ((typeof this.$events [event] != 'object') ||
            !Array.isArray (this.$events [event]))
          continue;
        
        // Check wheter to filter for a callback and/or context
        if ((callback === null) && (context === null)) {
          delete (this.$events [event]);
          
          continue;
        }
        
        // Try to remove the callback
        this.$events [event] = this.$events [event].filter (
          function (spec) {
            return ((spec [0] !== callback) || (spec [1] !== context));
          }
        );
      }
    },
    'trigger' : function (eventName) {
      // Make sure we have a dictionary for events
      if (typeof this.$events != 'object')
        return;
      
      // Extract arguments to pass
      let args = Array.prototype.slice.call (arguments, 1);
      
      // Check if we have event-handlers for this event
      if ((typeof this.$events [eventName] == 'object') &&
          Array.isArray (this.$events [eventName]))
        // Raise all callbacks
        for (let spec of this.$events [eventName])
          spec [0].apply (spec [1] || this, args);
      
      // Check wheter to fire an additional all-event
      if (eventName != 'all') {
        args.unshift ('all', eventName);
        
        this.trigger.apply (this, args);
      }
    },
    'listenTo' : function (emitter, eventName, callback) {
      // TODO
    },
    'listenToOnce' : function (emitter, eventName, callback) {
      // TODO
    },
    'stopListening' : function (emitter, eventName, callback) {
      // TODO
    }
  });
  // }}}
})(self);
