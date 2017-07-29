(function () {
  /**
   * Generic Backbone Model-Implementation for use with qcREST
   **/
  self.qcREST = self.qcREST || { };
  self.qcREST.Model = Backbone.Model.extend ({
    constructor : function () {
      this.permissions = {
        read : true, 
        write : true,
        delete : true
      };
      
      Backbone.Model.apply (this, arguments);
    },
    parse : function (json) {
      // Check if the json is from a collection-call
      if (!json || (typeof json._id == 'undefined') || (typeof json._href == 'undefined') || (typeof json._collection == 'undefined'))
        return json;
      
      // Remember meta-data from the collection
      this.id = json._id;
      this.url = json._href;
      this.isCollection = json._collection;
      this.permissions = json._permissions;
      
      // Make sure permissions are initilized
      if (!this.permissions)
        this.permissions = {
          read : true,
          write : true,
          delete : true
        };
      
      // Remove this meta-information
      delete (json._href);
      delete (json._collection);
      delete (json._permissions);
      
      return json;
    },
    isNew : function () {
      return !this.getId () && (!this.url || (this.url.substring (this.url.length - 1, 1) == '/'));
    },
    getId : function () {
      if (this.idAttribute && this.has (this.idAttribute))
        return this.get (this.idAttribute);
      
      if (this.collection && this.collection.idAttribute && this.has (this.collection.idAttribute))
        return this.get (this.collection.idAttribute);
      
      if (this.id)
        return this.id;
      
      if (this.url) {
        var p = this.url.split ('/');
        
        if (p [p.length - 1].length > 0)
          return p [p.length - 1];
      }
    }
  });
  
  /**
   * Generic Backbone Collection-Implementation for use with qcREST
   **/
  self.qcREST.Collection = Backbone.Collection.extend ({
    initialize : function (models, options) {
      // Push forward a given collection-url
      if (options && options.url) {
        this.url = options.url;
        delete (options.url);
      }
    },
    model : self.qcREST.Model,
    modelId : function (model) {
      if (typeof model == 'undefined')
        return;
      
      return model [this.idAttribute] || model ['_id'];
    },
    parse : function (json) {
      // Store idAttribute
      this.idAttribute = json.idAttribute;
      
      // Return the items
      return json.items;
    }
  });
})();
