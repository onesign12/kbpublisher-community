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





class SettingViewPluginExport_popup extends SettingView_form
{
    
    
    function execute(&$obj, &$manager, $extra_options = array()) {
        $this->addMsg('common_msg.ini', 'export_setting');
        $this->addMsg('common_msg.ini', 'email_setting');
        
        $tpl = new tplTemplatez($this->template_dir . 'form_plugin_export.html');
        
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $key = $_GET['popup'];
        $tpl->tplAssign('key', $key);
        
        $type = substr($key, 14);
        $title = $this->msg[$type . '_template_msg'];
        $tpl->tplAssign('title', $title);
        
        $tags = array(
            'top_category_title',
            'top_category_description',
            'category_title',
            'category_description',
            'export_title',
            'article_title');
        
        if (in_array($type, array('header', 'footer'))) {
            $tags = array_merge($tags, KBExport2_pdf::$wkpdftohtml_vars);
        }
 
        $tags = '[' . implode('], [', array_unique($tags)) . ']';
        $msgs = array('title'=>$this->msg['template_tags'], 'body'=>$tags);
        $tpl->tplAssign('hint_msg', BoxMsg::factory('hint', $msgs));
        
        
        $tpl->tplAssign('ckeditor', $this->getEditor($obj->get($key . '_tmpl'), 'export', 'body'));
        
        if(!empty($_GET['saved']) || !empty($_GET['disabled'])) {
            $tpl->tplSetNeeded('/close_window');
            $tpl->tplSetNeeded('/set_status');
            
            $status = (!empty($_GET['saved'])) ? 'true' : 'false';            
            $tpl->tplAssign('status', $status);
            
        }
        
        $popup_link = $this->controller->getCurrentLink();
        $popup_link = $this->controller->_replaceArgSeparator($popup_link);
        $tpl->tplAssign('popup_link', $popup_link);
        
        $enabled = (bool) $obj->get($key);
        if ($enabled) {
            $button_value = 'submit';
            $button2_value = 'submit_disable';
            $button2_title = $this->msg['save_disable_msg'];
            
        } else {
            $button_value = 'submit_disable';
            $button2_value = 'submit';
            $button2_title = $this->msg['save_enable_msg'];
        }
        $tpl->tplAssign('button_value', $button_value);
        $tpl->tplAssign('button2_value', $button2_value);
        $tpl->tplAssign('button2_title', $button2_title);
        
        if (in_array($type, array('header', 'footer'))) {
            $tpl->tplSetNeeded('/margin_setting');
            
            $margin_setting = ($type == 'header') ? 'top' : 'bottom';
            $margin_setting = 'plugin_wkhtmltopdf_margin_' . $margin_setting;
            
            $margin_msg = AppMsg::getMsgs('setting_msg.ini', 'export_setting', $margin_setting);
            
            $tpl->tplAssign('margin_title', $margin_msg['title']);
            $tpl->tplAssign('margin_value', $obj->get($margin_setting));
            
            $tpl->tplAssign('margin_setting', $margin_setting);
        }
        
        $vars = $this->setCommonFormVars($obj);
        
        $tpl->tplAssign($vars);
        $tpl->tplParse($this->msg);
        
        return $tpl->tplPrint(1);
    }
}
?>