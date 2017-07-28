(function () {
  /**
   * Generic Backbone Model-Implementation for use with qcREST
   **/
  self.qcREST = self.qcREST || { };
  self.qcREST.Model = Backbone.Model.extend ({
    parse : function (json) {
      // Check if the json is from a collection-call
      if ((typeof json._id == 'undefined') || (typeof json._href == 'undefined') || (typeof json._collection == 'undefined'))
        return json;
      
      // Remember meta-data from the collection
      this.url = json._href
      this.isCollection = json._collection;
      
      // Remove this meta-information
      delete (json._href);
      delete (json._collection);
      
      return json;
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
      return model [this.idAttribute || '_id'];
    },
    parse : function (json) {
      // Store idAttribute
      this.idAttribute = json.idAttribute;
      
      // Return the items
      return json.items;
    }
  });
})();
