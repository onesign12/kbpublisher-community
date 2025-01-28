<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+




class Person extends AppObj
{
    
    var $full_name;
    var $short_name;
    var $email_name;    
    
    
    function setNames() {
        $this->setFullName();
        $this->setShortName();
        $this->getEmailName();
    }
    
    
    // Slazo John Haert
    function getFullName() {
        if(empty($this->full_name)) { $this->setFullName(); }
        return $this->full_name;
    }
    
    function setFullName() {
        $this->full_name = PersonHelper::getFullName($this->properties['first_name'], 
                                                     $this->properties['middle_name'], 
                                                     $this->properties['last_name']);
    }
    
    
    // Scalzo E.D.
    function getShortName() {
        if(empty($this->short_name)) { $this->setShortName(); }
        return $this->short_name;
    }
    
    function setShortName() {
        $this->short_name = PersonHelper::getShortName($this->properties['first_name'], 
                                                       $this->properties['middle_name'], 
                                                       $this->properties['last_name']);
    }
    
    
    // John Scalzo
    function getEmailName() {
        if(empty($this->email_name)) { $this->setEmailName(); }
        return $this->email_name;
    }
    
    function setEmailName() {
        $this->email_name = PersonHelper::getEmailName($this->properties['first_name'],  
                                                       $this->properties['last_name']);
    }
}
?>
