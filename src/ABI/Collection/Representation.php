<?php

  /**
   * qcREST - Special Resource Representation on Collections Interface
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
  
  interface Representation {
    // {{{ getCollectionRepresentation
    /**
     * Retrive an additional representation from this resource to be included in collection-output
     * 
     * @access public
     * @return Events\Promise
     **/
    public function getCollectionRepresentation () : Events\Promise;
    // }}}
  }
