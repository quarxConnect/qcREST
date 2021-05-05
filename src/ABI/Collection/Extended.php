<?php

  /**
   * qcREST - Extended Collection Interface
   * Copyright (C) 2016-2021 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  declare (strict_types=1);

  namespace quarxConnect\REST\ABI\Collection;
  use \quarxConnect\Events;
  
  interface Extended {
    public const SORT_ORDER_ASCENDING = 0;
    public const SORT_ORDER_DESCENDING = 1;
    
    // {{{ getChildrenCount
    /**
     * Retrive the total number of children matching the last getChildren()-Request
     * 
     * @access public
     * @return int
     **/
    public function getChildrenCount () : int;
    // }}}
    
    // {{{ setSlice
    /**
     * Define a slice to return by getChildren()-Calls
     * 
     * @param int $sliceOffset (optional)
     * @param int $sliceLength (optional)
     * 
     * @access public
     * @return void
     **/
    public function setSlice (int $sliceOffset = 0, int $sliceLength = null) : void;
    // }}}
    
    // {{{ setSorting
    /**
     * Define a sorting of children returned by getChildren()-Calls
     * 
     * @param string $sortField Sort by this field
     * @param enum $sortOrder (optional) Return sorted in this order
     * 
     * @access public
     * @return void
     **/
    public function setSorting (string $sortField, int $sortOrder = Extended::SORT_ORDER_ASCENDING) : void;
    // }}}
    
    // {{{ setSearchPhrase
    /**
     * Store a search-phrase from a query for later getChildren()-Calls
     * 
     * @param string $searchPhrase The Phrase to filter children with
     * 
     * @access public
     * @return void
     **/
    public function setSearchPhrase (string $searchPhrase) : void;
    // }}}
    
    // {{{ setNames
    /**
     * Store a set of names to retrive on later getChildren()-Calls
     * 
     * @param array $childNames
     * 
     * @access public
     * @return void
     **/
    public function setNames (array $childNames) : void;
    // }}}
    
    // {{{ resetParameters
    /**
     * Reset any search/sort/limit-parameters applied to this collection
     * 
     * @access public
     * @return void
     **/
    public function resetParameters () : void;
    // }}}
  }
