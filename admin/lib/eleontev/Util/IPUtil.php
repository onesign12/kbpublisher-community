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


class IPUtil
{

    var $ip;
    var $ip_long;
    var $ip_range = array();
    var $ip_range_long = array();
    

    function __construct($ip = false) {
        $this->setIP($ip);
    }
    
    
    static function isValidQuick($valid_ips, $ip = false) {
        $ip = new IPUtil($ip);
        
        if($valid_ips) {
            $ip->setRange($valid_ips);        
        }
        
        return $ip->isValid();    
    }
    
    
    function isValid() {
        
        if(!$this->ip_range) {
            return true;
        }
        
        if(in_array($this->ip, $this->ip_range)) {
            return true;
        }
        
        foreach($this->ip_range_long as $k => $ip) {
            if(is_array($ip)) {
                if($this->ip_long >= $ip[0] && $this->ip_long <= $ip[1]) {
                    return true;
                }                
            }
        }
        
        return false;
    }
        
    
    function setIP($ip) {
        $this->ip = ($ip) ? long2ip(ip2long($ip)) : IPUtil::getIP();
        $this->ip_long = ip2long($this->ip);
    } 
    
    
    function setRange($ip) {
        static $i = 1;
        
        $ip = explode(';', $ip);
        foreach($ip as $k => $v) {
            if(strpos($v, '-') !== false) {
                foreach(explode('-', $v) as $k1 => $v1) {
                    $this->ip_range[$i][$k1] = long2ip(ip2long(trim($v1)));
                    $this->ip_range_long[$i][$k1] = ip2long(trim($v1));        
                }
            
            } else {
                $this->ip_range[$i] = long2ip(ip2long(trim($v)));
                $this->ip_range_long[$i] = ip2long(trim($v));
            }
        
            $i++;
        }
    }    

    static function getIP() {
        return WebUtil::getIP();
    }
}



/*
$ips = '192.168.1.1-192.168.255.255';

$ip = new IPUtil();
$ip->setRange($ips);
$ip->setRange(IPUtil::getIP());
$ip->setRange('234.132.23.1');

$ip->isValid();

echo "<pre>"; print_r($ip); echo "</pre>";
*/
?>