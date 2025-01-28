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


class SettingViewAuthMapPriv_popup extends SettingView_form
{


    function execute(&$obj, &$manager, $extra_options = array()) {
        $this->addMsg('common_msg.ini', 'ldap_setting');

        $tpl = new tplTemplatez($this->template_dir . 'auth_priv_map.html');

        // check
        $au = KBValidateLicense::getAllowedUserRest($manager);
        if($au !== true) {
            $key = ($au <= 0) ? 'license_exceed_users_note' : 'license_limit_users_note';
            $msg = AppMsg::licenseBox($key, array('num_users' => $au));
            $tpl->tplAssign('license_limit_user_msg', $msg);
            $tpl->tplSetNeeded('/license_limit_user');
        }

        $manager2 = new UserModel;
        $privileges = $manager2->getPrivSelectRange();
        $this->privileges = $privileges;

        $rules = $manager->getSettings(160, 'remote_auth_map_group_to_priv');
        $rules = explode("\n", $rules);
        
        try { // fetching groups
            $ldap = new AuthLdap($obj->get());
            $ldap->validateCompability();
            $ldap->connect();
            
            $password = @$obj->get('ldap_connect_password');
            //$password = EncryptedPassword::encode($password);
            $ldap->bind(@$obj->get('ldap_connect_dn'), $password);
            
            $ldap_groups = $ldap->getGroupList();
            
            $tpl->tplSetNeededGlobal('mapping_block');
            
            $button['+'] = 'javascript:$(\'#new_rule\').show();void(0);';
            $tpl->tplAssign('buttons', $this->getButtons($button));

            $select = new FormSelect();
            $select->setSelectWidth(250);

            $select->setSelectName('ldap_group');

            /*$range = array();
            foreach ($ldap_groups as $dn => $name) {
                $range[$dn] = sprintf('%s [%s]', $name, $dn);
            }
            $select->setRange($range);*/
            $select->setRange($ldap_groups);
            $tpl->tplAssign('ldap_group_select', $select->select());

            $select->setSelectName('kbp_priv');
            $select->setRange($privileges);
            $tpl->tplAssign('kbp_priv_select', $select->select());

            $connected = true;

        } catch (Exception $e) {
            $msg = array();
            $msg['title'] = $this->msg['error_msg'];
            $msg['body'] = $e->getMessage();
            $tpl->tplAssign('error_message', BoxMsg::factory('error', $msg));
            
            $tpl->tplAssign('table_class', 'disabled');

            $connected = false;
        }

        // current rules
        for ($i = 0; $i < count($rules); $i ++) {
            $rule = explode('|', trim($rules[$i]));

            if (count($rule) != 2) { // this rule is broken
                continue;
            }
            
            $v = $this->msg;
            
            $v['ldap_group_dn'] = $rule[0];
            $v['kbp_priv_id'] = $rule[1];
            
            $img = ($connected) ? 'check.svg' : false;
            $default_title = ($connected) ? $this->msg['good_msg'] : $this->msg['unknown_msg'];
            $error_msg = '';

            // ldap group
            if (empty($ldap_groups[$v['ldap_group_dn']])) {
                if ($connected) {
                    $img = 'warning.svg';
                    $error_msg .= sprintf('%s <b>%s</b><br/>', $this->msg['missing_ldap_group_msg'], $v['ldap_group_dn']);
                }

                $v['ldap_group'] = $v['ldap_group_dn'];
                
            } else {
                $v['ldap_group'] = sprintf('<b>%s</b> [%s]', $ldap_groups[$v['ldap_group_dn']], $v['ldap_group_dn']);
            }

            // kbp privilege
            if (empty($privileges[$v['kbp_priv_id']])) {
                $img = 'warning.svg';
                $error_msg .= sprintf('%s <b>%s</b><br/>', $this->msg['missing_kbp_priv_msg'], $v['kbp_priv_id']);

                $v['kbp_priv'] = '--';
            } else {
                $v['kbp_priv'] = $privileges[$v['kbp_priv_id']];
            }

            $v['line'] = $i;
            
            if (!empty($img)) {
                $tpl->tplSetNeeded('rule/status_icon');
                $v['img'] = $img;
            }
            
            $v['title'] = ($error_msg) ? $error_msg : $default_title;

            $tpl->tplParse($v, 'rule');
        }

        // custom
        $custom_mapping = $manager->getSettings(160, 'remote_auth_map_priv_id');
        $tpl->tplAssign('custom_mapping', $custom_mapping);

        $custom_num = (strlen($custom_mapping) > 0) ? count(explode("\n", $custom_mapping)) : 0;
        $tpl->tplAssign('custom_num', ($custom_num) ? sprintf('(%d)', $custom_num) : '');

        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        $more_ajax = array('popup' => 'remote_auth_map_group_to_priv');
        $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more_ajax));

        $xajax->registerFunction(array('deleteRule', $this, 'ajaxDeleteRule'));
        $xajax->registerFunction(array('addRule', $this, 'ajaxAddRule'));
        $xajax->registerFunction(array('updateItem', $this, 'ajaxUpdateItem'));
        $xajax->registerFunction(array('saveCustom', $this, 'ajaxSaveCustom'));

        $tpl->tplParse($this->msg);

        return $tpl->tplPrint(1);
    }


    function ajaxDeleteRule($line) {
        $objResponse = new xajaxResponse();

        $setting_key = 'remote_auth_map_group_to_priv';

        $rules = $this->manager->getSettings(160, $setting_key);
        $rules = explode("\n", $rules);

        unset($rules[$line]);

        $rules = implode("\n", $rules);

        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($rules)));

        $objResponse->call('hideDeletedRule', $line);

        return $objResponse;
    }


    function ajaxAddRule($data) {
        $objResponse = new xajaxResponse();

        $manager2 = new UserModel;
        $privileges = $manager2->getPrivSelectRange();

        $setting_key = 'remote_auth_map_group_to_priv';

        $rules = $this->manager->getSettings(160, $setting_key);
        $lines = explode("\n", $rules);

        foreach ($lines as $line) {
            $r = explode('|', $line);

            if ($r[0] == $data['ldap_group_dn']) {
                $search = array('{ldap_group}', '{privilege_name}');
                $replace = array($data['ldap_group_dn'], $privileges[$data['kbp_priv']]);
                $message = str_replace($search, $replace, $this->msg['ldap_group_assigned_to_priv_msg']);

                $objResponse->addAlert($message);
                return $objResponse;
            }
        }

        $rule = sprintf("%s|%s", $data['ldap_group_dn'], $data['kbp_priv']);

        if (strlen($rules) != 0) {
            $rules .= "\n";
        }
        $rules .= $rule;

        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($rules)));

        // get html to insert
        $tpl = new tplTemplatez($this->template_dir . 'auth_priv_map.html');

        $v = array(
            'line' => count($lines),
            'ldap_group' => sprintf('<b>%s</b> [%s]', $data['ldap_group_name'], $data['ldap_group_dn']),
            'ldap_group_dn' => $data['ldap_group_dn'],
            'kbp_priv' => $privileges[$data['kbp_priv']],
            'kbp_priv_id' => $data['kbp_priv'],
            'delete_msg' => $this->msg['delete_msg'],
            'img' => 'check.svg',
            'title' => $this->msg['good_msg']
        );
                   
        $tpl->tplSetNeeded('rule/status_icon');
        $tpl->tplParse($v, 'rule');
        $html = $tpl->parsed['rule'];
        
        $line_num = count($lines);
        
        $objResponse->call('showAddedRule', $html, $line_num);

        return $objResponse;
    }


    function ajaxUpdateItem($line_number, $type, $value, $name) {
        $objResponse = new xajaxResponse();
        
        $setting_key = 'remote_auth_map_group_to_priv';

        $rules = $this->manager->getSettings(160, $setting_key);
        $rules = explode("\n", $rules);
        
        if ($type == 'ldap_group') {
            foreach ($rules as $k => $line) {
                $r = explode('|', $line);
    
                if ($r[0] == $value && $line_number != $k) {
                    $search = array('{ldap_group}', '{privilege_name}');
                    $replace = array($value, $this->privileges[$r[1]]);
                    $message = str_replace($search, $replace, $this->msg['ldap_group_assigned_to_priv_msg']);
    
                    $objResponse->addAlert($message);
                    return $objResponse;
                }
            }            
        }
        
        $parts = explode('|', trim($rules[$line_number]));
        
        $value = str_replace('|', '&#124;', $value);
        $field_num = ($type == 'ldap_group') ? 0 : 1;
        $parts[$field_num] = $value;
        
        $rules[$line_number] = implode('|', $parts);
        $rules = implode("\n", $rules);
        
        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($rules)));
        
        $script = '$("#rule_%s td[data-type=\'%s\']").attr("data-value", "%s");';
        $script = sprintf($script, $line_number, $type, $value);
        $objResponse->script($script);
        
        $script = '$("#rule_%s td[data-type=\'%s\']").html("%s");';
        
        if ($type == 'ldap_group') {
            $name = sprintf('<b>%s</b> [%s]', $name, $value);
        }
        
        $script = sprintf($script, $line_number, $type, $name);
        $objResponse->script($script);
        
        $objResponse->call('makeEditable', '#rule_' . $line_number);
        
        return $objResponse;
    }


    function ajaxSaveCustom($data) {
        $objResponse = new xajaxResponse();

        $rules = explode("\n", $data);
        if (strlen($data) > 0) { // validating
            foreach ($rules as $key => $rule) {
                $r = explode('|', trim($rule));
                $line = $key + 1;

                if (count($r) != 3) {
                    $message = str_replace('{line_num}', $line, $this->msg['error_wrong_format_msg']);
                    $objResponse->addAlert($message);
                    return $objResponse;
                }

                if (!ctype_digit($r[2])) {
                    $message = str_replace('{line_num}', $line, $this->msg['error_wrong_priv_id_msg']);
                    $objResponse->addAlert($message);
                    return $objResponse;
                }
            }
        }

        $setting_key = 'remote_auth_map_priv_id';
        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($data)));

        $custom_num = (strlen($data) > 0) ? count($rules) : 0;
        $objResponse->assign('custom_counter', 'innerHTML', ($custom_num) ? sprintf('(%d)', $custom_num) : '');

        $objResponse->call('toggleCustom');

        return $objResponse;
    }
}
?>