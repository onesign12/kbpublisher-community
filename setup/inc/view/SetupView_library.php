<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class SetupView_library extends SetupView
{

    var $refresh_button = true;


    function &execute($manager) {
        $data = $this->getContent($manager);
        return $data;
    }
    
    
    function getContent($manager) {
        
        $tpl = new tplTemplatez($this->template_dir . 'library.html');
    
        $config = new SetConfiguration();
    
        // required
        $php_recomended = '8.0 - 8.3';
        $php_required = ['8.0', '8.3.999'];
        $s = array('PHP', $php_recomended, PHP_VERSION);
        $config->setItem($s, CheckConfiguration::checkPHPVersion($php_required));
                
        
        $mysql_ext_avail = array();
        $mysql_ext = array('mysqli', 'mysql');
        foreach($mysql_ext as $v) {
            if(extension_loaded($v)) {
                $mysql_ext_avail[] = $v;
            }
        }
        
        $s = array('MySQL extension', implode(',', $mysql_ext), implode(',', $mysql_ext_avail));
        $config->setItem($s, !empty($mysql_ext_avail));
        
        $s = array('Intl extension', 1, extension_loaded('intl'));
        $config->setItem($s, ($s[2]));
        
        $s = array('DOM extension', 1, extension_loaded('dom'));
        $config->setItem($s, ($s[2]));
        
        $s = array('Session Support', 'ON', (isset($_SESSION['setup_'])) ? 'ON' : 'OFF');
        $config->setItem($s, (isset($_SESSION['setup_'])));        
        
        
        // recomended
        $gd = CheckConfiguration::getGD();
        if($gd === false) {
            $s = array('GD Version', '2.0', 'OFF');
            $config->setItem($s, false, 'optional');
            
        } else {
            $version = substr(preg_replace("#[^\d]#", '', $gd['GD Version']), 0, 3);
            $version = sprintf("%-03s\n",   $version); // left-justification with spaces
            
            $s = array('GD', '2.0', preg_replace("#[^\d.+]#", '', $gd['GD Version']));
            $config->setItem($s, (200 <= $version), 'optional');
            
            // $s = array('GD FreeType Support', 1, $gd['FreeType Support']);
            // $config->setItem($s, ($s[1] == $s[2]), 'optional');
            //
            // $s = array('GD FreeType Linkage', 'with freetype', $gd['FreeType Linkage']);
            // $config->setItem($s, ($s[1] == $s[2]), 'optional');
            //
            // $jpg = (isset($gd['JPEG Support'])) ? $gd['JPEG Support'] : $gd['JPG Support'];
            // $s = array('GD JPEG Support', 1, $jpg);
            // $config->setItem($s,($s[1] == $s[2]), 'optional');       
        }

        
        // xajax
        if(strtoupper($this->encoding) != 'ISO-8859-1') {
        
            $func = array();
            if(function_exists('iconv')) {
                $func[] = 'iconv ' . ICONV_VERSION;
            }
            
            if(function_exists('mb_convert_encoding')) {
                $func[] = 'mb_convert_encoding';
            }
        
            $s = array('iconv <br /> mb_convert_encoding', 1, implode('<br />', $func));
            $config->setItem($s, ($func), 'optional');            
        }
        

        // This extension enables you to transparently read or write ZIP compressed 
        // archives and the files inside them.
        // $d = "ZIP extension allows you to extract text from .docx, .xlsx and .odt 
        // files and make them searchable.";
        $s = array('Zip', 1, extension_loaded('zip'));
        $config->setItem($s, ($s[2]), 'optional');
        
        // curl 
        $s = array('Curl', 1, extension_loaded('curl'));
        $config->setItem($s, ($s[2]), 'optional');
        
        foreach($config->getItems() as $k => $row) {
            $tpl->tplParse($row, 'row');
        }
        
        $this->passed = $config->isPassed();
        
        
        $msg_key = false;
        if($config->getPassedKey() == 'failed')    { 
            $msg_key = 'pass_failed_library'; 
        } elseif($config->getPassedKey() == 'limit') { 
            $msg_key = 'pass_limit_library';  
        }
        
        if($msg_key) {
            $msg = array('title' => '', 'body' => $this->getPhraseMsg($msg_key));
            $msg = BoxMsg::factory('error', $msg);
            $tpl->tplAssign('user_msg', $msg);
        }        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>