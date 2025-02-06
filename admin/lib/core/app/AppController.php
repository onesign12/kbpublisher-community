<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007-2021 Evgeny Leontev                                    |
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


class AppController extends Controller
{
    
    var $action = false;
    var $page_to_return;
    
    var $query = array(
        'module'   =>'module',
        'page'     =>'page',
        'sub_page' =>'sub_page',
        'action'   =>'action',
        'id'       =>'id',
        'show_msg' =>'show_msg'
        );
    
    
    var $actions = array(
        'insert'=>'insert',
        'update'=>'update',
        'delete'=>'delete',
        'status'=>'status',
        'detail'=>'detail'
        );
    
    var $id_key = 'id';
    var $self_page;
    var $base_href;
    var $module;
    var $page;
    var $sub_page;
    var $rp;
    
    // it should be added to params is isset
    // when we return from good operations or from "cancel" from form
    var $params = array('module', 'page', 'sub_page');
    var $more_params = array(
        'bpr', 'sort', 'order', 'letter', 'filter', 
        'popup', 'frame', 'field_name', 'field_id', 
        'referer', 'bp', 'bpt', 'no_attach', 'close', 'vars'
        );
                             
    var $more_params_ajax = array();
    
    var $custom_return_params = array();
    var $custom_page_to_return;
    
    var $delay_time = '1000'; // js delay time on after action screen
    var $lang = APP_LANG;
    var $module_dir = APP_MODULE_DIR;
    var $plugin_dir = APP_PLUGIN_DIR;
    var $account_module_dir = APP_ACCOUNT_MODULE_DIR;
    var $extra_modules = array();
    
    var $working_dir;
    var $full_page;
    var $full_page_params;
    var $arg_separator = '&amp;';
    
    var $msg = array();
    var $encoding;
    
    
    function __construct() {
        
        parent::__construct();
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        $this->encoding = $conf['lang']['meta_charset'];
        
        $this->base_href = $conf['client_path'];
        // $this->self_page = $_SERVER['PHP_SELF'];
        $this->self_page = htmlspecialchars($_SERVER['PHP_SELF']);
        // maybe need to change to fixed but too many changes to /account
        // used in admin and client
        // $this->self_page = $conf['admin_path'] . 'index.php';
        // $this->self_page_client = $conf['client_path'] . 'index.php';
        
        $this->action = $this->getRequestVar('action');
        $this->module = $this->getRequestVar('module');
        $this->page   = $this->getRequestVar('page');
        $this->sub_page = $this->getRequestVar('sub_page');
        
        
        $this->working_dir = $this->module_dir . $this->module . '/';
        $this->setCommonLink();
    }
    
    
    // when we need to add additonal lang file
    // actually we need it to add msg to view 
    function addMsg($file_name, $module = false) {
        
        if($module) {
            $file_name = AppMsg::getModuleMsgFile($module, $file_name);
        } else {
            $file_name = AppMsg::getCommonMsgFile($file_name);
        }        
        
        $this->msg = array_merge($this->msg, AppMsg::parseMsgs($file_name));
    }    
    
    
    function getRequestVar($var) {
        return (isset($_GET[$this->query[$var]])) ? urlencode(urldecode($_GET[$this->query[$var]])) : NULL;
    }
    
    
    function getRequestKey($key) {
        return @$this->query[$key];
    }
    
    
    function setRequestVar($var, $value) {
        $_GET[$this->query[$var]] = $value;
    }
    
    
    function getCurrentLink() {
        $query = http_build_query($_GET);
        return ($query) ? $this->self_page . '?' . $query : $this->self_page;
    }
    
    
    function getCommonLink() {
        return $this->full_page;
    }
    
    
    //full_page
    function setCommonLink() {
        $this->full_page = $this->getFullPageUrl();
        $this->full_page_params = $this->getFullPageParams();
    }
    
    
    function getAction() {
        return $this->action;
    }
    
    
    // to change values for action key
    function setCustomAction($action, $value) {
        $this->actions[$action] = $value;
    }
    
    
    function getActionValue($action_key) {
        return @$this->actions[$action_key];
    }
    
    
    function getFullPageUrl() {
        return $this->self_page . '?' . http_build_query(array_merge($this->getParams(), $this->getMoreParams()));
    }
    
    
    function getFullPageParams() {
        return array_merge($this->getParams(), $this->getMoreParams());
    }    
    
    
    // to generate main part of link
    function &_getParams($params) {
        $params_ = array();
        foreach($params as $v) {
            // echo '<pre>', print_r($v, 1), '</pre>';
            if(isset($_GET[$v])) {
                $params_[$v] = $_GET[$v];
            }
        }
        
        return $params_;
    }


    function &getParams() {
        return $this->_getParams($this->params);
    }

    
    function getMoreParams() {
        return $this->_getParams($this->more_params);
    }

    
    // set one more param
    //  more params always stay in GET if initialized
    function setMoreParams($val, $rebuilt_params = true) {
        $val = (is_array($val)) ? $val : array($val);
        foreach($val as $v) {
            $this->more_params[] = $v;    
        }
        
        if($rebuilt_params) {
            $this->setCommonLink();
        }        
    }
    
    
    function removeMoreParams($val, $rebuilt_params = true) {
        $val = (is_array($val)) ? $val : array($val);
        foreach($val as $v) {
            $key = array_search($v, $this->more_params);
            if($key) {
                unset($this->more_params[$key]);
            }            
        }
        
        if($rebuilt_params) {
            $this->setCommonLink();
        }
    }
    
    
    function setMoreParamsAjax($val) {
        $val = (is_array($val)) ? $val : array($val);
        foreach($val as $v) {
            $this->more_params_ajax[] = $v;    
        }
    }
    
    
    function getMoreParam($param) {
        $ret = false;
        if(is_array($param)) {
            $ret = (!empty($_GET[$param[0]][$param[1]])) ? $_GET[$param[0]][$param[1]] : false;
        } else {
            $ret = (!empty($_GET[$param])) ? $_GET[$param] : false;
        }
        
        if($ret) {
            $ret = (is_array($ret)) ? array_map('urlencode', $ret) : urlencode($ret);
        }
        
        return $ret;
    }
    
    
    function setCustomPageToReturn($page, $unserialize = true) {
        @$link = WebUtil::unserialize_url($page);
        $this->custom_page_to_return = ($link === false) ? $page : $link;
    }
        
    
    // set js delay time on after action screen
    // for $multiplier use 0.5, 1, 2 so on 
    // it will set delay time = 1000*$multiplier
    function setDelayTime($multiplier) {
        $this->delay_time = $this->delay_time*$multiplier;
    }
    
    
    // example: $client_link = ['index', $cate_id, $entry_id, ...]
    function getRefererLink($referer, $client_link = array()) {
        $link = '';
        if(strpos($referer, 'client') !== false) {
            $link = $this->getClientLink($client_link);

        } elseif(strpos($referer, 'emode') !== false) {
            $link = $this->getClientLink($client_link);

        } elseif($referer) {
            $link = WebUtil::unserialize_url($referer);
        }

        return $link;
    }
    
    
    function go($msg = 'success', $same_page = false, $after_msg = false, $action = false) {
        
        // activity
        $entry_id = $this->getRequestVar('id');
        $action = (!$action) ? $this->action : $action;
        
        $extra_data = false;
        if ($action == 'bulk') {
            
            if (in_array($this->rp->vars['bulk_action'], array('delete', 'trash'))) {
                $action = $this->rp->vars['bulk_action'];
                $entry_id = $this->rp->vars['id'];
                
            } else {
                $action = 'bulk_update';
                $extra_data = array(
                    'bulk_action' => $this->rp->vars['bulk_action'],
                    'ids' => $this->rp->vars['id']
                );
                
                if (!empty($this->rp->vars['value'][$extra_data['bulk_action'] . '_action'])) {
                    $extra_data['bulk_sub_action'] = $this->rp->vars['value'][$extra_data['bulk_action'] . '_action'];
                }
            }
        }
        
        if ($action != 'skip') {
            UserActivityLog::addAction($this->module, $this->page, $action, $entry_id, $extra_data);
        }
        
        // page to redirect
        $page = $this->getGoLink($msg, $same_page, $after_msg);
        
        if($same_page) {
            header("location: $page");
            exit();
        }
        
        // msg for crowl
        if($msg === 'success') {
            if(in_array($this->getRequestVar('action'), array('insert', 'clone'))) {
                $msg = 'added';
                
            } elseif($this->getRequestVar('action') == 'update') {
                $msg = 'updated';            
            
            } elseif(in_array($this->getRequestVar('action'), array('delete', 'trash'))) {
                $msg = 'deleted';
            
            } elseif($this->getRequestVar('action') == 'bulk') {
                $msg = 'data_updated';
            
            } else {
                $msg = 'success'; // Operation successfully completed
            }
            
        } elseif($msg === 'csrf') {
            $msg = 'csrf-error';
        }

        $_SESSION['success_msg_'] = $msg;

        header("location: $page");
        exit();
    }
    
    
    function getGoLink($msg = 'success', $same_page = false, $after_msg = false) {
        
        ini_set('arg_separator.output', '&');
        $this->arg_separator = '&';
		$this->full_page = self::_replaceArgSeparator($this->full_page);
        
        // means on the same page dispaly message
        // message generated in index.php
        if($same_page) {
            $params = array($this->getRequestKey('show_msg') => $msg);
            $page = $this->full_page . $this->arg_separator . http_build_query($params);
            return $page;
        }
        
        // after msg
        $params = array();
        if($after_msg) {
            $params = array($this->getRequestKey('show_msg') => $after_msg);
            $this->full_page = $this->full_page . $this->arg_separator . http_build_query($params);
        }        
        
        // page to return 
        $page_to_return = $this->full_page;
        if($this->custom_page_to_return) {
			$page_to_return = self::_replaceArgSeparator($this->custom_page_to_return);
        }

        return $page_to_return;
    }


    function goUrl($url) {
        $url = $this->_replaceArgSeparator($url);
        header("Location: " . $url);
        exit();
    }

    
    function goPage($module = false, $page = false, $sub_page = false, $action = false, $more = array()) {
        $link = $this->getLink($module, $page, $sub_page, $action, $more);
        $link = $this->_replaceArgSeparator($link);
        
        header("location: $link");
        exit();        
    }
    
    
    function getGoPageLink($module = false, $page = false, $sub_page = false, $action = false, $more = array()) {
        $link = $this->getLink($module, $page, $sub_page, $action, $more);
        return $this->_replaceArgSeparator($link);        
    }
    
    
    function setWorkingDir($dirs = array()) {
    
        $ar[] = (isset($dirs['sub_page'])) ? $dirs['sub_page'] : $this->sub_page;
        $ar[] = (isset($dirs['page']))     ? $dirs['page']     : $this->page;
        $ar[] = (isset($dirs['module']))   ? $dirs['module']   : $this->module;
        
        if(!file_exists($this->module_dir . 'config_path.php')) { return; }
        require_once $this->module_dir . 'config_path.php';
        
        foreach($ar as $k => $v) {
            if(isset($conf_module[$v])){
                if(strpos($conf_module[$v], '{') !== false) {
                    $r = [ // 14-07-2022 eleontev
                        '{plugin_dir}'   => $this->plugin_dir,
                        '{account_dir}' => $this->account_module_dir
                    ];
                    $this->working_dir = strtr($conf_module[$v], $r);
                } else {
                    $this->working_dir = $this->module_dir . $conf_module[$v];
                }
                
                break;
            }
        }
        
        // echo '<pre>' . print_r($this->working_dir, 1) . '</pre>';
        // exit;
    }
    
    
    function getLink($module = false, $page = false, $sub_page = false, $action = false, $more = array()) {
        
        $options = [];
        $options['more'] = $more;
        
        // $module as array with extra options
        if(is_array($module)) {
            $options = array_merge($options, $module);
            @$module = $module['module'];
        }
        
        // determine by getRequestVar to use below
        $params = ['module', 'page', 'sub_page', 'action'];
        foreach($params as $p) {
            $options[$p] = $this->getRequestVar($p);
        }
        
        if($module == 'all') {
            $link = $this->_getLink($options); 
                                   
        // remove $action and $more
        } elseif($module == 'main') {
            $moptions = array_diff_key($options, array_flip(['action', 'more']));
            $link = $this->_getLink($moptions); 
        
        // with full GET patams
        } elseif($module == 'full') {
            $more = array_merge($this->getFullPageParams(), $options['more']);
            $options['more'] = array_diff_key($more, array_flip($params));
            
            $link = $this->_getLink($options); 
                                                              
        } else {
            
            $options = [];
            $options['more'] = $more;
            
            $args = func_get_args();
            foreach($params as $k => $p) {
                if(isset($args[$k])) {
                    $options[$p] = ($args[$k] == 'this') ? $this->getRequestVar($params[$k]) : $args[$k];
                }
            }
            
            // echo '<pre>' . print_r($params, 1) . '</pre>';
            // echo '<pre>' . print_r($args, 1) . '</pre>';
            // echo '<pre>' . print_r($options, 1) . '</pre>';
            // exit;
            
            $link = $this->_getLink($options);
        }
        
        return $link;
    }
    
    
    private function _getLink($options) {
        
        // maiin part
        $params = [1 => 'module', 'page', 'sub_page', 'action'];
        foreach($params as $num => $page) {
            if(!empty($options[$page])) { 
                $link[$num] = sprintf('%s=%s', $this->getRequestKey($page), $options[$page]); 
            }
        }
        
        $self_page = $this->self_page;
        if(!empty($options['public'])) {
            $self_page = $this->self_page_client;
        }
        
        $more = (!empty($options['more'])) ? $options['more'] : [];
        
        $link = (empty($link)) ? $self_page : $self_page . '?' . implode($this->arg_separator, $link);
        $link .= ($more) ? $this->arg_separator . http_build_query($more) :  '';
        
        return $link;
    }
    
    
    function _getActionLink($action_value, $record_id = false, $more_params = array()) {

        $arg = $this->arg_separator;
        // $common_link = $this->getCommonLink();
        $common_link = $this->getLink('full');
        $action_key = $this->getRequestKey('action');

        $id_param = ($record_id) ? sprintf("%s%s=%d", $arg, $this->id_key, $record_id) : '';

        $action_val = $this->getActionValue($action_value);
        $action_value = ($action_val) ? $action_val : $action_value;
        $more_params = ($more_params) ?  $arg . http_build_query($more_params) : '';

        $str = '%s%s%s=%s%s%s';
        $str = sprintf($str, $common_link, $arg, $action_key, $action_value, $id_param, $more_params);

        return $str;
    }


    // $more_params - array(key=value, key=value);
    function getActionLink($action_val, $record_id = false, $more_params = array()) {
        return $this->_getActionLink($action_val, $record_id, $more_params);
    }
    
    
    function getShortLink($module = false, $page = false, $sub_page = false, $action = false, $more = array()) {
        $this->self_page = '';
        $link = $this->getLink($module, $page, $sub_page, $action, $more);
        $link = str_replace('?', '', $link);
        
        return $link;
    }    
    
    
    function getFullLink($module = false, $page = false, $sub_page = false, $action = false, $more = array()) {
        $self_page = $this->self_page;
        $this->self_page = APP_ADMIN_PATH . 'index.php';
        $link = $this->getLink($module, $page, $sub_page, $action, $more);
        $this->self_page = $self_page;
        
        return $link;
    }
    
    
    // truing to make compatible with IIS 
    function getAjaxLink($module = false, $page = false, $sub_page = false, $action = false, $more = array()) {
        
        $more = RequestDataUtil::addslashes($more); // to escape xajaxargs 2019-03-22 eleontev
        
        if(empty($more) && isset($_GET['id'])) {
            $more = array('id' => (int) $_GET['id']);
        }
        
        // add extra get to ajax, example in for example in list
        foreach($this->more_params_ajax as $v){
            if(isset($_GET[$v])) {
                $more[$v] = addslashes($_GET[$v]);
            }
        }
        
        $more['ajax'] = 1;
        $link = $this->getLink($module, $page, $sub_page, $action, $more);
        $link = $this->_replaceArgSeparator($link);
        
        return $link;
    }
    
    
    // to generate links to admin area and to be redireted after login 
    // used in e-mails and in client area
    static function getRefLink($module = false, $page = false, $sub_page = false, $action = false, 
                                $more = array(), $replace_arg = true) {

        $p = APP_ADMIN_PATH . 'index.php';
        if($module) {        
            $c = new AppController();
            $more['r'] = 1; // means save url if not logged and redirect to requestet page after login
            $link = $c->getShortLink($module, $page, $sub_page, $action, $more);
            if($replace_arg) {
                $link = $c->_replaceArgSeparator($link);
            }
            
            $p = $p . '?' . $link;
        }
        
        return $p;
    }
    
    
    function getClientLink($client_link, $serialize = false) {
        $cc = &$this->getClientController();
        @$link = $cc->getRedirectLink($client_link[0], $client_link[1], $client_link[2], $client_link[3], $client_link[4]);
        if($serialize) {
            $link = WebUtil::serialize_url($link);
        }
        
        return $link;
    }


    // get rewrite link to entry
    function getPublicLink($view, $data, $more = []) {
        static $cc;
        
        if($cc === null) {
            $settings = SettingModel::getQuick(2);
            $cc = $this->getClientController($settings);
            $cc->setModRewrite($settings['mod_rewrite']);
        }
        
        $entry_id = $data['id'];
        if($view != 'file') {
            $entry_id = $cc->getEntryLinkParams($data['id'], $data['title']);
        }
        
        $link = $cc->getLink($view, false, $entry_id, false, $more, true);
        
        // if($serialize) {
            // $link = WebUtil::serialize_url($link);
        // }
        
        return $link;
    }    


    // link to client_path/endpoint.php, add https if admin has https to avoid ssl errors
    static function getAjaxLinkToFile($type, $more = array()) {
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');        
        
        $client_path = APP_CLIENT_PATH;
        if($conf['ssl_admin']) {
            $client_path = str_replace('http://', 'https://', APP_CLIENT_PATH);
        }
        
        $more = RequestDataUtil::addslashes($more); // to escape xajaxargs 2019-03-22 eleontev
        $link = $client_path . 'endpoint.php';
        $link .= ($type) ? '?type=' . $type : '';
        $link .= ($more) ? '&' . http_build_query($more) : '';
        $link = AppController::_replaceArgSeparator($link);
        return $link;
    }

    
    // function setSelfPage($page = 'reset') {
        // static $default_page;
        // if($default_page === null) {
        //     $default_page = $this->self_page;    
        // }
        // 
        // if($page == 'client') {
        //     $this->self_page = $this->self_page_client;
        // } elseif($page == 'reset') {
        //     $this->self_page = $default_page;
        // }
    // }


	static function isAjaxCall() {
		return (!empty($_GET['ajax']));
	}


    static function _replaceArgSeparator($str) {
        return str_replace('&amp;', '&', $str);
    }
    
    
    static function &getClientController($settings = []) {
        $controller = new KBClientController();
        $controller->kb_path = APP_CLIENT_PATH;
        $controller->link_path = APP_CLIENT_PATH;
        
        if($settings) {
            $controller->setDirVars($settings);
        }

        // $client_dir = APP_CLIENT_DIR . 'client/inc/';
        // $view_format = (!empty($settings['view_format'])) ? $settings['view_format'] : 'default';
        // $controller->working_dir = $client_dir . $view_format . '/';
            
        return $controller;
    }
    
    
    function goView(&$obj, &$manager, $class, $values = array()) {
        $view = new $class;
        $view->execute($obj, $manager, $values);
    }
    
    
    function getView(&$obj, &$manager, $class, $values = array()) {
        $view = new $class;        
        return $view->execute($obj, $manager, $values);
    }
    
    
    function isClass($class_name, $path = false) {
        
        $class_name = $class_name . '.php';
        
        if($path) {
            $p[] = $this->module_dir . $path . '/inc/';
            $p[] = $this->plugin_dir . $path . '/inc/';
            
            foreach($p as $v) {
                if(file_exists($v . $class_name)) {
                    return true;
                }
            }
        } else {
            if(file_exists($this->working_dir . 'inc/' . $class_name)) {
                return true;
            }
        }
        
        return false;
    }    
}
?>
