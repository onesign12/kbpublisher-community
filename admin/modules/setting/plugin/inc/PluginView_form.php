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


class PluginView_form extends AppView
{
    
    var $template = 'form.html';
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('setting_msg.ini', 'plugin_setting');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $vars = [
            'add_plugins_msg' => $this->msg['add_plugins_msg'],
            'link' => 'https://www.kbpublisher.com/plugins/'
        ];
        $tpl->tplAssign('hint_msg', AppMsg::hintBoxCommon('note_add_plugin_flow', $vars));
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        $this->msg['action_title'] = $this->msg['add_plugins_msg'];
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        // $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>