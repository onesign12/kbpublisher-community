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



class SettingViewSamlDebug_popup extends AppView
{
    
    var $tmpl = 'saml_debug.html';
    
    
    function getLoginPage($attributes, $name_id, $user, $slo_enabled) {
        
        $this->addMsg('common_msg.ini', 'saml_setting');
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $tpl->tplAssign('hint_msg', AppMsg::hintBox('saml_debug_login_success', 'saml_setting'));
        $tpl->tplSetNeeded('/info');
        
        foreach ($attributes as $name => $value) {
            $value = (count($value) == 1) ? $value[0] : print_r($value, 1);
            
            $v = array(
                'attr_name' => $name,
                'attr_value' => $value,
            );
            
            $tpl->tplParse($v, 'saml_user_attribute');
        }
        
        $tpl->tplAssign('name_id', $name_id);
    
        if (!empty($user['priv_id']) && $user['priv_id'] != 'off') { // showing names for privileges
            $manager2 = new UserModel;
            $priv_range = $manager2->getPrivSelectRange();
            
            $privilege = (empty($priv_range[$user['priv_id']])) ? '--' : $priv_range[$user['priv_id']];
            $user['priv_id'] .= sprintf(' (%s)', $privilege);
        }
        
        if (!empty($user['role_id']) && $user['role_id'] != 'off') { // showing names for roles
            $manager2 = new UserModel;
            $roles_range = $manager2->getRoleSelectRangeFolow();
            
            $roles_ids = explode(',', $user['role_id']);
            $roles = array();
            foreach ($roles_ids as $role_id) {
                $roles[] = (empty($roles_range[$role_id])) ? '--' : $roles_range[$role_id];
            }
            
            $user['role_id'] .= sprintf(' (%s)', implode(', ', $roles));
        }
            
        foreach ($user as $k => $v) {
            $v = array(
                'attr_name' => $k,
                'attr_value' => $v,
            );
            
            $tpl->tplParse($v, 'kbp_user_row');
        }
        
        // logout
        if ($slo_enabled) {
            $tpl->tplSetNeeded('/slo_button');
            
            $this->controller->self_page = 'index.php';
            $more = array('popup' => 'saml_debug_slo');
            $slo_url = $this->controller->getFullLink('setting', 'auth_setting', 'saml_setting', false, $more);
            $tpl->tplAssign('slo_url', $slo_url);
        }
        
        $tpl->tplAssign('debug_info', '');
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }
    
    
    function getLogoutPage() {
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $tpl->tplAssign('hint_msg', AppMsg::hintBox('saml_debug_logout_success', 'saml_setting'));
        
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }
    
    
    function getErrorPage($error_msg, $debug_arr_msg = array()) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $msg_vars = array('body' => $error_msg);
        $tpl->tplAssign('hint_msg', BoxMsg::factory('error', $msg_vars));
        
        $debug = '';
        if($debug_arr_msg) {
            $debug = DebugUtil::getDebugBlock($debug_arr_msg);
        }
        
        $tpl->tplAssign('debug_info', $debug);
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }
    
}
?>