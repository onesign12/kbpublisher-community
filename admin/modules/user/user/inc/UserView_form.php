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

class UserView_form extends AppView
{
    
    var $tmpl = 'form.html';
    var $account_view = false;

    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));

        if(!$this->account_view) {
            $tpl->tplSetNeededGlobal('not_account');
        }
        
        $select = new FormSelect();
        $select->setSelectWidth(250);        
                
        // role
        if($manager->use_role) {
            $tpl->tplSetNeeded('/role_box');
            $link = $this->controller->getFullLink('users', 'user', '', 'role'); // 18-07-2022 eleontev
            $tpl->tplAssign('role_block_tmpl', $this->getRoleBlock($obj, $manager, $link));
        }
        
        //priv
        $this->getPrivBlock($tpl, $obj, $manager);
        
        $action = $this->controller->getAction();
        
        // company
        $company_popup_link = $this->getLink('users', 'company');
        $tpl->tplAssign('company_popup_link', $company_popup_link);
        
        $select->setSelectName('company_id');
        $select->setRange($manager->getCompanySelectRange(), array(0=>'__'));
        $tpl->tplAssign('company_select', $select->select($obj->get('company_id')));        
        
        // state
        require_once 'eleontev/data_arrays/state.php';
        $select->setSelectName('state');        
        $select->setRange($state, array(''=>'___'));
        $tpl->tplAssign('state_select', $select->select(@$obj->get('state')));        
        
        // country
        $select->setSelectName('country');        
        $select->setRange($manager->getCountrySelectRange(), array(0=>'___'));
        $tpl->tplAssign('country_select', $select->select(@$obj->get('country')));                
        
        // status
        if(!$this->account_view) {
            $cur_status = ($action == 'update') ? $obj->get('active') : false;
            $range = $manager->getListSelectRange(true, $cur_status);         
            $range = $this->getStatusFormRange($range, $cur_status);

            $select->resetOptionParam();
            $select->setSelectName('active');
            $select->setRange($range);            
            $tpl->tplAssign('status_select', $select->select($obj->get('active')));        
        }
        
        // api keys
        if(!$this->account_view) {
            $api_rule_id = $manager->extra_rules['api']; 
            $api_data = $obj->getExtraValues($api_rule_id);
            
            $tpl->tplAssign('api_rule_id', $api_rule_id);
            $tpl->tplAssign('ch_api_access', $this->getChecked(!empty($api_data['api_access'])));
        }
        
        if(!$this->account_view && $action == 'update') {
            $tpl->tplSetNeeded('/not_change_pass');
            $tpl->tplAssign('date_formatted_full', $this->getFormatedDate($obj->get('date_registered'), 'datetime'));
            $tpl->tplAssign('date_full_interval', $this->getTimeInterval($obj->get('date_registered'), true));
            
            $lastauth = ($la = $obj->get('lastauth')) ? $this->getFormatedDate($la, 'datetime') : '-';
            $tpl->tplAssign('date_lastauth_formatted_full', $lastauth);

            $lastauth = ($la) ? sprintf('(%s)', $this->getTimeInterval($la, true)) : '-';
            $tpl->tplAssign('date_lastauth_full_interval', $lastauth);
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->setRequestURI($this->controller->getAjaxLink('full'));
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormUser'));
        $xajax->registerFunction(array('generatePassword', $this, 'ajaxGeneratePassword'));
        
        $tpl->tplAssign('generate_pass_block', $this->getGeneratePasswordBlock());
        
        if($action == 'insert') {
            $tpl->tplSetNeeded('/subscribe');
            
            $select->setSelectName('subscription');
            $select->setMultiple(3, true);
            $select->setRange($manager->getSubscriptionSelectRange($this->msg));
            $tpl->tplAssign('subscription_select', $select->select($obj->getSubscription()));
            
            $tpl->tplSetNeeded('/set_by_email');
                    
            // other values
            $xajax->registerFunction(array('fetchCompany', $this, 'ajaxfetchCompany'));
        }
        
                
        if($obj->more_info) {
            $tpl->tplSetNeededGlobal('more_info');
        }

        if(!$this->account_view) {
            if(($action == 'insert' || $action == 'update')) {
                @$val = ($_POST) ? $_POST['notify'] : 0;
                $tpl->tplAssign('notify_ch', $this->getChecked($val));
                $tpl->tplSetNeeded('/notify');
            }
            
            // login as user
            if($action == 'update' && $this->priv->isPriv('login')) {
                $tpl->tplSetNeeded('/login_as_user');
                $tpl->tplAssign('login_user_link', $this->getActionLink('login', $obj->get('id')));
            }
            
            // force email
            if(SettingModel::getQuick(1, 'username_force_email')) {
                $tpl->tplSetNeeded('/username_force_email');
            }
            
            // tabs
            if ($obj->get('id')) {
                $tpl->tplAssign('menu_block', UserView_common::getEntryMenu($obj, $manager, $this));
            }
        }
        
        @$val = ($_POST) ? $_POST['not_change_pass'] : 1;
        $tpl->tplAssign('pass_change_checked', $this->getChecked($val));
        
        $vars = $this->setCommonFormVars($obj);
        $vars['privilege_tip_msg'] = $this->getPrivMessage($manager, $vars);  
        
        // change cancel link to detail
        if(isset($_GET['back'])) {
            $vars['cancel_link'] = $this->getActionLink('detail', $obj->get('id'));              
        }
        
        $tpl->tplAssign($vars);
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getRoleBlock($obj, $manager, $module = 'users', $page = 'user', $no_button = false) {

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'user/user/template/block_role_template.html');
        
        $roles = $this->stripVars($manager->getRoleRecords());
        $roles = $manager->getRoleSelectRangeFolow($roles);
        
        $range = array();
        foreach($obj->getRole() as $role_id) {
            $range[$role_id] = $roles[$role_id];
        }        
        
        $select = new FormSelect();
        $select->select_tag = false;
        $select->setRange($range);


        $tpl->tplAssign('role_select', $select->select());
        $tpl->tplAssign('module', $module);
        $tpl->tplAssign('page', $page);
        $tpl->tplAssign('no_button', ($no_button) ? 'true' : 'false');  

        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }
    
    
    function getPrivBlock(&$tpl, $obj, $manager) {
        
        $disable_select = false;
        if(!$this->account_view) {
            $au = KBValidateLicense::getAllowedUserRest($manager);
            if($au !== true) {
                if($au < 0) {
                    $disable_select = true;
                    if($this->controller->action == 'insert') {
                        $this->controller->go('', true);
                    } elseif(!$obj->getPriv()) {
                        $this->controller->go('', true);
                    }
                }
        
                // disable priv select
                if($au == 0) {
                    if($this->controller->action == 'insert') {
                        $disable_select = true;
                    }
        
                    if($this->controller->action == 'update' && !$obj->getPriv()) {
                        $disable_select = true;
                    }
                }
            }
        }
        
        if($manager->use_priv) {
        
            $range = $manager->getPrivSelectRange();
            if($disable_select) {
                $range = array();
                $file = AppMsg::getCommonMsgFile('license_msg.ini');
                $exceed_users_note = AppMsg::parseMsgsMultiIni($file, 'license_exceed_users_note');
                $tpl->tplAssign('exceed_users_note', $exceed_users_note);            
            }

            $select = new FormSelect();
            $select->setSelectWidth(250); 
            $select->setSelectName('priv');
            $select->setRange($range, array(''=>'__'));
        
            $tpl->tplAssign('priv_select', $select->select($obj->getPriv()));
            $tpl->tplSetNeeded('/priv_box');
        }
    }
    
    
    function getGeneratePasswordBlock() {

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'user/user/template/block_generate_password.html');
        
        $tpl->tplAssign('password_hint', htmlentities(PasswordUtil::getWeakPasswordError()));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }
    
    
    function getPrivMessage($manager, $vars) {
        $priv_msg = $this->stripVars($manager->getUserPrivMsg());
        $html[] = '<ul>';
        foreach($priv_msg as $k => $v) {
            $html[] = '<li><b>' . $v['name'] . ':</b></li>';
            $html[] = nl2br($v['description']);
        }
        $html[] = '</ul>';
        $html = str_replace('{privileges}', implode('', $html), $vars['privilege_tip_msg']);
    
        return $html;
    }
    
    
    function ajaxFetchCompany($email) {
        $objResponse = new xajaxResponse();
        
        $email = explode('@', $email);
        if (count($email) != 2) {
            return $objResponse;
        }
        
        $domain = $email[1];
        $company = $this->manager->company_manager->getByDomain($domain);
        
        if (empty($company)) {
            $objResponse->addAlert($this->msg['not_found_msg']);
            
        } else {
            $objResponse->script(sprintf('$("#company_id").val(%s)', $company['id']));
        }

        return $objResponse;
    }
    
    
    function ajaxGeneratePassword() {
        $objResponse = new xajaxResponse();
        
        $password = WebUtil::generatePassword(4,3,3);
        
        $objResponse->addScript("random_password = '$password'");
        $objResponse->assign('random_password', 'innerHTML', $password);
        
        return $objResponse;
    }
    
    
    function ajaxValidateFormUser($values, $options = array()) {
        if ($this->controller->module == 'account') {
            $values['not_change_pass'] = 1;
        }
        
        $objResponse = $this->ajaxValidateForm($values, $options);
        
        return $objResponse;
    }
    
}
?>