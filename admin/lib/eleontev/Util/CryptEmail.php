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

class CryptEmail
{

    function getMailtolJs($use_script_tag = false) {
        $html = <<<EOF
        function namylo (domen, server, login, sub) {
            eml = "mailto:" + login +  "@" + server + "." + domen;
            if(sub != "") eml += "?subject=" + sub;
            window.location.href = eml;
        }\n
EOF;
    
        $html = ($use_script_tag) ? "\n<script>\n".$html."\n</script>\n" : $html;
        return $html;
    }

    
    function getMailto($email, $subj = false) {
        $chunks = explode('@', $email);
        
        $pos = strrpos($chunks[1], '.');
        $server = substr($chunks[1], ($pos+1));
        $domen = substr($chunks[1], 0, $pos);
        
        $str = "javascript:namylo('%s', '%s','%s', '%s');";
        return sprintf($str, $server, $domen, $chunks[0], $subj);
    }
    
    
    function getMailtoLink($email, $text, $subj = false) {
        $str = '<a href="%s">%s</a>';
        return sprintf($str, CryptEmail::getMailto($email, $subj), $text);
    } 
    
}


//echo CryptEmail::getMailtoLink('info@das.hag.com', 'text');
?>