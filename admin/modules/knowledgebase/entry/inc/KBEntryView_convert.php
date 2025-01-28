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

class KBEntryView_convert extends AppView 
{
    
    var $template = 'form_convert.html';
    

    function execute(&$obj, &$manager) {
        
        $this->template_dir = APP_MODULE_DIR . 'knowledgebase/entry/template/';
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        if (!extension_loaded('curl')) {
            $msg = 'cURL - extension is not loaded';
            $msg_vars = array('body' => $msg);
            return BoxMsg::factory('error', $msg_vars);
        }
        
        
        $ws = new FileToHtmlWebService;
        //$ws->ssl = true;
            
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        $ws->api_url = $conf['web_service_url'];
        
        
        // extensions
        $ext_list = $ws->getExtensionsList();

        $allowed_extensions = sprintf("'.%s'",  implode(',.', $ext_list));
        $tpl->tplAssign('allowed_extensions', $allowed_extensions);
        
        
        // hints
        $msgs['title'] = '';
        $msgs['body'] = $this->msg['convertion_hing_msg'];
        $tpl->tplAssign('conversion_hint', BoxMsg::factory('hint', $msgs));

        $msgs['body'] = sprintf('%s: %s', $this->msg['convertion_allowed_types_msg'], implode(', ', $ext_list));
        $tpl->tplAssign('extensions_hint', BoxMsg::factory('hint', $msgs));
                
        
        $form_block_display = 'block';
        
        if ($obj->get('body')) {
            $tpl->tplSetNeeded('/body');
            $form_block_display = 'none';
        }
        
        $tpl->tplAssign('form_block_display', $form_block_display);
        
        
        $link = $this->controller->getRefLink('this', 'this', false, 'convert');        
        $tpl->tplAssign('file_upload_url', $link);
        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>