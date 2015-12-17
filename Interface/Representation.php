<?PHP

  interface qcREST_Interface_Representation extends Countable, ArrayAccess, Traversable {
    // {{{ setStatus
    /**
     * Force a specific status for the output
     * 
     * @param enum
     * 
     * @access public
     * @return void
     **/
    public function setStatus ($Status);
    // }}}
    
    // {{{ allowRedirect
    /**
     * Define wheter redirects are allowed on this representation
     * 
     * @param bool $SetRedirect (optional)
     * 
     * @access public
     * @return bool
     **/
    public function allowRedirect ($SetRedirect = null);
    // }}}
    
    // {{{ addMeta
    /**
     * Register a meta-value for this representation
     * 
     * @param string $Key
     * @param string $Value
     * 
     * @access public
     * @return void
     **/
    public function addMeta ($Key, $Value);
    // }}}
    
    // {{{ setPreferedOutputTypes
    /**
     * Set a list of prefered output-types
     * 
     * @param array $Preferences
     * 
     * @access public
     * @return void
     **/
    public function setPreferedOutputTypes (array $Preferences);
    // }}}
    
    // {{{ toArray
    /**
     * Create an array from this representation
     * 
     * @access public
     * @return array
     **/
    public function toArray ();
    // }}}
  }

?>