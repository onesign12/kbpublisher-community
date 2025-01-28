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

class AuthHttp
{
    
    var $realm = 'Restricted area';
    var $do_auth = true;
    var $login;
    var $password;
    
    
    function auth() {
        if($this->do_auth === false) { return; }
        
        if (!isset($_SERVER['PHP_AUTH_USER']) ||
            @$_SERVER['PHP_AUTH_USER'] != $this->login || 
            @$_SERVER['PHP_AUTH_PW'] != $this->password) {
            
            $this->getHeader();
        }
    }
    
    
    function quickAuth($login, $password) {
        $a = new HttpAuth;
        $a->login = $login;
        $a->password = $password;
        $a->auth();
    }
    
    
    function unsetAuth() {
        unset($_SERVER['PHP_AUTH_USER']);
        unset($_SERVER['PHP_AUTH_PW']);
    }
    
    
    function getHeader() {
        header("WWW-Authenticate: Basic realm=\"{$this->realm}\"");
        header("HTTP/1.0 401 Unauthorized");
        exit();
    }
}
?>