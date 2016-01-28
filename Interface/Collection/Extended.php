<?PHP

  /**
   * qcREST - Extended Collection Interface
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
  
  interface qcREST_Interface_Collection_Extended {
    const SORT_ORDER_ASCENDING = 0;
    const SORT_ORDER_DESCENDING = 1;
    
    // {{{ getChildrenCount
    /**
     * Retrive the total number of children matching the last getChildren()-Request
     * 
     * @access public
     * @return int
     **/
    public function getChildrenCount ();
    // }}}
    
    // {{{ setSlice
    /**
     * Define a slice to return by getChildren()-Calls
     * 
     * @param int $Offset (optional)
     * @param int $Count (optional)
     * 
     * @access public
     * @return bool
     **/
    public function setSlice ($Offset = 0, $Count = null);
    // }}}
    
    // {{{ setSorting
    /**
     * Define a sorting of children returned by getChildren()-Calls
     * 
     * @param string $Field Sort by this field
     * @param enum $Order (optional) Return sorted in this order
     * 
     * @access public
     * @return bool
     **/
    public function setSorting ($Field, $Order = qcREST_Interface_Collection_Extended::SORT_ORDER_ASCENDING);
    // }}}
    
    // {{{ setSearchPhrase
    /**
     * Store a search-phrase from a query for later getChildren()-Calls
     * 
     * @param string $Phrase The Phrase to filter children with
     * 
     * @access public
     * @return bool
     **/
    public function setSearchPhrase ($Phrase);
    // }}}
    
    // {{{ resetParameters
    /**
     * Reset any search/sort/limit-parameters applied to this collection
     * 
     * @access public
     * @return void
     **/
    public function resetParameters ();
    // }}}
  }

?>