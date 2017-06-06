<?PHP

  /**
   * qcREST - Response
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
  
  require_once ('qcREST/Interface/Response.php');
  
  class qcREST_Response implements qcREST_Interface_Response {
    private $Request = null;
    private $Status = qcREST_Interface_Response::STATUS_OK;
    private $Meta = array ();
    private $ContentType = 'application/octet-stream';
    private $Content = '';
    
    // {{{ __construct
    /**
     * Create a new response-object
     * 
     * @param qcREST_Interface_Request $Request Request-Object for this response
     * @param int $Status
     * @param string $Content (optional)
     * @param string $ContentType (optional)
     * @param array $Meta (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (qcREST_Interface_Request $Request, $Status, $Content = '', $ContentType = null, array $Meta = array ()) {
      // Set content-type to some default
      if (($ContentType === null) && ($Request->getMethod () != $Request::METHOD_OPTIONS))
        $ContentType = 'application/octet-stream';
      
      $this->Request = $Request;
      $this->Status = (int)$Status;
      $this->Meta = $Meta;
      $this->Content = $Content;
      $this->ContentType = $ContentType;
    }
    // }}}
    
    // {{{ getRequest
    /**
     * Retrive the request this response is generated for
     * 
     * @access public
     * @return qcREST_Interfaces_Request
     **/
    public function getRequest () {
      return $this->Request;
    }
    // }}}
    
    // {{{ getStatus
    /**
     * Retrive the status of the response
     * 
     * @access public
     * @return enum
     **/
    public function getStatus () {
      return $this->Status;
    }
    // }}}

    // {{{ getMeta
    /**
     * Retrive the meta-data of this response
     * 
     * @access public
     * @return array
     **/
    public function getMeta () {
      return $this->Meta;
    }
    // }}}

    // {{{ getContentType
    /**
     * Retrive the content-type of the response
     * 
     * @access public
     * @return string
     **/
    public function getContentType () {
      return $this->ContentType;
    }
    // }}}

    // {{{ getContent
    /**
     * Retrive the whole response-data
     * 
     * @access public
     * @return string
     **/
    public function getContent () {
      return $this->Content;
    }
    // }}}
  }

?>