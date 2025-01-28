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


class SettingViewAuthMapRole_popup extends SettingView_form
{


    function execute(&$obj, &$manager, $extra_options = array()) {
        $this->addMsg('common_msg.ini', 'ldap_setting');

        $tpl = new tplTemplatez($this->template_dir . 'auth_role_map.html');

        $manager2 = new UserModel;
        $roles = $manager2->getRoleSelectRange();
        $roles_range = $manager2->getRoleSelectRangeFolow();

        $rules = $manager->getSettings(160, 'remote_auth_map_group_to_role');
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

            $select->setSelectName('kbp_role');
            $select->setMultiple(7);
            $select->setRange($roles_range);
            $tpl->tplAssign('kbp_role_select', $select->select());

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
            $v['kbp_role_id'] = $rule[1];
            $kbp_role_ids = explode(',', $rule[1]);

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

            // kbp roles
            $v['kbp_role'] = '';
            foreach ($kbp_role_ids as $role_id) {
                if (empty($roles[$role_id])) {
                    $img = 'warning.svg';
                    $error_msg .= sprintf('%s <b>%s</b><br/>', $this->msg['missing_kbp_role_msg'] ,$role_id);

                    $v['kbp_role'] = '--';
                } else {
                    $v['kbp_role'] .= $roles_range[$role_id] . '<br />';
                }
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
        $custom_mapping = $manager->getSettings(160, 'remote_auth_map_role_id');
        $tpl->tplAssign('custom_mapping', $custom_mapping);

        $custom_num = (strlen($custom_mapping) > 0) ? count(explode("\n", $custom_mapping)) : 0;
        $tpl->tplAssign('custom_num', ($custom_num) ? sprintf('(%d)', $custom_num) : '');

        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        $more_ajax = array('popup' => 'remote_auth_map_group_to_role');
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

        $setting_key = 'remote_auth_map_group_to_role';

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
        $roles_range = $manager2->getRoleSelectRangeFolow();

        $setting_key = 'remote_auth_map_group_to_role';

        $rules = $this->manager->getSettings(160, $setting_key);
        $lines = explode("\n", $rules);

        foreach ($lines as $line) {
            $r = explode('|', $line);

            if ($r[0] == $data['ldap_group_dn']) {
                $message = str_replace('{ldap_group}', $data['ldap_group_dn'], $this->msg['ldap_group_assigned_to_role_msg']);

                $objResponse->addAlert($message);
                return $objResponse;
            }
        }

        $role_ids = implode(',', $data['kbp_role']);
        $rule = sprintf("%s|%s", $data['ldap_group_dn'], $role_ids);

        if (strlen($rules) != 0) {
            $rules .= "\n";
        }
        $rules .= $rule;

        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($rules)));

        foreach ($data['kbp_role'] as $role_id) {
            $roles_names[] = $roles_range[$role_id];
        }

        // get html to insert
        $tpl = new tplTemplatez($this->template_dir . 'auth_role_map.html');

        $v = array(
            'line' => count($lines),
            'ldap_group' => sprintf('<b>%s</b> [%s]', $data['ldap_group_name'], $data['ldap_group_dn']),
            'ldap_group_dn' => $data['ldap_group_dn'],
            'kbp_role' => implode('<br />', $roles_names),
            'kbp_role_id' => $role_ids,
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
        
        $setting_key = 'remote_auth_map_group_to_role';

        $rules = $this->manager->getSettings(160, $setting_key);
        $rules = explode("\n", $rules);
        
        
        if ($type == 'ldap_group') {
            foreach ($rules as $k => $line) {
                $r = explode('|', $line);
    
                if ($r[0] == $value && $line_number != $k) {
                    $message = str_replace('{ldap_group}', $value, $this->msg['ldap_group_assigned_to_role_msg']);

                    $objResponse->addAlert($message);
                    return $objResponse;
                }
            }            
        }
        
        
        $parts = explode('|', trim($rules[$line_number]));
        
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        
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
            
        } else {
            $name = implode('<br />', $name);
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

                if (!preg_match('/^[0-9,]+$/', $r[2])) {
                    $message = str_replace('{line_num}', $line, $this->msg['error_wrong_role_id_msg']);
                    $objResponse->addAlert($message);
                    return $objResponse;
                }
            }
        }

        $setting_key = 'remote_auth_map_role_id';
        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($data)));

        $custom_num = (strlen($data) > 0) ? count($rules) : 0;
        $objResponse->assign('custom_counter', 'innerHTML', ($custom_num) ? sprintf('(%d)', $custom_num) : '');

        $objResponse->call('toggleCustom');

        return $objResponse;
    }
}
?>