<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007-2021 Evgeny Leontev                                    |
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

require_once 'Cache/Lite.php';


class AppCache extends Cache_lite
{
    
    //24 hours - 84400, 12 hours - 43200, 1 hour - 3600;
    
    var $default_options = array(
        'caching'  => true,
        'cacheDir' => APP_CACHE_DIR,
        'lifeTime' => 84400
    );
    
    
    function setDefaultOptions($options = array()) {
        foreach($this->default_options as $k => $v) {
            $val = (isset($options['caching'])) ? $options['caching'] : $v;
            $this->setOption($k, $val);
        }
    }
    
    
    function getCacheId($name, $view = 'index', $category_id = false, $entry_id = false, $priv_id = true, $role_id = true) {
        
        $priv_id = ($priv_id === true) ? AuthPriv::getPrivId() : $priv_id;
        
        // no need role for admin
        $role_id = ($role_id === true) ? AuthPriv::getRoleId() : $role_id;    
        if(AuthPriv::isAdmin()) {
            $role_id = 'admin';
        }

        return  $name . $view  . $category_id . $entry_id . $priv_id . $role_id;
    }
    
    
    function getUserCacheId($name, $priv_id = true, $role_id = true) {
        $priv_id = ($priv_id === true) ? AuthPriv::getPrivId() : $priv_id;
        $role_id = ($role_id === true) ? AuthPriv::getRoleId() : $role_id;

        return  $name . $priv_id . $role_id;
    }
    
    
    //function 123() {
        //timestart('getCategoryListCache');
        //timestop('getCategoryListCache');
    //}
}
?>