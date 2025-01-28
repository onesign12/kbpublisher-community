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


class SettingViewHeaderLogo_popup extends SettingView_form
{
    
    
    function execute(&$obj, &$manager, $extra_options = array()) {
        
        $this->addMsg('common_msg.ini', 'public_setting');
        $this->addMsg('common_msg.ini', 'file');
        
        $tpl = new tplTemplatez($this->template_dir . 'form_header_logo.html');
        
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $key = $_GET['popup'];
        $tpl->tplAssign('key', $key);
        
        if ($obj->get('header_logo')) {
            $tpl->tplSetNeeded('/current_logo');
            $tpl->tplAssign('image_data', $obj->get('header_logo'));
        }
        
        if(!empty($_GET['saved']) && !$obj->errors) {
            $tpl->tplSetNeeded('/set_status');
            
            $header_logo_status = 'true';
            
            if (!$obj->get('header_logo')) {
                $tpl->tplSetNeeded('/close_window');
                $header_logo_status = 'false';
            }
            
            $tpl->tplAssign('header_logo_status', $header_logo_status);
        }
        
        $vars = $this->setCommonFormVars($obj);
        
        $tpl->tplAssign($vars);
        $tpl->tplParse($this->msg);
        
        return $tpl->tplPrint(1);
    }
}
?>