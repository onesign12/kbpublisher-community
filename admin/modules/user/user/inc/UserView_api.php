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


class UserView_api extends AppView
{
    
    var $tmpl = 'form_api.html';
    var $account_view = false;

    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->escapeMsg(array('api_reset_key_sure_msg'));
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        $tpl->tplAssign('api_note_msg', AppMsg::hintBoxCommon('note_api_account'));
        

        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->setRequestURI($this->controller->getAjaxLink('full'));
        $xajax->registerFunction(array('generateApiKey', $this, 'ajaxGenerateApiKey'));
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormApi'));

        // extra
        $api_rule_id = $manager->extra_rules['api']; 
        $api_data = $obj->getExtraValues($api_rule_id);
        $api_access = $api_data['api_access'];
        $tpl->tplAssign($api_data);
        $tpl->tplAssign('api_rule_id', $api_rule_id);
        $tpl->tplAssign('ch_api_access', $this->getChecked(!empty($api_access)));
        $tpl->tplAssign('ch_api_puser', $this->getChecked($api_access == 2));
        $tpl->tplAssign('private_key_link_display', ($api_data['api_private_key']) ? 'inline' : 'none');

        $vars = $this->setCommonFormVars($obj);

        if($this->account_view) {
            if($manager->is_admin) {
                $tpl->tplSetNeeded('/api_access_checkbox');
            }
        } else {
            
            $vars['cancel_link'] = $this->getActionLink('detail', $obj->get('id'));
            
            $tpl->tplAssign('menu_block', UserView_common::getEntryMenu($obj, $manager, $this));     
            $tpl->tplSetNeeded('/api_access_checkbox');
        }
        
        if(!empty($api_data['api_public_key'])) {
            $tpl->tplSetNeededGlobal('reset_key');
        } else {
            $tpl->tplSetNeededGlobal('generate_key');
        }
        
        
        $http = ($this->setting['api_secure']) ? 'https://' : 'http://';
        $api_url = $http . $this->conf['api_path'];
        $tpl->tplAssign('api_url', $api_url);
        
        
        $tpl->tplAssign($vars);
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign('action_title', $this->msg['api_update_msg']);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    static function ajaxGenerateApiKey() {
        $objResponse = new xajaxResponse();

        $key = UserModel::generateApiKey();
        $objResponse->addScript("$('#api_public_key').val('$key');");
        $objResponse->addScript("$('#api_public_key_text').text('$key');");
        
        $key = UserModel::generateApiKey();
        $objResponse->addScript("$('#api_private_key').val('$key');");
        $objResponse->addScript("$('#api_private_key_text').text('$key');");
        $objResponse->addScript("private_key = '$key'");
        $objResponse->assign('private_key_link', 'style.display', 'inline');

        return $objResponse;
    }
    
    
    function ajaxValidateFormApi($values, $options = array()) {
        $options['func'] = 'getValidateApiKeys';
        return $this->ajaxValidateForm($values, $options);
    }
}
?>