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

use Validator;
use KBExport;
use KBExport2_pdf;
use KBValidateLicense;
use AppMsg;

class SettingValidator
{
     
    function validate($values) {
                
        $required = array();
        
        $v = new Validator($values, true);

        $v->required('required_msg', $required);        
        if($v->getErrors()) {
            return $v->getErrors();
        }
        
        // wkhtmltopdf
        if(isset($values['plugin_wkhtmltopdf_path'])) {   
            $key = $values['plugin_wkhtmltopdf_path'];
            if(strtolower($key) != 'off') {
                $ret = $this->validateWKHTMLTOPDF($values['plugin_wkhtmltopdf_path']);
                if($ret !== true) {
                    $msg = AppMsg::getMsgs('error_msg.ini', 'export_setting', 'export_wkhtmltopdf');
                    $body = AppMsg::replaceParse($msg['body'], $ret);
                    $v->setError($body, 'plugin_wkhtmltopdf_path', 'plugin_wkhtmltopdf_path', 'custom');
                }
            }
        }
        
        // htmldoc
        if(isset($values['plugin_htmldoc_path'])) {
            $key = $values['plugin_htmldoc_path'];
            if(strtolower($key) != 'off') {
                $ret = $this->validateHTMLDOC($values['plugin_htmldoc_path']);
                if($ret !== true) {
                    $msg = AppMsg::getMsgs('error_msg.ini', 'export_setting', 'export_htmldoc');
                    $body = AppMsg::replaceParse($msg['body'], $ret);
                    $v->setError($body, 'plugin_htmldoc_path', 'plugin_htmldoc_path', 'custom');
                }
            }
        }
        
        return $v->getErrors();
    }
    
    
    function validateHTMLDOC($tool_path) {
        $export = KBExport::factory('pdf');
        return $export->validate($tool_path, false); //second arg not to check license
    }
	
    
    function validateWKHTMLTOPDF($tool_path) {
        $export = new KBExport2_pdf;
        return $export->validate($tool_path, false); //second arg not to check license
    }
    
}
?>