<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2010 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+

namespace ExportSetting;

use SettingParserCommon;
use BaseModel;
use tplTemplatez;


class SettingParser extends SettingParserCommon
{
    
    function parseSubmit($template_dir, $msg, $options = array()) {
        $tpl = new tplTemplatez($template_dir . 'form_submit_plugin.html');
        
        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }
    
    function parseIn($key, $value, &$values = array()) {

        if($key == 'plugin_htmldoc_path') {
            if(!empty($value) && strtolower($value) != 'off') {
                $value = $this->parseDirectoryValue($value);
            }
            
        } elseif($key == 'plugin_wkhtmltopdf_path') {
            if(!empty($value) && strtolower($value) != 'off') {
                $value = $this->parseDirectoryValue($value);
            }
            
        } elseif(in_array($key, array('plugin_wkhtmltopdf_dpi', 
									  'plugin_wkhtmltopdf_margin_top', 
									  'plugin_wkhtmltopdf_margin_bottom'))) {
            if(strlen($value)) {
                $value = intval($value);
            }
        }

        return $value;
    }
    
    
    function parseOut($key, $value) {
        
        // hide key in cloud
        if(BaseModel::isCloud()) {
            $keys = BaseModel::getCloudPluginKeys();
            if(in_array($key, $keys)) {
                if(!empty($value) && strtolower($value) != 'off') {
                    $value = 'ON';
                }
            }
        }
        
        return $value;
    }
    
    
    function skipSettingDisplay($key, $value = false) {
        $ret = false;

        $keys = array(
            'plugin_export_cover_tmpl',
            'plugin_export_header_tmpl',
            'plugin_export_footer_tmpl',
            'plugin_wkhtmltopdf_margin_top',
            'plugin_wkhtmltopdf_margin_bottom'
        );

        if(in_array($key, $keys)) {
            $ret = true;
        }

        return $ret;
    }
    
    
    function parseInputOptions($key, $value) {
        $ret = false;
        
        // readonly in cloud
        if(BaseModel::isCloud()) {
            $keys = BaseModel::getCloudPluginKeys();
            if(in_array($key, $keys)) {
                $ret = 'readonly';
            }
        }
        
        return $ret;
    }
        
}
?>