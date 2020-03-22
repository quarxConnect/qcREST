(function (root) {
  // Make sure qcREST is setup in root
  root.qcREST = root.qcREST || { };
  
  // {{{ REST.request
  /**
   * Perform simple REST-Requests
   * 
   * @param string url
   * @param string method (optional)
   * @param object headers (optional)
   * @param mixed body (optional)
   * 
   * @access public
   * @return Promise
   **/
  root.qcREST.request = function (url, method = 'GET', headers = { }, body = null) {
    // Make sure we have promises in place
    if (!Promise)
      throw 'No Promise-Support here';
    
    // Make sure we have XMLHttpRequest in place
    if (!XMLHttpRequest)
      return Promise.reject ('No XMLHttpRequest-Support here');
    return new Promise (function (resolve, reject) {
      // Create a new request
      let xhr = new XMLHttpRequest;
      
      xhr.open (method, url);
      
      // Setup event-handlers
      xhr.onabort = function () {
        // Check for a race-condition
        if (!xhr)
          return;
        
        xhr = null;
        
        // Reject the promise
        reject ('Request aborted');
      };
      
      xhr.ontimeout = function () {
        // Check for a race-condition
        if (!xhr)
          return;
        
        xhr = null;
        
        // Reject the promise
        reject ('Request timeout');
      };
      
      xhr.onerror = function () {
        // Check for a race-condition
        if (!xhr)
          return;
        
        xhr = null;
        
        // Reject the promise
        reject ('Request error');
      };
      
      xhr.onload = function () {
        // Check for a race-condition
        if (!xhr)
          return;
        
        let req = xhr;
        xhr = null;
        
        // Process the response
        let headers = { };
        
        req.getAllResponseHeaders ().trim ().split (/[\r\n]+/).forEach (
          function (header) {
            let p = header.indexOf (': ');
            
            if (p < 0)
              return;
            
            headers [header.substr (0, p)] = header.substr (p + 2);
          }
        );
        
        let body = req.response,
            contentType = req.getResponseHeader ('Content-Type');
        
        if (contentType == 'application/json')
          body = JSON.parse (body);
        
        // Fullfill the promise
        resolve ({ 'body' : body, 'headers' : headers });
      };
      
      // Push headers to request
      if ('setRequestHeader' in xhr)
        for (let key in headers)
          xhr.setRequestHeader (key, headers [key]);
      
      // Submit the request
      if (body)
        xhr.send (body);
      else
        xhr.send ();
    });
  };
  // }}}
})(self);
