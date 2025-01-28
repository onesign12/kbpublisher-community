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

// UPD: 4 May 2012, to all in_array() added strict comparsion, eleontev
// UPD: 11 May 2012, replaced all foreach($arr as $k => $v) to foreach(array_keys($arr) as $k)
//      and skip assign if no need to parse, skip this: 
//      if(in_array($k, $skip_keys, 1)) {$arr[$k] = $arr[$k]; continue; }


class RequestDataUtil
{
    
    // static function toInt(&$arr, $keys) {
    //     foreach($keys as $k => $v) {
    //         if(array_key_exists($v, $arr)) {
    //             $arr[$v] = (int) $arr[$v];
    //         }
    //     }
    //
    //     return $arr;
    // }
    
    
    // can parse 2 levels arrays
    static function toInt(&$arr, $keys) {
        foreach($keys as $k => $v) {
            if(array_key_exists($v, $arr)) {
                if(is_array($arr[$v])) {
                    foreach($arr[$v] as $k2 => $v2) {
                        $arr[$v][$k2] = (int) $v2;
                    }
                } else {
                    $arr[$v] = (int) $arr[$v];
                }
            }
        }
    
        return $arr;
    }
    
    
    static function _getRules($type = 'html') {
        
        // order does matter !
        $a['html']['search']  = array('&quot;', '&#039;', '&gt;', '&lt;', '&amp;');
        $a['html']['replace'] = array('"',      "'",      '>',    '<',    '&');
        
        // order does matter !
        //$a['xml']['search']  = array('&amp;', '&quot;', '&apos;', '&apos;',    '&gt;', '&lt;');
        //$a['xml']['replace'] = array('&',     '"',      "'",      '&#039;',    '>',    '<');
        
        $a['xml']['search']  = array('&quot;', '&apos;', '&apos;',    '&gt;', '&lt;');
        $a['xml']['replace'] = array('"',      "'",      '&#039;',    '>',    '<');        
        
        return $a[$type];
    }
        
    
    static function _stripVar(&$value, $magic_quotes_gpc, $rules, $server_check = false) {
        
        // to db
        if(empty($server_check) || $server_check === 'stripslashes' 
                                || $server_check === 'addslashes' 
                                || $server_check === 'skipslashes') {
                                    
            $value = trim(str_replace($rules['search'], $rules['replace'], $value));
            
            if(!$magic_quotes_gpc || $server_check === 'addslashes') {
                if($server_check !== 'stripslashes' && $server_check !== 'skipslashes') {
                    $value = addslashes($value);
                }
            }
            
            // to other sources as email for example
            if($server_check === 'stripslashes') {
                $value = stripslashes($value);
            }
        
        // to display
        } else {
        
            if(is_numeric($value)) { return $value; }
        
            $value = str_replace($rules['search'], $rules['replace'], $value); // for compability
            $value = htmlspecialchars($value);// ENT_QUOTES - both single and double quotes are translated
            
            // only if we show it after form submission
            if($magic_quotes_gpc && $server_check !== 'display') {
                $value = stripslashes($value);
            }
            
            if($server_check === 'stripslashes_display') {
                $value = stripslashes($value);
            }
        }
        
        return $value;
    }    
    
    
    static function &stripVars(&$arr, $skip_keys = array(), $server_check = false) {
        
        $rules = RequestDataUtil::_getRules();
        $magic_quotes_gpc = false; //get_magic_quotes_gpc();

        if(!is_array($arr)) {
            $arr = RequestDataUtil::_stripVar($arr, $magic_quotes_gpc, $rules, $server_check);
            return $arr;
        }
        
        foreach(array_keys($arr) as $k) {
            if(is_array($arr[$k])) {
                $arr[$k] = RequestDataUtil::stripVars($arr[$k], $skip_keys, $server_check);
            
            } elseif(!in_array($k, $skip_keys, 1)) {    
                $arr[$k] = RequestDataUtil::_stripVar($arr[$k], $magic_quotes_gpc, $rules, $server_check);
            }
        }

        //echo "<pre>"; print_r($arr); echo "</pre>";
        return $arr;
    }
    
    
    static function _stripVarXml(&$value, $rules_html, $rules_xml) {
        
        $value = str_replace('&#160;', ' ', $value);
        $value = trim(str_replace($rules_html['search'], $rules_html['replace'], $value));        
        $value = htmlspecialchars($value, ENT_QUOTES);
                
        return $value;
    }    
    
    
    static function &stripVarsXml(&$arr, $skip_keys = array()) {
    // static function stripVarsXml($arr, $skip_keys = array()) {
        
        $rules_html = RequestDataUtil::_getRules('html');
        $rules_xml = RequestDataUtil::_getRules('xml');
        
        if(!is_array($arr)) {
            $arr = RequestDataUtil::_stripVarXml($arr, $rules_html, $rules_xml);
            return $arr;
        }
        
        foreach(array_keys($arr) as $k) {
            if(is_array($arr[$k])) {
                $arr[$k] = RequestDataUtil::stripVarsXml($arr[$k], $skip_keys);

            } elseif(!in_array($k, $skip_keys, 1)) {
                $arr[$k] = RequestDataUtil::_stripVarXml($arr[$k], $rules_html, $rules_xml);
            }
        }

        //echo "<pre>"; print_r($arr); echo "</pre>";
        return $arr;
    }    
    
    
    static function _stripVarEncode(&$value, $server_check = false) {
        
        // to db
        if(empty($server_check)) {
            $value = addslashes(base64_decode($value));

        // to display // only if we show it after form submission
        } else {
            $value = base64_encode($value);
        }
        
        return $value;
    }
    
    
    static function &stripVarsEncode(&$arr, $server_check = false) {

        if(!is_array($arr)) {
            $arr = RequestDataUtil::_stripVarEncode($value, $server_check);
            return $arr;
        }
        
        foreach(array_keys($arr) as $k) {
            if(is_array($arr[$k])) {
                $arr[$k] = RequestDataUtil::stripVarsEncode($arr[$k], $server_check);

            } else {
                $arr[$k] = RequestDataUtil::_stripVarEncode($arr[$k], $server_check);
            }
        }

        return $arr;
    }
    
    
    static function _stripVarHtml(&$value, $magic_quotes_gpc, $rules, $server_check = false) {
        
        // to db
        if(empty($server_check) || $server_check === 'stripslashes' 
                                || $server_check === 'addslashes' 
                                || $server_check === 'skipslashes') {

            if(!$magic_quotes_gpc || $server_check === 'addslashes') {
                if($server_check !== 'stripslashes' && $server_check !== 'skipslashes') {
                    $value = addslashes($value);
                }
            }

            // to other sources as email for example
            if($server_check === 'stripslashes') {
                $value = stripslashes($value);
            }


        // to display // only if we show it after form submission
        } elseif($magic_quotes_gpc && $server_check !== 'display') {
            $value = stripslashes($value);
        }
        
        //echo "<pre>"; print_r($value); echo "</pre>";
        return $value;
    }
    
    
    static function &stripVarsHtml(&$arr, $skip_keys = array(), $server_check = false) {
        
        $rules = RequestDataUtil::_getRules();
        $magic_quotes_gpc = false; //get_magic_quotes_gpc();

        if(!is_array($arr)) {
            $arr = RequestDataUtil::_stripVarHtml($arr, $magic_quotes_gpc, $rules, $server_check);
            return $arr;
        }
        
        foreach(array_keys($arr) as $k) {
            if(is_array($arr[$k])) {
                $arr[$k] = RequestDataUtil::stripVarsHtml($arr[$k], $skip_keys, $server_check);

            } elseif(!in_array($k, $skip_keys, 1)) {
                $arr[$k] = RequestDataUtil::_stripVarHtml($arr[$k], $magic_quotes_gpc, $rules, $server_check);
            }
        }

        return $arr;
    }
    
    
    static function &stripslashes($arr, $skip_keys = array()) {
        
        if(!is_array($arr)) {            
            $arr = stripslashes($arr);
            return $arr;
        }        
        
        foreach(array_keys($arr) as $k){
            if(is_array($arr[$k])) {
                $arr[$k] = RequestDataUtil::stripslashes($arr[$k], $skip_keys);
            
            } elseif(!in_array($k, $skip_keys, 1)) {
                $arr[$k] = stripslashes($arr[$k]);
            }
        }

        return $arr;
    }    
    
    static function &stripslashesObj($arr) {

        if (is_array($arr)) {
            $arr = array_map(array('RequestDataUtil', 'stripslashesObj'), $arr);
        
        } elseif (is_object($arr)) {
            $vars = get_object_vars($arr);
            foreach($vars as $k => $v) {
                $arr->{$k} = RequestDataUtil::stripslashesObj($v);
            }
        
        } else {
            $arr = stripslashes($arr);
        }
     
        return $arr;
    }
    
    
    static function &addslashes($arr, $skip_keys = array()) {
        
        if(!is_array($arr)) {
            $arr = addslashes($arr);
            return $arr;
        }        
        
        foreach(array_keys($arr) as $k){
            if(is_array($arr[$k])) {
                $arr[$k] = RequestDataUtil::addslashes($arr[$k], $skip_keys);
            
            } elseif(!in_array($k, $skip_keys, 1)) {
                $arr[$k] = addslashes($arr[$k]);
            }
        }

        return $arr;
    }
    
    
    static function &addslashesObj($arr) {

        if (is_array($arr)) {
            $arr = array_map(array('RequestDataUtil', 'addslashesObj'), $arr);
        
        } elseif (is_object($arr)) {
            $vars = get_object_vars($arr);
            foreach($vars as $k => $v) {
                $arr->{$k} = RequestDataUtil::addslashesObj($v);
            }
        
        } else {
            $arr = addslashes($arr);
        }
     
        return $arr;
    }
    
    
    static function stripJs($str) {
        $search[] = '#<script[^>]*>.*?<\/script>#si';
        return preg_replace($search, '', $str);
    }
    
    
    static function getIndexText($str) {
        
        // 2014.04.08, added u, without it in French onwindows'à' gives sql error
        $search  = array('/[\n\r\t]/u', '/&nbsp;/u', '/\s{2,}/u');
        $replace = ' ';
        
        $str = preg_replace($search, $replace, strip_tags($str));
        return trim(html_entity_decode($str, ENT_QUOTES, 'UTF-8'));
    }
    
    
    static function _stripVarCurlyBraces(&$value, $server_check = false, $simple = false) {
        
        // to db
        if(empty($server_check)) {
            $value = str_replace(array('&#123;', '&#125;'), array('{', '}'), $value);
        
        // to display
        } else {
            if($simple) {
                $value = str_replace(array('{', '}'), array('&#123;', '&#125;'), $value);
            } else {
                $value = RequestDataUtil::curly_replace_out_script($value);
            }
        }
        
        return $value;
    }
    
    
    static function &stripVarsCurlyBraces(&$arr, $server_check = false, $simple = false) {
        
        if(!is_array($arr)) {
            $arr = RequestDataUtil::_stripVarCurlyBraces($arr, $server_check, $simple);
            return $arr;
        }
        
        foreach(array_keys($arr) as $k){
            if(is_array($arr[$k])) {
                $arr[$k] = RequestDataUtil::stripVarsCurlyBraces($arr[$k], $server_check, $simple);
            } else {
                $arr[$k] = RequestDataUtil::_stripVarCurlyBraces($arr[$k], $server_check, $simple);
            }
        }
        
        return $arr;
    }
    
    
    static function parseCsv($data, $opts) {

        $opts = array_map('preg_quote', $opts);
        $opts = str_replace(array('\\\n', '\\\r', '\\\t'), array("\n", "\r", "\t"), $opts);
        $opts_str = $opts['ft'].$opts['lt'].$opts['oe'];
        $search = sprintf('/[%s]/', $opts_str);
        // $oe = $opts['oe']; // optinaly enclose normally it is always ", readonly in UI
        
        foreach(array_keys($data) as $row) {
            // $data[$row] = array_map(function($cell) use($search, $oe) {
            $data[$row] = array_map(function($cell) use($search) {
                // Cells containing a quote, a comma or a new line will need to be 
                // contained in double quotes.
                // if (preg_match('/["\n,]/', $cell)) {
                if (preg_match($search, $cell)) {
                    // double quotes within cells need to be escaped.
                    return '"' . preg_replace('/"/', '""', $cell) . '"';
                    // return $oe . preg_replace('/'.$oe.'/', $oe.$oe, $cell) . $oe;
                }

                return $cell;
            }, $data[$row]);

            $data[$row] = implode($opts['ft'], $data[$row]);
        }
    
        return implode($opts['lt'], $data);
    }


    static function parseCsv_old($data, $opts) {

        // enclose in double quotes, escape double quotes if necessary
        foreach(array_keys($data) as $val) {      
            foreach(array_keys($data[$val]) as $key) {
                $data[$val][$key] = RequestDataUtil::getCsvStr($data[$val][$key], $opts);
            }
        }
        
        $lines = array();
        foreach($data as $line) {
            $lines[] = implode($opts['ft'], $line);        
        }
        
        $str = implode($opts['lt'], $lines);
        $str = str_replace(array('\n', '\r', '\t'), array("\n", "\r", "\t"), $str);

        return stripslashes($str);
    }
    
    
    static function getCsvStr($str, $opts) {
        
        $chars = array("\n", "\t", "\r");
        $chars[] = $opts['ft'];
        $chars[] = $opts['oe'];
        
        $a = array();
        for($i = 0; $i < strlen($str); $i++){
            $a[] = $str[$i];
            if ($str[$i] == '"') $a[]='"'; 
        }
        
        $str = implode('', $a);
        
        foreach ($chars as $char) {
            if (strpos($str, $char) > -1) {
                $str = $opts['oe'] . $str. $opts['oe'];
                break; 
            }    
        }

        // remove  \n \r \t
        $str = str_replace($chars, '', $str);

        return $str;
    }
    
    
    static function jsEscapeString($str) {
        return addslashes(str_replace(array("\n", "\r"), '', $str));
    }
    
    
    /**
     * Does string contain curly brace(s)?
     */
    static function curly_contains($s) {
        if (strpos($s, '{') === false && strpos($s, '}') === false) {
            return false;
        }
        
        return true;
    }


    static function _curly_replace($matches) {
        return $matches[1] . str_replace(array('{', '}'),
            array('&#123;', '&#125;'), $matches[2]) . $matches[3];
    }


    static function _curly_replace_out_tag($matches) {
        $s = $matches[2];
        if (RequestDataUtil::curly_contains($s)) {
            $res = preg_replace_callback('/(^|>)([^<]*)($|<)/',
                array('RequestDataUtil', '_curly_replace'), $s);
            if ($res == NULL) {
                $res = $s;    // leave it unchanged
            }
        } else {
            $res = $s;
        }
        
        return $matches[1] . $res . $matches[3];
    }


    static function curly_replace_out_script($s) {
        if (RequestDataUtil::curly_contains($s)) {
            // $res = preg_replace_callback('/(^|<\/script>)([\x00-\xFF]*?)($|<script[^>]*>)/i',
            // added style tag, not to strip {} inside style - 2014-02-15 eleontev 
            // $res = preg_replace_callback('/(^|<\/script|style>)([\x00-\xFF]*?)($|<script|style[^>]*>)/i',
            // changed to correct pattern - 2016-06-01 eleontev  
            // $res = preg_replace_callback('/(^|<\/script>|<\/style>)([\x00-\xFF]*?)($|<script[^>]*>|<style[^>]*>)/i',
            $res = preg_replace_callback('/(^|<\/(?:script|style)>)([\x00-\xFF]*?)($|<(?:script|style)[^>]*>)/i',
                array('RequestDataUtil', '_curly_replace_out_tag'), $s);
            if ($res == NULL) {
                $res = $s;    // leave it unchanged
            }
        } else {
            $res = $s;
        }
        
        return $res;
    }
    
    
    static function safeHtmlentitiesGrowl($str) {
        $search = array("&lt;br&gt;", "&lt;br/&gt;", "&lt;br /&gt;", "&lt;b&gt;", "&lt;/b&gt;");
        $replace = array("<br/>", "<br/>", "<br/>", "<b>", "</b>");
        
        $str = htmlentities($str);
        $str = str_replace($search, $replace, $str);
            
        return $str;
    }


    static function _substrVar($value, $num) {
        return (_strlen($value) > $num) ? _substr($value, 0, $num) : $value;
    } 
    
    
    static function substrVars($arr, $key_to_num) {

        if(!is_array($key_to_num)) {
            return RequestDataUtil::_substrVar($arr, $key_to_num);
        }
        
        foreach($key_to_num as $k => $num) {
            if(is_array($num)) {
                $arr[$k] = RequestDataUtil::substrVars($arr[$k], $num);
            
            } elseif(isset($arr[$k])) {
                $arr[$k] = RequestDataUtil::_substrVar($arr[$k], $num);
            }
        }

        return $arr;
    }

}
/*
// htmlspecialchars  ( string $string  [, int $quote_style  [, string $charset  [, bool $double_encode  ]]] )

'&' (ampersand) becomes '&amp;' 
'"' (double quote) becomes '&quot;' when ENT_NOQUOTES is not set. 
''' (single quote) becomes '&#039;' only when ENT_QUOTES is set. 
'<' (less than) becomes '&lt;' 
'>' (greater than) becomes '&gt;'
*/


/*
$data = array('html'=>'<br>adasd " dflksdfk',
              'text'=>'jjhasjk " laksdlk <>');
              
              
echo "<pre>"; print_r(RequestDataUtil::stripVarsHtml($data['html'])); echo "</pre>";
echo "<pre>"; print_r(RequestDataUtil::stripVars($data, array(), true)); echo "</pre>";
*/

?>