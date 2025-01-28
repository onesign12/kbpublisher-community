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



class SettingViewSamlMap_popup extends SettingView_form
{


    function execute(&$obj, &$manager, $extra_options = array()) {
        $this->addMsg('common_msg.ini', 'ldap_setting');
        $this->addMsg('common_msg.ini', 'saml_setting');

        $tpl = new tplTemplatez($this->template_dir . 'saml_map.html');

        $popup = $this->controller->getMoreParam('popup');
        $this->type = substr($popup, -4);

        $tpl->tplAssign('type', $this->type);

        $tpl->tplAssign('popup_title', $this->msg[sprintf('group_to_%s_mapping_msg', $this->type)]);

        $manager2 = new UserModel;

        $select = new FormSelect();
        $select->setSelectWidth(250);
        $select->setSelectName('kbp_attr');

        if ($this->type == 'priv') {
            // check
            $au = KBValidateLicense::getAllowedUserRest($manager);
            if($au !== true) {
                $key = ($au <= 0) ? 'license_exceed_users_note' : 'license_limit_users_note';
                $msg = AppMsg::licenseBox($key, array('num_users' => $au));
                $tpl->tplAssign('license_limit_user_msg', $msg);
                $tpl->tplSetNeeded('/license_limit_user');
            }

            $privileges = $manager2->getPrivSelectRange();
            $select->setRange($privileges);
            
            $tpl->tplSetNeededGlobal('priv');
            
            $this->privileges = $privileges;

            $kbp_attr_title = $this->msg['kbp_priv_msg'];

        } else {
            $roles = $manager2->getRoleSelectRange();
            $roles_range = $manager2->getRoleSelectRangeFolow();

            $select->setMultiple(7);
            $select->setRange($roles_range);

            $tpl->tplSetNeededGlobal('roles');
            
            $this->roles = $roles_range;
            
            $kbp_attr_title = $this->msg['kbp_roles_msg'];
        }

        $tpl->tplAssign('kbp_attr_title', $kbp_attr_title);
        $tpl->tplAssign('kbp_attr_select', $select->select());
        
        
        $rules = $manager->getSettings(162, $popup);
        $rules = ($rules) ? explode("\n", $rules) : array();
        
        $button['+'] = 'javascript:$(\'#new_rule\').show();void(0);';
        $button['...'] = array(array(
            'msg' => $this->msg['reorder_msg'],
            'link' => 'javascript:xajax_getSortableList();void(0);',
            //'disabled' => ($rules) ? true : false
        ));
        
        
        $tpl->tplAssign('buttons', $this->getButtons($button));
        
        
        $initial_value = '';

        // current rules
        for ($i = 0; $i < count($rules); $i ++) {
            $rule = explode('|', trim($rules[$i]));

            if (count($rule) != 3) { // this rule is broken
                continue;
            }

            $initial_value = $rule[0];

            $v = $this->msg;

            // saml group
            $v['saml_attr_name'] = $rule[0];
            $v['saml_attr_value'] = $rule[1];
            $v['kbp_attr_value'] = $rule[2];

            if ($this->type == 'priv') {// kbp privilege
                $kbp_priv_id = $rule[2];
                if (empty($privileges[$kbp_priv_id])) {
                    $error_msg .= sprintf('%s <b>%s</b><br/>', $this->msg['missing_kbp_priv_msg'], $kbp_priv_id);

                    $v['kbp_attr_data'] = '--';
                } else {
                    $v['kbp_attr_data'] = $privileges[$kbp_priv_id];
                }

            } else { // kbp roles
                $kbp_role_ids = explode(',', $rule[2]);

                $v['kbp_attr_data'] = '';
                foreach ($kbp_role_ids as $role_id) {
                    if (empty($roles[$role_id])) {
                        $error_msg .= sprintf('%s <b>%s</b><br/>', $this->msg['missing_kbp_role_msg'] ,$role_id);

                        $v['kbp_attr_data'] = '--';
                    } else {
                        $v['kbp_attr_data'] .= $roles_range[$role_id] . '<br />';
                    }
                }
            }

            $v['line'] = $i;

            $tpl->tplParse($v, 'rule');
        }

        $tpl->tplAssign('initial_value', $initial_value);

        $msg = AppMsg::getErrorMsgs();
        $tpl->tplAssign('required_msg', $msg['required_msg']);

        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        $more_ajax = array('popup' => $popup);
        $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more_ajax));

        $xajax->registerFunction(array('deleteRule', $this, 'ajaxDeleteRule'));
        $xajax->registerFunction(array('addRule', $this, 'ajaxAddRule'));
        $xajax->registerFunction(array('updateItem', $this, 'ajaxUpdateItem'));
        $xajax->registerFunction(array('getSortableList', $this, 'ajaxGetSortableList'));

        $tpl->tplParse($this->msg);

        return $tpl->tplPrint(1);
    }


    function ajaxDeleteRule($line) {
        $objResponse = new xajaxResponse();

        $setting_key = $this->controller->getMoreParam('popup');

        $rules = $this->manager->getSettings(162, $setting_key);
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

        if ($this->type == 'priv') {
            $privileges = $manager2->getPrivSelectRange();

        } else {
            $roles_range = $manager2->getRoleSelectRangeFolow();
        }

        $setting_key = $this->controller->getMoreParam('popup');

        $rules = $this->manager->getSettings(162, $setting_key);
        $lines = array_filter(explode("\n", $rules));

        $merged_line = false;

        foreach ($lines as $num => $line) {
            $r = explode('|', $line);

            if ($r[0] == $data['saml_attr_name'] && $r[1] == $data['saml_attr_value']) {

                if ($this->type == 'role') { // merging
                    $priv_ids = explode(',', $r[2]);
                    $data['kbp_attr_value'] = array_merge($priv_ids, $data['kbp_attr_value']);
                    $data['kbp_attr_value'] = array_unique($data['kbp_attr_value']);

                    $priv_ids_str = implode(',', $data['kbp_attr_value']);
                    $new_line = sprintf('%s|%s|%s', $r[0], $r[1], $priv_ids_str);
                    $rules = str_replace($line, $new_line, $rules);

                    $merged_line = $num;

                    $objResponse->call('hideDeletedRule', $num);

                } else { // error for privileges
                    $search = array('{saml_group}');
                    $replace = array($data['saml_attr_value']);

                    if ($this->type == 'priv') {
                        $search[] = '{privilege_name}';
                        $replace[] = $privileges[$data['kbp_attr_value']];
                    }

                    $message = str_replace($search, $replace, $this->msg[sprintf('saml_group_assigned_to_%s_msg', $this->type)]);

                    $objResponse->addAlert($message);
                    return $objResponse;
                }
            }
        }

        if ($this->type == 'role') {
            foreach ($data['kbp_attr_value'] as $role_id) {
                $roles_names[] = $roles_range[$role_id];
            }

            $data['kbp_attr_value'] = implode(',', $data['kbp_attr_value']);
        }
        $rule = implode('|', $data);

        if ($merged_line === false) { // +1 line
            if (strlen($rules) != 0) {
                $rules .= "\n";
            }
            $rules .= $rule;
        }

        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($rules)));

        if ($this->type == 'priv') {
            $kbp_attr_data = $privileges[$data['kbp_attr_value']];

        } else {
            $kbp_attr_data = implode('<br />', $roles_names);
        }

        // get html to insert
        $tpl = new tplTemplatez($this->template_dir . 'saml_map.html');

        $v = array(
            'line' => ($merged_line === false) ? count($lines) : $merged_line,
            'saml_attr_name' => $data['saml_attr_name'],
            'saml_attr_value' => $data['saml_attr_value'],
            'kbp_attr_value' => $data['kbp_attr_value'],
            'kbp_attr_data' => $kbp_attr_data,
            'delete_msg' => $this->msg['delete_msg']
        );

        $tpl->tplParse($v, 'rule');
        $html = $tpl->parsed['rule'];
        
        $line_num = count($lines);
        $objResponse->call('showAddedRule', $html, $line_num);

        return $objResponse;
    }


    function ajaxUpdateItem($line_number, $field, $value) {
        $objResponse = new xajaxResponse();
        
        $setting_key = $this->controller->getMoreParam('popup');

        $rules = $this->manager->getSettings(162, $setting_key);
        $rules = explode("\n", $rules);
        
        $parts = explode('|', trim($rules[$line_number]));
        
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        
        $value = str_replace('|', '&#124;', $value);
        $parts[$field] = $value;
        
        $rules[$line_number] = implode('|', $parts);
        $rules = implode("\n", $rules);
        
        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($rules)));
        
        $objResponse->call('emptyGrowls');
        
        if ($field == 2) {
            $script = '$("#rule_%s td.editable_chosen").attr("data-value", "%s");';
            $script = sprintf($script, $line_number, $value);
            $objResponse->script($script);
                
            if ($this->type == 'priv') {
                $script = '$("#rule_%s td.editable_chosen").html("%s");';
                $script = sprintf($script, $line_number, $this->privileges[$value]);
                $objResponse->script($script);
                
                $objResponse->call('makeEditableSelect', '#rule_' . $line_number);
                
            } else {
                $roles = explode(',', $value);
                
                $html = array();
                foreach ($roles as $role_id) {
                    $html[] = $this->roles[$role_id];
                }
                
                $script = '$("#rule_%s td.editable_chosen").html("%s");';
                $script = sprintf($script, $line_number, implode('<br />', $html));
                $objResponse->script($script);
                
                $objResponse->call('makeEditableSelect', '#rule_' . $line_number);
            }
        }

        return $objResponse;
    }
    
    
    function ajaxGetSortableList() {
        $objResponse = new xajaxResponse();
        
        $tpl = new tplTemplatez($this->template_dir . 'extra_items_sortable.html');
        
        $popup = $this->controller->getMoreParam('popup');
        
        $rules = $this->manager->getSettings(162, $popup);
        
        if (empty($rules)) {
            return $objResponse;
        }
        
        
        $rules = explode("\n", $rules);
        
        // current rules
        for ($i = 0; $i < count($rules); $i ++) {
            $rule = explode('|', trim($rules[$i]));

            if (count($rule) < 2) { // this item is broken
                continue;
            }

            $v = $this->msg;
            
            if ($this->type == 'priv') { // kbp privilege
                $kbp_priv_id = $rule[2];
                if (empty($this->privileges[$kbp_priv_id])) {
                    $kbp_attr_data = '--';
                    
                } else {
                    $kbp_attr_data = $this->privileges[$kbp_priv_id];
                }

            } else { // kbp roles
                $kbp_role_ids = explode(',', $rule[2]);
                
                $kbp_attr_data = array();
                foreach ($kbp_role_ids as $role_id) {
                    if (empty($this->roles[$role_id])) {
                        $kbp_attr_data[] = '--';
                        
                    } else {
                        $kbp_attr_data[] = $this->roles[$role_id];
                    }
                }
                
                $kbp_attr_data = implode(', ', $kbp_attr_data);
            }
            
            $v['title'] = sprintf('%s - %s &nbsp;&nbsp;&rarr;&nbsp;&nbsp; %s', $rule[0], $rule[1], $kbp_attr_data);
            $v['line'] = $i;

            $tpl->tplParse($v, 'rule');
        }
        
        $tpl->tplParse($this->msg);
        
        //$objResponse->script("$('.bb_popup').remove();");        
        $objResponse->addAssign('extra_list', 'innerHTML', $tpl->tplPrint(1));
        $objResponse->call('initSort');
    
        return $objResponse;
    }

}
?>