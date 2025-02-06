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


class KBClientController
{
    var $query  = array(
        'view'           =>'View',
        'page'           =>'Page',
        'category_id'    =>'CategoryID',
        'entry_id'       =>'EntryID',
        'entry_title'    =>'EntryTitle',
        'category_title' =>'CategoryTitle',
        'msg'            =>'Msg',
        'bp'             =>'bp',
        'message_id'     =>'message_id',
        'access_key'     =>'ak' 
        );
    
    var $request;
    
    var $working_dir;
    var $working_path;    
    var $kb_path;
    var $client_path;
    var $setting = array();
    
    var $mod_rewrite = false;
    var $url_replace_rule = array();
    var $encoding;  
    
    var $extra_params = array();
    var $dirs = array();
    var $arg_separator = '&amp;';
    var $auth_ended = false;
    var $admin_login = false; // used for login page in admin/

    var $debug;
    var $kb_dir;
    var $admin_path;
    var $skin_dir;
    var $skin_path;
    var $common_dir;
    var $default_working_dir;
    
    var $entry_title;
    var $view_key;
    var $view_id;
    var $category_id;
    var $category_title;
    var $entry_id;
    var $page_id;
    var $msg_id;
    var $link_path;
    
    function __construct() {
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        
        $this->debug = $conf['debug_info'];
        $this->encoding = $conf['lang']['meta_charset'];
        if(isset($conf['lang']['replace'])) {
            $this->url_replace_rule = $conf['lang']['replace'];
        }
        
        $this->setUrlVars();
    }
    
    
    function setDirVars(&$settings) {
    
        $this->kb_path   = APP_CLIENT_PATH;
        $this->link_path = APP_CLIENT_PATH;
        $this->setting   = &$settings;
                
        $this->kb_dir      = APP_CLIENT_DIR;
        $this->client_path = $this->kb_path . 'client/'; 
        $this->admin_path = APP_ADMIN_PATH; 
        $this->skin_dir    = APP_CLIENT_DIR . 'client/skin/';
        $this->skin_path   = $this->kb_path . 'client/skin/';
        
        $client_dir = APP_CLIENT_DIR . 'client/inc/';
        $this->common_dir          = $client_dir;
        $this->default_working_dir = $client_dir . 'default/';
        $this->working_dir         = $client_dir . $settings['view_format'] . '/';
    }
    
    
    function setUrlVars() {

        $this->category_id = (int) $this->getRequestVar('category_id');
        if($this->category_title = $this->getRequestVar('category_title')) {
            $this->category_title = urldecode($this->category_title);
        }

        $this->entry_id = (int) $this->getRequestVar('entry_id');
        if($this->entry_title = $this->getRequestVar('entry_title')) {
            $this->entry_title = urldecode($this->entry_title);
        }
        
        $this->view_key = $this->getRequestKey('view');
        $view_id = $this->getRequestVar('view');
        $this->view_id = ($view_id) ? $view_id : 'index';
        
        // $this->page_key = $this->getRequestKey('page');
        $page_id = $this->getRequestVar('page');
        $this->page_id = ($page_id) ? $page_id : false;
        
        $this->msg_id = $this->getRequestVar('msg');
    }
    
    
    function setSettings(&$settings) {
        $this->setting = &$settings;
    }
    
    
    function getSetting($setting_key) {
        return @$this->setting[$setting_key];
    }
    
    
    function setModRewrite($var) {
        if($var == 2 || $var == 3) {
            $this->mod_rewrite = $var;
            
        } elseif($var == 1) { // automatic
            $this->mod_rewrite = (!empty($_SERVER['KBP_REWRITE'])) ? 2 : false;
        
        } else {
            $this->mod_rewrite = false;
        }
    }
    
    
    function getRequestVar($var) {
        return (isset($_GET[$this->query[$var]])) ? urlencode(urldecode($_GET[$this->query[$var]])) : NULL;
    }
    
    
    function getRequestKey($key) {
        return @$this->query[$key];
    }
    
    
    function goUrl($url) {
        $url = $this->_replaceArgSeparator($url);
        header("Location: " . $url);
        exit();
    }
    
    
    function setCrowlMsg($msg_key) {
        $_SESSION['success_msg_'] = $msg_key;
    }
    
    
    function go($view = false, $category_id = false, $entry_id = false, $msg_key = false, 
                    $more = array(), $growl = false) {
        
        $this->arg_separator = '&';
        ini_set('arg_separator.output', '&');
        
        if(empty($more)) {
            $more = array();
        }
        
        // hash to anchor to correct message in comments
        $hash = '';
        if (!empty($more['message_id'])) {
            $hash = '#c' . $more['message_id'];
            unset($more['message_id']);
        }
        
        
        // growl instead of msg
        if($growl) {
            $_SESSION['success_msg_'] = $msg_key;
            $msg_key = false;
        }
        
        
        // to avoid success_go, use crowl
        // to redirect to correct page and set session success_msg_ ...
        $success_go = false;
        if($view === 'success_go') {
            
            $sgo = $this->getView('success_go');
            $view = $sgo->getViewId($msg_key);
            $msg_id = $sgo->getMsgId($msg_key); // msg in url

            // if we redirect with msg (msg_id) then no need in clrowl, message will be on page
            if(!$msg_id) {
                $_SESSION['success_msg_'] = $sgo->getMsgIdToDisplay($msg_key);
            }

            $msg_key = $msg_id;// msg in URL
            
            //copied from KBClientView_success_go.php, not sure what it did
            // set to 0 correct display msg
            $category_id = ($msg_id && !$category_id) ? 0 : $category_id;
        }

        // for url title
        if($view == 'entry' && $this->mod_rewrite == 3) {
            if($this->entry_title) {
                $entry_id = $this->getEntryLinkParams($entry_id, false, $this->entry_title);
            } else {
                $data = KBClientQuickModel::getEntryTitles($entry_id, $view);
                $entry_id = $this->getEntryLinkParams($entry_id, $data['title'], $data['url_title']);
            }
        }

        // for url category title
        if($view == 'index' || $view == 'files' && $this->mod_rewrite == 3) {
            if($this->category_title && $category_id) {
                $category_id = $this->getEntryLinkParams($category_id, false, $this->category_title);
            }
        }

        
        $url = $this->getLink($view, $category_id, $entry_id, $msg_key, $more);
        $url .= $hash;
        
        // echo '<pre>', print_r($url, 1), '</pre>';
        // exit;
        
        header("Location: " . $url);
        exit();
    }    
    
    
    // function getFullUrl($params, $more_params = array()) {
    // 
    //     $query = false;
    //     if($params || $more_params) {
    //         $params = array_merge($params, $more_params);
    //         $query = http_build_query($params);
    //     }
    // 
    //     return ($query) ? $_SERVER['PHP_SELF'] . '?' . $query : $_SERVER['PHP_SELF'];
    // 
    // }
    
    
    function getLink($view = false, $category_id = false, $entry_id = false, $msg_key = false, $more = array(), $more_rewrite = false) {
        if($view == 'all') {
        
            if($_GET) {
                $diff = array_diff(array_keys($_GET), $this->query);
                foreach($diff as $v) {
                    $more[$v] = $_GET[$v];
                }
            }
            
            $view = $this->view_id;
            if($this->page_id) {
                $view = array($this->view_id, $this->page_id);
            }
            
            return $this->_getLink($view, $this->category_id, $this->entry_id, 
                                    $this->msg_id, $more, $more_rewrite);     
        
        } else {
            
            if($view == 'this') {
                $view = $this->getRequestVar('view');
                if($this->page_id) {
                    $view = array($this->getRequestVar('view'), $this->getRequestVar('page'));
                }            
            }
            
            $category_id  = ($category_id == 'this') ? $this->getRequestVar('category_id') : $category_id;
            $entry_id    = ($entry_id == 'this') ? $this->getRequestVar('entry_id') : $entry_id;
            $msg_key    = ($msg_key == 'this') ? $this->getRequestVar('msg') : $msg_key;
            return $this->_getLink($view, $category_id, $entry_id, $msg_key, $more, $more_rewrite);
        }
    }
    
    
    function getLinkNoRewrite($view = false, $category_id = false, $entry_id = false, $msg_key = false, 
                                $more = array()) {
        
        $rewrite = $this->mod_rewrite;
        $this->mod_rewrite = 0;
        $link = $this->getLink($view, $category_id, $entry_id, $msg_key, $more);
        $this->mod_rewrite = $rewrite;
    
        return $link;
    } 
    
        
    // truing to make compatible with IIS 
    function getAjaxLink($view = false, $category_id = false, $entry_id = false, $msg_key = false, $more = array()) {
        $more['ajax'] = 1;
        $more = RequestDataUtil::addslashes($more); // to escape xajaxargs 2019-03-22 eleontev
        $link = $this->getLink($view, $category_id, $entry_id, $msg_key, $more);
        $link = $this->_replaceArgSeparator($link);
        return $link;
    }
    
    
    function getRedirectLink($view = false, $category_id = false, $entry_id = false, $msg_key = false, $more = array()) {
        $link = $this->getLink($view, $category_id, $entry_id, $msg_key, $more);
        $link = $this->_replaceArgSeparator($link);        
        return $link;
    }    
    
    
    function getFolowLink($view = false, $category_id = false, $entry_id = false, $msg_key = false, $more = array()) {
        $link = $this->getLink($view, $category_id, $entry_id, $msg_key, $more);
        $link = $this->_replaceArgSeparator($link);        
        return $link;
    }
        
    
    static function getAdminRefLink($module = false, $page = false, $sub_page = false, $action = false, $more = array(), $replace_arg = true) {
        
        return AppController::getRefLink($module, $page, $sub_page, $action, $more, $replace_arg);
    }
    
    
    // admin_path force to https or http, dpends on ssl settings  
    function getAdminJsLink() {
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
             
        if($conf['ssl_client']) {
            $link = str_replace('http://', 'https://', APP_ADMIN_PATH);    
        } else {
            $link = str_replace('https://', 'http://', APP_ADMIN_PATH);
        }
        
        return $link;
    }
    
    
    private function _getLink($view = 'index', $category_id = false, $entry_id = false, $msg_key = false, 
                                $more = array(), $more_rewrite = false) {
        
        $link = array();
        $kb_path = $this->link_path;
        $with_category_id = array(
            'index', 'files', 'news', 'featured',
            'print-cat', 'pdf-cat', 'entry_add');
        $category_id = (in_array($view, $with_category_id)) ? $category_id : false;
        
        if($view == 'news' && $entry_id) { // as the same view for category and entry
            $category_id = false;
        }
        
        // msg on index page
        $force_index = ($view == 'index' && !$category_id && !$entry_id && $msg_key);
        $view = ($view == 'index' && !$force_index) ? false : $view;
            
        if(!$this->mod_rewrite) {
        
            if($view) { 
                if(is_array($view)) {
                    $link[0] = sprintf('%s=%s', $this->getRequestKey('view'), $view[0]); 
                    $link[1] = sprintf('%s=%s', $this->getRequestKey('page'), $view[1]); 
                } else {
                    $link[1] = sprintf('%s=%s', $this->getRequestKey('view'), $view); 
                }
            }
            
            // if($view)        { $link[1] = sprintf('%s=%s', $this->getRequestKey('view'), $view); }
            if($category_id) { $link[2] = sprintf('%s=%d', $this->getRequestKey('category_id'), $category_id); }
            if($entry_id)    { $link[3] = sprintf('%s=%d', $this->getRequestKey('entry_id'), $entry_id); }
            if($msg_key)     { $link[4] = sprintf('%s=%s', $this->getRequestKey('msg'), $msg_key); }
            if($more)        { $link[5] = http_build_query($more); }
            if($this->extra_params) { $link[6] = http_build_query($this->extra_params); }
            
            if($view == 'form') {
                $link = $kb_path . 'index.php';
            } else {
                $link = (!$link) ? $kb_path : $kb_path . 'index.php?' . implode($this->arg_separator, $link);
            }
        
        // entry title in url 
        } elseif($this->mod_rewrite == 3 && is_array($entry_id)) {
            $link = $entry_id['title'] . '_' . $entry_id['id'] . '.html';
            $link = ($view != 'entry') ? $view . '/' . $link : $link;
            $link = (@!$link) ? $kb_path : $kb_path . $link;
            // echo '<pre>', print_r($link, 1), '</pre>';
            
            if($msg_key) {
                $more[$this->getRequestKey('msg')] = $msg_key;
            }
                   
            if($more) {
                $link .= '?' . http_build_query($more);
            }
        
        // category title in url 
        } elseif($this->mod_rewrite == 3 && is_array($category_id)) {
            $link = $category_id['title'] . '-' . $category_id['id'].'/';
            $link = ($view && $view != 'entry') ? $view . '/' . $link : $link;
            $link = (@!$link) ? $kb_path : $kb_path . $link;
            // echo '<pre>', print_r($link, 1), '</pre>';
            
            if($more) {
                $link .= '?' . http_build_query($more);
            }
        
        // rewrite = /entry/1
        } else {
        
            if($view) { 
                if(is_array($view)) {
                    $link[0] = sprintf('%s', $view[0]); 
                    $link[1] = sprintf('%s', $view[1]); 
                } else {
                    $link[1] = sprintf('%s', $view); 
                }
            }
            
            // if($view)        { $link[1] = sprintf('%s', $view); }
            if($category_id) { $link[2] = sprintf('%d', $category_id); }
            if($entry_id)    { $link[3] = sprintf('%d', $entry_id); }
            if($msg_key)     { $link[4] = sprintf('%s', $msg_key); }            
            
            $link = (@!$link) ? $kb_path : $kb_path . implode('/', $link) . '/';
        
            $link_extra = array();
            if($more) {
                if($more_rewrite) {
                    $link .= implode('/', $more) . '/';
                } else {
                    $link_extra[1] = http_build_query($more);
                }
            }
            
            if($this->extra_params) { 
                $link_extra[2] = http_build_query($this->extra_params); 
            }
        
            if($link_extra) {
                $link .= '?' . implode($this->arg_separator, $link_extra);
            }
        }
        
        return $link;
    }
    
    
    function _replaceArgSeparator($str) {
        return str_replace('&amp;', '&', $str);
    }
    
    
    function getUrlTitle($str, $maxlen = 100) {
        
        $str = strtr($str, $this->url_replace_rule);
        @$str = html_entity_decode($str, ENT_QUOTES, $this->encoding);
    
       $rule  = array('#[\s–—]#' => '-', // white space, en dash, em dash
                       '#[.,!?&<>\'":;/\[\]\|=‘’”"“–…«»\#%]#' => '',
                       '#-{2,}#' => '-');
        
        $str = preg_replace(array_keys($rule), $rule, $str);
        $str = _substr($str, 0, $maxlen);
        
        return $str;
    }
    
    
    function getEntryLinkParams($entry_id, $entry_title, $url_title = false) {
        if($this->mod_rewrite == 3) {
            $url_title = ($url_title) ? $url_title : $this->getUrlTitle($entry_title);
            $url_title = _strtolower($url_title);
            $entry_id = array('id' => $entry_id, 'title' => $url_title);
        }
        
        return $entry_id;
    }
        
    
    function loadClass($view_id = false, $type = 'View') {
        if(!$view_id){
            $view_id = $this->view_id;
        }        
        
        $class = 'KBClientView_'. $view_id;
        
        $files = array();
        $files[]  = $this->working_dir . 'view/' . $class . '.php';
        $files[]  = $this->default_working_dir . 'view/' . $class . '.php';
        
        foreach($files as $file) {
            if(file_exists($file)) {
                return $class;
            }            
        }
        
        if($this->debug) {
            exit("KBClientController::loadClass($view_id) - Unable to load file $class");
        } else {
            $this->goStatusHeader('404');
        }
    }
    
    
    function &getView($view_id = false) {
        
        if(!$view_id){
            $view_id = $this->view_id;
        }
        
        $class = $this->loadClass($view_id);
        $class = new $class;
        return $class;
     }
    
    
    //could be called in some action to replace current action
    function getAction($view_id = false) {
        
        if(!$view_id){
            $view_id = $this->view_id;
        }
        
        $class = 'KBClientAction_'. $view_id;
        
        $files = array();
        $files[]  = $this->working_dir . 'action/' . $class . '.php';
        $files[]  = $this->default_working_dir . 'action/' . $class . '.php';
        $files[]  = $this->working_dir . 'action/KBClientAction_default.php';
        
        foreach($files as $file) {
            if(file_exists($file)) {
                return new $class;
            }
        }
    }    
    
    
    function getSearchParamsOnLogin($params) {
        
        $search_params = array(
            's', 'q', 'in', 'custom', 
            'et', 'c', 'cp', 'cf',
            'period', 'pv', 'is_from', 'is_to');
        
        $more = array();
        
        foreach($search_params as $v) {
            if(isset($params[$v])) {
                if(is_array($params[$v])) {
                    foreach($params[$v] as $k2 => $v2) {
                        $more[$v][$k2] = $v2;
                    }
                } else {
                    $more[$v] = $params[$v];
                }
            }
        }
        
        return $more;
    }
    
    
    function goStatusHeader($header, $view = 'index', $category_id = false, $entry_id = false, $msg_key = false) {
        if($header == '301') {
            header ('HTTP/1.1 301 Moved Permanently');
            $this->go($view, $category_id, $entry_id, $msg_key);
        
        } elseif(in_array($header, [302, 307])) {
            header (sprintf('HTTP/1.1 %s Moved Temporaryly', $header));
            $this->go($view, $category_id, $entry_id, $msg_key);
        
        } elseif($header == '404') {
            header("HTTP/1.1 404 Not Found");
            
            if($this->mod_rewrite) {
                $this->goUrl($this->link_path . '404.html');
            } else {
                $this->go('404');
            }
        }
    }
    
    
    function goAccessDenied($view = 'index') {
        $this->go($view, false, false, 'access_denied'); // display meesage on page 
        // $this->go('success_go', false, false, 'access_denied'); // in growl
    }   
    
    
    function setCanonicalHeader($url) {
        header(sprintf('Link: <%s>; rel="canonical"', $url));
    }
    
    
    function checkRedirect() {
        
        // glossary print moved 4.0
        if($this->view_id == 'print' && $this->msg_id == 'glossary') {
            $this->goStatusHeader(301, 'print-glossary');
        
        // map moved to Page second parameter  7.0 -> 7.0.1
        } elseif ($this->view_id == 'map' && isset($_GET['type'])) {
            $this->goStatusHeader(301, array('map', addslashes($_GET['type'])));
        
        // member to account  7.5 -> 8.0
        } elseif (strpos($this->view_id, 'member') !== false) {
            $this->goStatusHeader(301, 'account');
        }
        
        // check settings and plugins
        if(!KbClientBaseModel::isModuleByView($this->view_id, $this->setting)) {
            // $this->go();
            $this->goStatusHeader(307);
        }
    }

    
    function checkAuth($ajax = false) {
        
        // check for inactivity time
        $this->auth_ended = false;
        if(AuthPriv::getUserId()) {
            
            $auth = new AuthPriv;
            $auth->setCheckIp($this->setting['auth_check_ip']);
            
            $auth_expired = $this->setting['auth_expired'];
            $auth->setAuthExpired($auth_expired);
            
            if(!$auth->isAuth()) {
                $auth->logout(false);
                $this->auth_ended = true;
            }
        }
    }
     
     
    function checkAuthRegisteredOnly() {
            
        // registered only
        if(!AuthPriv::isAuthSession($this->setting['auth_check_ip']) && $this->setting['kb_register_access']) {
            
            $not_private = array(
                'register', 'login', 'password', 'confirm', 'sso', 'mfa', 'success_go'
            );
            
            if(!in_array($this->view_id, $not_private)) {
                $login_msg = ($this->auth_ended) ?  'authtime_' . $this->view_id : $this->view_id . '_enter';
            
                // set entry_id
                $entry_id = $this->entry_id;
                if(!$this->entry_id && $this->category_id) {
                    $entry_id = $this->category_id;
                }
                
                // search params
                $more = array();
                if($this->view_id == 'search') {
                    $more = $this->getSearchParamsOnLogin($_GET);
                }
                
                // ajax logout
                if(isset($_GET['ajax'])) {
                    if(KBClientAjax::isAjaxRequest()) {
                        $str = sprintf('<html>%s</html>', KBClientAjax::getlogout());
                        echo $str;
                        ob_end_flush();
                        exit;
                    }
                }
                
                $this->go('login', $this->category_id, $entry_id, $login_msg, $more);
            }
        }
    }
    
    
    /* Auto auth works on cookies
    No cookies no auth authentication
    token  saved to cookie saved and saved to table kbp_user_auth_token 
    then we validate both records  
    On logout cookies removed  so no auth auth worked after logout*/
    
    function checkAutoLogin() {
                
        // saml auto
        if(!AuthPriv::getUserId() && $this->view_id != 'login') {
            $saml_auto = (AuthProvider::isSamlAuth() && AuthProvider::isSamlAuto());
        
            if($saml_auto && empty($_SESSION['saml_attempts_'])) {
                $_SESSION['saml_attempts_'] = 1;
                $more = array('sso' => 1, 'return' => $this->getLink('all'));
                $this->go('login', $this->category_id, $this->entry_id, false, $more);
            }    
        }
        
        
        // keep me signed, cookie exists and logged out
        // $auth_cookie removed on saml login so it should work 
        $auth_cookie = AuthPriv::getCookie();
        if(!AuthPriv::getUserId() && $auth_cookie) {
            
            $remove_auth = true;
            @list($selector,$validator) = explode(':', $auth_cookie);
                
            $log = new LoggerModel;
            $log->putLogin('Initializing...');
            
            $auth_type = 'auto'; // auto
            $exitcode = 3; // error
            $user_id = 0;
            $username = '';
            
            // auto login allowed (https, settings)
            if($this->isAutoLoginAllowed()) {
                
                $auth = new AuthPriv; 
                $data = $auth->isValidRememberAuth($selector, $validator);
                
                if($data) {
                    
                    // validate remote user data, changed or not
                    $user_remote_token = '';
                    $rvalid = true;
                    if(AuthProvider::isRemoteAuth()) {
                        $load_remote_error = AuthRemote::loadEnviroment();
                        $provider = AuthProvider::getAuthProvider();
                        
                        if($provider == 'ldap') {
                        
                            $rvalid = false;
                            $remote_provider_id = AuthProvider::getProviderId($provider);
                            
                            if(!empty($data['ruid'][$remote_provider_id])) {
                                $sso_user_id = $data['ruid'][$remote_provider_id];
                                $ldap_setting = SettingModel::getQuick(array(160));
                                $user_remote_token = AuthLdap::getUserTokenByUid($sso_user_id, $ldap_setting);
                                $rvalid = ($data['remote_token'] && ($data['remote_token'] == $user_remote_token));                                    
                            }
                       
                            // not implemented for remote auth
                        } else {
                            // $remote_setting = SettingModel::getQuick(array(163));
                            // $user_remote_token = AuthRemote::getUserTokenByUid($data['ruid'], $remote_setting);
                            // $rvalid = ($data['remote_token'] && ($data['remote_token'] == $user_remote_token));
                            $rvalid = false;
                        }
                        
                        if($rvalid) {
                            $log->putLogin('Remote user was not changed, proceed to auto authentication');
                        } else {
                            $log->putLogin('Remote user changed, proceed to login form');
                        }       
                    }
                    
                    $user_id = $data['user_id'];
                    $username = addslashes($data['username']);
                    
                    $values = array('id' => $user_id);
                    $logged = ($rvalid) ? $auth->doAuthByValue($values) : false;
                                        
                    if($logged) {
                        $remove_auth = false;
                        $auth->setRememberAuth($user_id, $user_remote_token, $selector);
                        
                        $exitcode = 1; //well
                        $log->putLogin('Login successful');
                        UserActivityLog::add('user', 'login');
                    
                    } else {
                        $exitcode = 2; //failed
                        $log->putLogin('Login failed');
                    }
                       
                } else {
                    
                    $exitcode = 2; //failed
                    $log->putLogin('Unable to get data for automatic authentication');
                    $log->putLogin('Login failed');
                }
            
            } else {
                $exitcode = 3; //error
                $log->putLogin('Auto authentication disabled');
            }
        
            $log->putLogin(sprintf('Exit with the code: %d', $exitcode));
            $log->addLogin($user_id, $username, $auth_type, $exitcode);
            
            // remove auth tokens if auth failed
            if($remove_auth) {
                $auth = (!empty($auth)) ? $auth : new AuthPriv;
                $auth->removeCookie();
                $auth->deleteRememberAuth($selector);
            }
        
        }
    }
    

    /* Authenticate current API user by access key
    Check in temp table if this user authenticated in API
    validate IP and time authentication in temp valid for some time only 
    $minutes_valid time in minutes api session is valid normally should be bigger than in API */
    
    function checkAuthByAccessKey($minutes_valid = 90) {
        
        if(!AuthPriv::getUserId() && $this->view_id != 'login') {
            
            if($public_key = KBClientController::getRequestVar('access_key')) {
                
                $api_dir = APP_CLIENT_DIR . 'client/api_v3/';
                require_once $api_dir . 'KBApiModel.php';
                
                
                $am = new KBApiModel();
                $data = $am->getApiInfoByPublicKey(addslashes($public_key));
                
                $log = new LoggerModel;
                $log->putLogin('Initializing...');
                $log->putLogin('Trting to authenticate user by API access key');
                
                $exitcode = 3; // error
                $user_id = 0;
                $username = '';
                $auth_type = 'api';
                
                if($data) {
                    
                    $user_id = $data['user_id'];
                    $username = addslashes($data['username']);
                    
                    $session = $am->getSession($user_id, $minutes_valid);
                    if($session) {
                        
                        $user_ip = ip2long(WebUtil::getIP());
                        if($session['user_ip'] && $session['user_ip'] == $user_ip) {   
                            
                            $values = array('id' => $user_id);
                            $auth = new AuthPriv; 
                            $logged = $auth->doAuthByValue($values);
                            
                            if($logged) {
                                $exitcode = 1; //well
                                $log->putLogin('Login successful');
                                UserActivityLog::add('user', 'login');
                    
                            } else {
                                $exitcode = 2; //failed
                                $log->putLogin('Login failed');
                            }
                            
                        } else { // <- $user_ip 
                             $log->putLogin('User IP does not match');
                        }
                        
                    } else { // <- $session getSession
                        $log->putLogin('Unable to get api session info');
                    } 
                    
                } else { // <- data getApiInfoByPublicKey
                    $log->putLogin('Unable to get user info');
                }
                
                $log->putLogin(sprintf('Exit with the code: %d', $exitcode));
                $log->addLogin($user_id, $username, $auth_type, $exitcode);
            
                $this->goUrl($this->getLink('all'));
            }
        }
    }
    
    
    function checkPasswordExpiered() {
        
        if (in_array($this->view_id, array('account', 'logout'))) {
            return;
        }
        
        if(!AuthPriv::getUserId()) {
            return;
        }
        
        if(AuthPriv::isAdmin()) {
            return;
        }
        
        if(AuthPriv::getPassExpired()) {
            if($this->getSetting('password_rotation_policy') == 2) {
                $this->go('account', false, false, 'password');
            }
        }
    }
    
    
    function isAutoLoginAllowed() {
        
        $ret = false;
        if($this->getSetting('auth_remember')) {            
            $reg = &Registry::instance();
            $conf = &$reg->getEntry('conf');
            $ret = ($conf['ssl_admin'] && $conf['ssl_client']);
        }
        $ret = true;
        return $ret;
    }
    
    
	static function isAjaxCall() {
		return (!empty($_GET['ajax']));
	}
    
}
?>