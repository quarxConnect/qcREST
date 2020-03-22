(function (root) {
  // Make sure qcREST is setup in root
  root.qcREST = root.qcREST || { };
  
  // {{{ extend
  /**
   * Enhance a given object by the attributes of other objects
   * 
   * @param object destination
   * @param object ...
   * 
   * @access private
   * @return object
   **/
  function extend (destination) {
    for (let i = 1; i < arguments.length; i++) {
      if ((typeof arguments [i] != 'object') &&
          (typeof arguments [i] != 'function'))
        continue;
      
      for (let key in arguments [i])
        destination [key] = arguments [i][key];
    }
    
    return destination;
  }
  // }}}
  
  // {{{ extendOwn
  /**
   * Enhance a given object by the own attributes of other objects
   * 
   * @param object destination
   * @param object ...
   * 
   * @access private
   * @return object
   **/
  function extendOwn (destination) {
    for (let i = 1; i < arguments.length; i++) {
      if ((typeof arguments [i] != 'object') &&
          (typeof arguments [i] != 'function'))
        continue;
      
      for (let key of Object.keys (arguments [i]))
        destination [key] = arguments [i][key];
    }
    
    return destination;
  }
  // }}}
  
  // {{{ __construct
  /**
   * Empty constructor for extendable objects
   * 
   * @access friendly
   * @return void
   **/
  root.qcREST.Extendable = function () { };
  // }}}
  
  // {{{ qcREST.Extendable.extend
  /** 
   * Extend this class
   * 
   * @access public
   * @return object
   **/
  root.qcREST.Extendable.extend = function (objProps, clsProps) {
    let parent = this,
        child;
    
    // Create the child-constructor
    if (objProps && objProps.hasOwnProperty ('constructor'))
      child = objProps.constructor;
    else
      child = function anon_constructor () { return parent.apply (this, arguments); };
    
    extend (
      child,
      parent,
      clsProps,
      {
        '__super__' : parent.prototype,
        'prototype' : extendOwn (Object.create (parent.prototype), objProps, { 'constructor' : child })
      }
    );
    
    return child;
  };
  // }}}
})(self);
