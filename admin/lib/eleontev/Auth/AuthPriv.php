<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+


/*
in priv module table, kbp_priv_module 
check_priv  1 = standart, check priv and availabe to set priv in priveledge
            2 = some kind of duplicate of priv area, check priv but not show priviledge interface
                for example we use it for kb_entry
                but it still working for getPrivArea - excellent !!! 
*/

class AuthPriv extends Auth
{
    
    const WRONG_REMOTE_PARAMS_ERROR  = 1;
    const EMAIL_EXISTS_ERROR         = 2;
    const NO_EMAIL_ERROR             = 3;
    const SYSTEM_ERROR               = 4;
    
    const MFA_SETUP                  = 5;
    const MFA_REQUIRED               = 6;
    
    var $use_exit_screen = true;
    var $skip_check_priv = false;
    var $as_name = 'auth_';
    var $auth;
    var $thua;
    var $bad_attempt = 3;
    
    var $ps_name = 'priv_';
    var $priv_area;                      // news, user, ...
    var $user_id;                        
    
    var $own_sql = false;                // to make check for owm records
    var $own_use = false;                // to make check for owm records
    var $own_field;                      // author field in table
    var $own_param = '1=1';              // select all records
    var $entry_status_sql;
    
    var $priv_actions = array('select','insert','update','status','delete');
    var $custom_actions = array(
        'detail' => 'select',
        'clone'  => 'insert',
        'bulk'   => 'update',
        'trash'  => 'delete'
    );
    
    var $tables = array(
        'user', 'user_role', 'user_to_role', 'user_extra', 'user_temp', 'user_auth_token',
        'priv', 'priv_rule', 'priv_name', 'priv_module'
        );
        
    var $tbl;
    var $cookie_expire = '2 week';
    var $skip_auth_expired;
    
    var $user_ldap_token;
    var $log_prefix = 'Default';
    
    
    function __construct() {
        parent::__construct();
    }
    
    
    // return true or false
    // set all nessesary sessions
    function doAuth($username, $password, $md5 = true, $mfa = false) {        

        if(is_array($md5)) {
            $mfa = (isset($md5['mfa'])) ? $md5['mfa'] : false;
            $md5 = (isset($md5['md5'])) ? $md5['md5'] : true;
        }
        
        $keys = ['auth_allow_email', 'auth_concurrent', 'saml_mfa', 'mfa_policy'];
        $options = SettingModel::getQuick([1, 162], $keys);
        $options['md5'] = $md5;
        
        // rewrite settings, need for api 
        if(is_array($md5)) {
            $options = array_replace($options, $md5);
        }
        
        $auth = false;
        $row = $this->getAuth($username, $password, $options);
        
        if($row) {
            
            // set exeption that mfa required, then redirected to mfa
            if($mfa) {
                $priv_id = $this->_getUserPriv($row['user_id']);
                if(MfaAuthenticator::isRequired($row['user_id'], $priv_id, $mfa)) {
                    MfaAuthenticator::setSession(['user_id' => $row['user_id']]);
                    throw new AuthPrivException ('MFA required', self::MFA_REQUIRED);
                }
            }
            
            $auth = true;
            $date = time();
            $this->setLastAuth($row['user_id'], $date);
            $this->authToSession($this->as_name, $row['user_id'], $row['username'], $options);
            
            $priv_id = $this->_getUserPriv($row['user_id']);
            if($priv_id) {
                $this->privToSession($priv_id);
            }
            
            $role = $this->_getUserRole($row['user_id']);
            if($role) {
                $this->roleToSession($this->as_name, $role);
            }
            
            // concurrent logins
            if(!$options['auth_concurrent']) {
                $this->saveSessionId($row['user_id'], AuthPriv::getSessionId());
            }
            
            // mustread
            if(AppPlugin::isPlugin('mustread')) {
                MustreadPlugin::mustreadToSession($row['user_id']);
            }
        }
        
        return $auth;
    }
    
    
    function _getUserPriv($user_id) {
        $sql = "
        SELECT 
            priv_name_id
        FROM 
            {$this->tbl->priv} p
        WHERE 1
            AND p.user_id = '%d'";
        
        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('priv_name_id');        
    }
        
    
    function isAuth() {
        return $this->_isAuth('auth_', $this->check_ip);
    }
    
    
    // use where we quick check if authentificated
    static function isAuthSession($use_ip) {
        return Auth::_isAuthSession('auth_', $use_ip);
    }
    
    
    function setSkipAuthExpired($val) {
        $this->skip_auth_expired = ($this->skip_auth_expired || $val);
    }
    
    
    // spentTime(session var[,time in minute]){
    // Unset all sessions if curent time > "time in minute"
    // if $how_long = false we do not use it
    function setAuthExpired($how_long){
        if($how_long && !$this->skip_auth_expired) {
            $exp_time = @$_SESSION[$this->as_name]['time_flag']+$how_long*60;
            $cur_time = time();
            
            if($cur_time < $exp_time){
                $_SESSION[$this->as_name]['time_flag'] = $cur_time;
            } else {
                $this->logout(false);
            }
        }
    }
    

    static function logout($remove_cookie = true) {
        $_SESSION['auth_'] = array();
        unset($_SESSION['auth_']);
        
        $_SESSION['priv_'] = array();
        unset($_SESSION['priv_']);
        
        if($remove_cookie) {
            self::removeCookie();
        }
    }
    

    // COOKIE // ----------------------
    
    // used in remember me on login allowed only https
    function authToCookie($period = false, $data = false, $path = '/') {
        $secure = self::getCookieSecure();
        parent::authToCookieByName('kb_authc_', $period, $data, $path, "", $secure, true);
    }
    
    static function removeCookie() {
        parent::removeCookieByName('kb_authc_');
    }
    
    static function getCookie() {
        return @$_COOKIE['kb_authc_']; 
    }
    
    static function removeAllCookie() {
        $cookies = array(
            'kb_window_width_', 'kb_selector_', 'kb_recent_setting_', 
            'kb_category_id_', 'kb_sidebar_width_', 'kb_pool_',
            'kb_spell_ignore_', 'kb_cookieconsent_',
            'CKFinder_Settings', 'CKFinder_Path', 'kb_mustread_'
        );
        
        foreach($cookies as $k => $name) {
            if(isset($_COOKIE[$name])) {
                parent::removeCookieByName($name);
            }
        }
    }
    
    
    // ROLE // -------------------------
    
    

    // PRIV // -------------------------
    
    // if we need to change some actions to common actions
    function setCustomAction($custom_action, $priv_action) {
        $this->custom_actions[$custom_action] = $priv_action;
    }    
    
    
    function setPrivArea($priv_area) {
        $this->priv_area = $priv_area;
        $this->own_use = false;
        if(!empty($_SESSION[$this->as_name]['user_id'])) {
            $this->user_id = $_SESSION[$this->as_name]['user_id'];
        }
    }
    
    
    // set sql for select own records
    function setOwnParam($own_param) {
        $this->own_param = $own_param;
        $this->own_use = true;
    }
    
    
    // get own param, should be used in selecting records
    function getOwnParam($priv_area = false) {
        $param = ($this->isSelfPriv('select', $priv_area)) ? $this->own_param : '1=1';
        return $param;
    }
    
    
    // set sql for unable to edit not own records 
    function setOwnSql($sql) {
        $this->own_sql = $sql;
    }    
    
    
    // user id for certain record
    function _selectUserId($sql = false) {
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->RecordCount();
    }
    
    
    function setEntryStatusSql($sql) {
        $this->entry_status_sql = $sql;
    }        
    
    
    function _selectEntryStatus($sql) {
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('status');
    }    
    
    
    function selectEntryStatus() {
        if($this->entry_status_sql) {
            return $this->_selectEntryStatus($this->entry_status_sql);
        }
        
        return false;
    }
    
    
    // to check status priv, priv with ranges - articles, users, etc.
    // parse $statuses and striped not allowed if any
    function getPrivStatusSet($statuses, $with_action = 'select') {
        
        $priv = false;
        if($with_action == 'update') {
            $priv = $this->checkPrivAction('status'); // to check with real own status (with db query)
        } else {
            $priv = $this->isPriv('status'); // to check without db query            
        }
        
        //echo '<pre>Action: ', print_r($with_action, 1), '</pre>';
        //echo '<pre>Range: ', print_r($statuses, 1), '</pre>';
        
        // if status allowed
        if($priv) {

            // parse/remove statuses if has allowed statuses only
            if(isset($_SESSION[$this->ps_name][$this->priv_area]['sp']['status'])) {
                $s = $_SESSION[$this->ps_name][$this->priv_area]['sp']['status'];
                $statuses = array_flip(array_intersect(array_flip($statuses), $s));    
            }
        
        // not allowed set status, reset it,
        // we do not chek here to return at least one status
        } else {
            $statuses = array();
        }
        
        //echo '<pre>Range after priv check: ', print_r($statuses, 1), '</pre>';
        return $statuses;
    }
    
    
    // check for action with certain status
    function isPrivStatusAction($action, $priv_area = false, $entry_status = false) {
        $ret = true;
        $priv_area = ($priv_area) ? $priv_area : $this->priv_area;
        
        if(!empty($_SESSION[$this->ps_name][$priv_area]['sp'][$action])) {
            $entry_status = ($entry_status !== false) ? $entry_status : $this->selectEntryStatus();
            
            if($entry_status !== false) {
                if(!in_array($entry_status, $_SESSION[$this->ps_name][$priv_area]['sp'][$action])) {
                    $ret = false;
                }
            }
        }
        
        return $ret; 
    }    
    
    
    // if user have any restriction for actions with status
    function isPrivStatusActionAny($action, $priv_area = false) {
        $ret = false;
        $priv_area = ($priv_area) ? $priv_area : $this->priv_area;
        if(!empty($_SESSION[$this->ps_name][$priv_area]['sp'][$action])) {
            $ret = true;
        }
        
        return $ret; 
    }    
    
    
    function getPrivStatusAction($action, $priv_area = false) {
        $ret = array();
        $priv_area = ($priv_area) ? $priv_area : $this->priv_area;
        if(!empty($_SESSION[$this->ps_name][$priv_area]['sp'][$action])) {
            $ret = $_SESSION[$this->ps_name][$priv_area]['sp'][$action];
        }
        
        return $ret; 
    }
    
    
    // $author_field - field in table where user_id stored
    // in news_record the field is 'author_id'
    function check($priv_action = 'select', $priv_area = false, $entry_status = false) {
    
        $priv = false;
        $priv_area = ($priv_area) ? $priv_area : $this->priv_area;
        $real_action = $priv_action;
        
        // change to commom action if needed
        if(isset($this->custom_actions[$priv_action])) {
            $priv_action = $this->custom_actions[$priv_action];
        }
        
        // if not acction assigned change to select
        if(empty($priv_action)) { 
            $priv_action = 'select'; 
        }
        

        // check for wrong param to functions 
        $extra_priv = false;
        if(!in_array($priv_action, $this->priv_actions)) {
            
            // get extra priv, then will be set as $force_own_check in checkPrivAction
            // for users we have array(0=>login) and it works for own checking own priv 
            $extra_priv = $this->getExtraPrivByPrivArea($priv_area);
            if(!in_array($priv_action, $extra_priv)) {
                exit("Wrong param fo function CheckPriv::check($priv_action). Correct it!");                
            }
        }
        
        
        // if we have check_priv = 0 in db (no priv area) or all priv
        if($priv_area === false || isset($_SESSION[$this->ps_name]['all'])) {
            return true;
        }
        
        // only if isset this chapters to enter
        if(isset($_SESSION[$this->ps_name][$priv_area])) {
            
            // if no any restrictions for action with statuses
            $psa = $this->isPrivStatusAction($priv_action, $priv_area, $entry_status);
            
            if($psa) {
                // own check will be used only if self priv in array (self_[action]) and action 
                // status, update or delete or $extra priv is not false 
                $priv = $this->checkPrivAction($priv_action, $priv_area, $extra_priv);
            }
        }
        
        //echo "<pre>"; print_r($this); echo "</pre>";
        if(!$priv && $this->use_exit_screen) { 
            echo $this->errorMsg();
            exit();
        }
        
        return $priv;
    }
    
    
    // check for certain priv action 
    // use $force_own_check to check for actions different than 'update', 'status', 'delete'
    // $force_own_check will be used in check function if extra priv available, for users 'login' for example
    function checkPrivAction($priv_action, $priv_area = false, $force_own_check = false) {
        
        $priv = false;
        if($this->privInArray($priv_action, $priv_area)) {
            $priv = true; 
    
        // check if only own record can modify
        } elseif($this->privInArray('self_' . $priv_action, $priv_area)) { 
            
            // for delete, update, change_status only own records
            $st = array('update', 'status', 'delete');
            if(in_array($priv_action, $st) || $force_own_check) {
                $priv = ($this->own_sql) ? $this->_selectUserId($this->own_sql) : true;
                // echo '<pre>', print_r($this->own_sql, 1), '</pre>';
                //echo '<pre>', print_r($priv, 1), '</pre>';
                            
            } elseif($priv_action == 'select') {
                $priv = true;
            }
        }
        
        return $priv;
    }

    
    function CheckSimpleStatus() {
        $priv = $this->privInArray('status');
        return $priv;      
    }

    
    // return true if user has self - privileges
    function isSelfPriv($priv_action, $priv_area = false) {
        return $this->privInArray('self_' . $priv_action, $priv_area);
    }    
    
    
    // we use it AppView 
    // here we do not check for real self priv
    function isPriv($priv_action, $priv_area = false) {
        $priv = false;
        if($this->privInArray($priv_action, $priv_area)) {
            $priv = true;
        } elseif($this->privInArray('self_' . $priv_action, $priv_area)) {
            $priv = true;
        }
        
        return $priv;
    }
    
    
    // only specified priv
    function isPrivConcrete($priv_action, $priv_area = false) {
        $priv = false;
        if($this->privInArray($priv_action, $priv_area)) {
            $priv = true;
        }
        
        return $priv;
    }    
    
    
    // check for priv optional
    function isPrivOptional($priv_action, $priv_optional, $priv_area = false) {
        $priv = false;
        $priv_area = ($priv_area) ? $priv_area : $this->priv_area;
        $priv_ses = (isset($_SESSION[$this->ps_name])) ? $_SESSION[$this->ps_name] : [];
        $priv = AuthPriv::isPrivOptionalStatic($priv_action, $priv_optional, $priv_area, $priv_ses);
        
        return $priv;
    }
    
    
    // check for priv optional
    static function isPrivOptionalStatic($priv_action, $priv_optional, $priv_area, $data) {
        $priv = false;
        if(isset($data[$priv_area]['op'][$priv_action])) {
            $optional = $data[$priv_area]['op'][$priv_action];
            if(in_array($priv_optional, $optional)) {
                $priv = true;
            }
        }
        
        return $priv;
    }
    
    
    // Returns TRUE if user has priv($priv_action), FALSE otherwise
    function privInArray($priv_action, $priv_area = false) {
        $priv = false;
        $priv_area = ($priv_area) ? $priv_area : $this->priv_area;
        
        // skip
        if($this->skip_check_priv) {    
            $priv = true;
        
        // admin
        } elseif(isset($_SESSION[$this->ps_name]['all'])) {
            
            // for admin should not return true for sef priv
            if(strpos($priv_action, 'self_') !== false) {
                $priv = false;
            } else {
                $priv = true;
            }
            
        } elseif(!isset($_SESSION[$this->ps_name][$priv_area])) {
            $priv = false;
        
        } elseif (in_array($priv_action, $_SESSION[$this->ps_name][$priv_area])) {
            $priv = true;
            
            // now checjed in checkPriv in module
            // only as draft allowed 
            // // $priv_action = str_replace('self_', '', $priv_action);
            // if($this->isPrivOptional($priv_action, 'draft', $priv_area)) {
                // $priv = false;
                
                // echo '<pre>', print_r($priv_area, 1), '</pre>';
                // echo '<pre>', print_r($priv_action, 1), '</pre>';
                // echo '<pre>', print_r("==============", 1), '</pre>';
            // }
        }
        
        return $priv;
    }


    // select for priv  
    // creating array with priv for current user
    function privToSession($priv_id){
        
        $sql = "
        SELECT 
            m.id AS module_id,
            m.module_name, 
            r.what_priv, 
            r.status_priv,
            r.optional_priv,
            r.apply_to_child, 
            n.id AS priv_id,
            n.name AS priv_name
            
        FROM ({$this->tbl->priv_rule} r, 
             {$this->tbl->priv_name} n,
             {$this->tbl->priv_module} m)
        WHERE 1
            AND r.priv_name_id = '{$priv_id}'
            AND r.active = 1
            AND n.active = 1            
            AND m.active = 1
            AND m.id = r.priv_module_id
            AND n.id = r.priv_name_id";
        
        //$sql = sprintf($sql, $priv_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        while($row = $result->FetchRow()){
            
            // add to auth session
            $_SESSION[$this->as_name]['priv_id'] = $row['priv_id'];
            $_SESSION[$this->as_name]['priv_name'] = $row['priv_name'];
            
            // creating session $_SESSION['module'] => array(what_priv)
            $_SESSION[$this->ps_name][$row['module_name']] = explode(',',$row['what_priv']);
            
            // status priv
            if($row['status_priv']) {
                $_SESSION[$this->ps_name][$row['module_name']]['sp'] = unserialize($row['status_priv']);
            }
            
            // optional priv
            if($row['optional_priv']) {
                $_SESSION[$this->ps_name][$row['module_name']]['op'] = unserialize($row['optional_priv']);
            }
            
            if($row['module_name'] == 'all' || $row['apply_to_child'] == 0) { 
                continue; 
            }
            
            $to_childs_ar[] = array('id'=>$row['module_id'], 'name'=>$row['module_name']);
        }
        
        if(isset($to_childs_ar)) {
            $tree_ar =& $this->getCheckPrivTreeArray();
            foreach($to_childs_ar as $k => $v) {
                if(isset($tree_ar[$v['id']])) {
                    $this->childPrivToSession($tree_ar, $v['name'], $v['id']);
                }
            }
        }
        
        //echo "<pre>"; print_r($_SESSION[$this->ps_name]); echo "</pre>";
        //exit;        
        
        if(!isset($_SESSION[$this->ps_name]['all'])) {
            $this->parentPrivToSession();
        }
    }
    
    
    // place all parent modules in session
    // to be able to show menu, sub menu etc.
    function parentPrivToSession() {

        $sql = "SELECT 
        p1.module_name AS parent,
        p2.module_name AS child,
        p1.by_default
        FROM {$this->tbl->priv_module} p1
        LEFT JOIN {$this->tbl->priv_module} p2 ON p2.parent_id = p1.id
        WHERE p2.check_priv = 1
        AND p2.active = 1
        ORDER BY p2.sort_order";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()){
            
            if(!$row['child']) { continue; }
            
            // home tab fix, always display it
            if($row['parent'] == 'home' && !isset($_SESSION[$this->ps_name][$row['child']])) {
                $_SESSION[$this->ps_name][$row['parent']][] = 'select';
            }
            
            // if top item selected but not selected by_default item
            // it will skipped and not change by_defalt value, it will be incorrect                
            if(isset($_SESSION[$this->ps_name][$row['child']]) && !isset($_SESSION[$this->ps_name][$row['parent']]) && $row['parent'] != 'all') {
                $_SESSION[$this->ps_name][$row['parent']][] = 'select';
                $data[$this->ps_name][$row['parent']][] = 'select';
                        
                // change by_default (sub_page menu) will be used in menu generation
                if($row['by_default']) {
                    if($row['by_default'] != $row['child']) {
                        $_SESSION[$this->ps_name][$row['parent']]['by_default'] = $row['child'];
                    }
                }
            }
        }
    }
        
    
    // return array with all modules that need to be check for priv
    function &getCheckPrivTreeArray() {
        
        $sql = "SELECT * FROM {$this->tbl->priv_module} WHERE check_priv = 1 AND active = 1 ORDER BY sort_order";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()){
            $array[$row['parent_id']][$row['id']] = $row['module_name'];
        }
        
        return $array;
    }
    
    
    // set priv session to all child modules from 
    function childPrivToSession($array, $module_name, $parent_id = 0) {
    
        //echo "<pre>"; print_r($array); echo "</pre>";
        //echo "<pre>"; print_r($_SESSION[$this->ps_name]); echo "</pre>";
        //echo "<pre>"; print_r($_SESSION[$this->ps_name][$module_name]); echo "</pre>";
        //exit;
    
        foreach($array[$parent_id] as $module_id => $v) { 
        
            if(isset($_SESSION[$this->ps_name][$v])) {
                $arr = array_merge($_SESSION[$this->ps_name][$v], $_SESSION[$this->ps_name][$module_name]);
                $arr = array_unique($arr);

                // remove not self if self exists
                foreach($arr as $k => $action) {
                    if(!is_array($action)) {
                        if(strpos($action, 'self_') !== false) {
                            $full_action = str_replace('self_', '', $action);
                            $full_action_key = array_search($full_action, $arr);
                            if($full_action_key) {
                                unset($arr[$full_action_key]);
                            }
                        }                    
                    }
                }

                $_SESSION[$this->ps_name][$v] = $arr;
            
            } else {
                $_SESSION[$this->ps_name][$v] = $_SESSION[$this->ps_name][$module_name];
            }
            
            if(isset($array[$module_id])){
                $this->childPrivToSession($array, $v, $module_id);
            }
        }
    }
    
    
    // return priv area starting from last parameter
    // as parameters get possible values getPrivArea($val,$val2);
    function getPrivArea($val) {
        
        $args = func_get_args();
        $numargs = func_num_args();
        
        // in some places we have the same values for $val1, $val2
        // to avoid it and generate apropriate sql
        for($i=0;$i<$numargs;$i++){
            if(isset($args[$i+1])) {
                if($args[$i] == $args[$i+1]) {
                    unset($args[$i+1]);
                }
            }
        }
        
        for($i=0;$i<$numargs;$i++){
            if(empty($args[$i])) { continue; }
            
            $as_sql[]  = "p{$i}.module_name AS module_{$i}, p{$i}.check_priv AS check_{$i}";
            $where_sql[] = "AND p{$i}.module_name = '{$args[$i]}'";
            if($i!=0) {
                $a = $i-1;
                $join_sql[] = "LEFT JOIN {$this->tbl->priv_module} p{$i} ON p{$i}.parent_id = p{$a}.id";
            }
        }
        
        $as_sql = implode(',', $as_sql);
        $join_sql = (isset($join_sql)) ? implode(' ', $join_sql) : '';
        $where_sql = implode(' ', $where_sql);
        
        $sql = "SELECT {$as_sql} FROM {$this->tbl->priv_module} p0 {$join_sql} 
        WHERE p0.parent_id = '0' {$where_sql}";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $row = $result->FetchRow();
        
        //in case if do not have $priv_area  (unable select by GET parameters)
        $priv_area = 'qw1212HGF%nkdf&^$etqweuqbJHG';
        
        if(!empty($row)) {
            $num = (count($row)/2)-1;
            for($i=$num;$i>=0;$i--){
                if($row['check_' . $i] != 0) {
                    $priv_area = $row['module_' . $i];
                    break;
                }
            }
        }
        
        return $priv_area;
    }
    
    
    function getExtraPrivByPrivArea($priv_area) {
        $ret = array();
        $sql = "SELECT extra_priv FROM {$this->tbl->priv_module} WHERE module_name = '{$priv_area}' LIMIT 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        if($res = $result->Fields('extra_priv')) {
            $ret = explode(',', str_replace('self_', '', $res));
        }
        
        return $ret;
    }
    
    
    static function errorMsg() {
        
        // for api to check priv
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        if($conf['debug_db_error'] === 'api') {
            register_shutdown_function(array('KBApiError','shutdownCheckPriv'));
            return;
        }
        
        
        $msgs = array(
            'title'=>'ACCESS DENIED!',
            'body' =>'You do not have enough permissions for this operation or access this module.<br><b>&laquo; <a href="#" onclick="javascript:history.back(); return false;">back</a></b> | <b><a href="' . $conf['admin_path'] . '">home</a></b>
            ');
        
        $options = array('page' => 1);
        return BoxMsg::factory('error', $msgs, array(), $options);
    }
    
    
    function resetPriv() {
        $_SESSION[$this->as_name]['priv_id'] = 0;
        unset($_SESSION[$this->as_name]['priv_id']);        
    
        $_SESSION[$this->ps_name] = array();
        unset($_SESSION[$this->ps_name]);
    }
    
    
    function addPriv($priv_action, $priv_area = false) {
        $priv = false;
        $priv_area = ($priv_area) ? $priv_area : $this->priv_area;
        $_SESSION[$this->ps_name][$priv_area][$priv_action] = $priv_action;
    }    

    
    // GETTERS // --------------------------------
    
    static function getSessionId() {
        return @$_SESSION['auth_']['auth'];
    }
        
    static function getUsername() {
        return @$_SESSION['auth_']['username'];
    }
    
    static function getUserId() {
        return @$_SESSION['auth_']['user_id'];
    }
    
    static function getPrivId() {
        return @$_SESSION['auth_']['priv_id'];
    }
    
    static function getRoleId() {
        return @$_SESSION['auth_']['role_id'];
    }
    
    static function getPassExpired() {
        return @$_SESSION['auth_']['pass_expired'];
    }
    
    static function setPassExpired($val) {
        if($val) {
            $_SESSION['auth_']['pass_expired'] = $val;
        
        } elseif(isset($_SESSION['auth_']['pass_expired'])) {
            $_SESSION['auth_']['pass_expired'] = 0;
            unset($_SESSION['auth_']['pass_expired']);
        }
    }
    
    
    static function isAdmin() {
        $ret = false;
        if(isset($_SESSION['priv_']['all'])) {
            $ret = (count($_SESSION['priv_']['all']) == 5);
        }        
    
        return $ret;
    }
    
    static function getPrivAllowed($priv_area) {
        if(AuthPriv::isAdmin()) {
            return $_SESSION['priv_']['all'];
        }
        
        if(isset($_SESSION['priv_'][$priv_area])) {
            return $_SESSION['priv_'][$priv_area];
        }
        
        return array();
    }
    
    
    static function getRemoteUsername() {
        return (isset($_SESSION['auth_']['remote_username'])) ? $_SESSION['auth_']['remote_username'] : null;
    }
    
    static function isSaml() {
        return (isset($_SESSION['auth_']['saml']));
    }    
    
    static function setRemoteAutoAuth($val = 1) {
        $_SESSION['auth_']['remote_auto_auth'] = $val;
    }

    static function isRemoteAutoAuth() {
        return (!empty($_SESSION['auth_']['remote_auto_auth']));
    }    

    // also tried to check what area alowed for remote auth
    static function isRemoteAutoAuthArea($area) {
        $ret = false;
        if(!empty($_SESSION['auth_']['remote_auto_auth'])){
            $ret = ($area <= $_SESSION['auth_']['remote_auto_auth']);
        } 
        
        return $ret;
    }

    static function saveUserData($data) {
        $_SESSION['user_data_'] = serialize($data);
    }

    static function getUserData() {
        $ret = false;
        if(isset($_SESSION['user_data_'])) {
            $ret = unserialize($_SESSION['user_data_']);
        }
        
        return $ret;
    }


    // LOGS // -----------------

    static function removeNewline($string) {
        return preg_replace(array("#[\n\r\t]#", "#\s{2,}#"), '', $string);
    }


    function putLog($msg, $prefix = false) {
        if(!empty($this->log)) {
            $prefix_ = ($prefix) ? $prefix : $this->log_prefix;
            $this->log->putLogin($msg, $prefix_);
        }
    }


    function addLog($user_id, $username, $type, $exitcode) {
        if(!empty($this->log)) {
            $this->log->AddLogin($user_id, $username, $type, $exitcode);
        }
    }
    
    
    function writeIncompleteRemoteUserLog($mapped_user, $auth_type) {
        if(!empty($this->log)) {
            $this->putLog('Not enough parameters returned by remote server.');
            $this->putLog('Returned mapped values: ' . print_r($mapped_user, 1));
        
            $exitcode = 3;
            $username = (isset($mapped_user['username'])) ? addslashes($mapped_user['username']) : '';
            $this->putLog(sprintf('Exit with the code: %d', $exitcode));
            $this->addLog(0, $username, $auth_type, $exitcode);
        }
    }


    // after authetication try
    function logAuth($values, $exitcode, $auth_type = false) {
        $user_id = (AuthPriv::getUserId()) ?: 0;
        $username = addslashes($values['username']);
        // $auth_type = ($this->auth_remote) ? AuthProvider::getAuthType() : 'local';
        // $auth_type = (!empty($vallues['auth_type'])) ? $vallues['auth_type'] : AuthProvider::getAuthType();
        $auth_type = ($auth_type) ?: AuthProvider::getAuthType();
                
        $this->log->putLogin(sprintf('Exit with the code: %d', $exitcode));
        $this->log->addLogin($user_id, $username, $auth_type, $exitcode);
    }


    // after successful authentication
    function postAuth() {        
        $this->log->putLogin('Login successful');
        UserActivityLog::add('user', 'login');
        CaptchaManager::resetCaptchaValues();
    }    
}


class AuthPrivException extends \Exception {}
?>