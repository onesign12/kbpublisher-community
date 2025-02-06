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

require_once 'config.inc.php';
require_once 'config_more.inc.php';
require_once 'common.inc.php';

session_name($conf['session_name']);
session_start();

// for compability with old version with frames and links in emails
if(isset($_GET['ref'])) {
    $params = WebUtil::unserialize_url($_GET['ref']);
    $page = APP_ADMIN_PATH . 'index.php?r=1&' . $params;
    header('Location:' . $page);
    exit();
}


$controller = new AppController();
$controller->setWorkingDir();
// echo "<pre>"; print_r($controller); echo "</pre>";

// $reg =& Registry::instance();
$reg->setEntry('controller', $controller);

// auto login disabled in admin area 
// if($setting['auth_remember'] && $conf['ssl_admin'] && $conf['ssl_client']) {
//     if(AuthPriv::getCookie()) {
//    
//     }
// }

// auth
$priv = Auth::factory('Priv');
$priv->setCheckIp($conf['auth_check_ip']);
$skip_arr = array('kb_entry', 'feedback', 'news');
$priv->setSkipAuthExpired((in_array($controller->page, $skip_arr) && $_POST));
$priv->setAuthExpired($setting['auth_expired']);

// no priv for user
if(!$priv->getPrivId() && $priv->isAuth()) {
    header('Location:' . APP_ADMIN_PATH . 'client.php');
    exit();
}

// no GET
if(!$_GET) {
    $r = APP_ADMIN_PATH . 'logout.php';
    if($priv->isAuth()) {
        $r = $controller->getGoPageLink('home', 'home');
    }
    
    header('Location:' . $r);
    exit();
}

// not logged
if(!$priv->isAuth()) {
    
    // to return to requested page after login
    if(isset($_GET['r'])) {
        $_SESSION['ref_'] = WebUtil::serialize_url(http_build_query($_GET));
        $link = APP_ADMIN_PATH . 'logout.php';       
    } else { 
        $_SESSION['ref_'] = WebUtil::serialize_url(http_build_query($controller->full_page_params));
        $link = APP_ADMIN_PATH . 'logout.php?Msg=auth_expired';
    }
    
    if(AppAjax::isAjaxRequest()) {
        $str = sprintf('<html>%s</html>', AppAjax::getlogout());
        echo $str;
        ob_end_flush();
        exit;
        
    } elseif($controller->getMoreParam('popup')) {
        $str = '<html><script>window.top.location.reload();window.close();</script></html>';
        echo $str;
        ob_end_flush();
        exit;
        
    } else {
        header('Location:' . $link);
        exit();
    }
}

if(!empty($_SESSION['ref_'])) {
    $_SESSION['ref_'] = '';
}

// if(empty($_SESSION['kbp_lang_'])) {
    $_SESSION['kbp_lang_'] = $setting['lang'];
// }


// settings, rewrite with user values 
$setting = SettingModel::getQuickUser(AuthPriv::getUserId(), array(0,1,2,12,150,141));

$reg->setEntry('setting', $setting);
$reg->setEntry('limit', $setting['num_entries_per_page_admin']);
$button_bottom = ($setting['button_position'] == 2);
// $conf['app_width'] = $setting['app_width'];


$license_msg = array();
// $ls = KBValidateLicense::validate();
// 
// if($ls !== true) {
// 
//     if($ls === 'license_file_not_found') {
//         $license_msg['action_msg'] = AppMsg::licenseBox('license_file_not_found');
//         // $_SESSION['priv_'] = array('license_request');
// 
//         $al = array('home', 'help');
//         if(!in_array($controller->module, $al)) {
//             $controller->goPage('home', 'home');
//         }
//     }
// 
//     $ls_keys = array(
//         'license_keys_not_match', 
//         'license_no_key_period_expire', 
//         'license_trial_period_expired'
//         );
// 
//     if(in_array($ls, $ls_keys)) {
//         $license_msg['action_msg'] = AppMsg::licenseBox($ls);
// 
//         $al = array('help', 'setting');
//         if(!in_array($controller->module, $al)) {
//             $controller->goPage('setting', 'licence_setting');
//         }
// 
//         if($controller->module == 'setting' && $controller->page != 'licence_setting') {
//             $controller->goPage('setting', 'licence_setting');            
//         }
//     }
// 
//     // license note
//     if(is_array($ls)) {
//         if($controller->page == 'home' || $controller->page == 'licence_setting') {
// 
//             if(in_array('license_no_key_period_notice', $ls)) {
//                 $license_msg['action_msg'] = AppMsg::licenseBox('license_no_key_period_notice', $ls);
// 
//             } elseif(in_array('license_trial_period_notice', $ls)) {
//                 $license_msg['action_msg'] = AppMsg::licenseBox('license_trial_period_notice', $ls);
//             }
//         }
//     }
// }

// pass expiered
if(AuthPriv::getPassExpired() && !AuthPriv::isAdmin() && $controller->action != 'password') {
    if($setting['password_rotation_policy'] == 2) {
        $link = $controller->getLink('account', 'account_security', '', 'password');
        $controller->goUrl($link);
    }
}

//priv
if($controller->module || $controller->page || $controller->sub_page) {
// if($controller->module && $controller->page) {
    $priv_area = $priv->getPrivArea($controller->module, $controller->page, $controller->sub_page);
    
    // unable to determine priv area 
    if($priv_area === 'qw1212HGF%nkdf&^$etqweuqbJHG') { // not found
        if(!in_array($controller->module, ['help', 'stuff', 'account']) && 
            strpos($controller->page, 'autosave') === false) {
            
            $controller->goPage('home', 'home');
        }
    }
    
} else {
    exit;
}

$priv->setPrivArea($priv_area);
$reg->setEntry('priv', $priv);
// echo "<pre>Priv Area: "; print_r($priv_area); echo "</pre>";
// echo "<pre>"; print_r($controller->module); echo "</pre>";
// echo "<pre>"; print_r($controller->page); echo "</pre>";
// echo "<pre>"; print_r($controller->sub_page); echo "</pre>";


// top menu
$nav = new AppNavigation;
$nav->setPage(APP_ADMIN_PATH . 'index.php');
$nav->setEqualAttrib('GET', 'module');
$nav->setSubEqualAttrib('GET', 'page');
$nav->setSubEqualAttrib('GET', 'sub_page');
$nav->setTemplate(APP_TMPL_DIR . 'sidebar_top_menu.html');
// $nav->setTemplate(APP_TMPL_DIR . 'sidebar_top_menu_ajax.html'); // ajax sidebar submenu

$top_menu_msg = AppMsg::getMenuMsgs('top');
$new_modules = array();
$nav->setMenuMsg($top_menu_msg, $new_modules);
$nav->setMenuName('topmenu');
$nav->setMenu('all');

// trash menu
if($controller->getMoreParam('popup') || !$priv->isPriv('select', 'trash')) {
    $nav->unsetMenuItem('trash');
}


$use_one_page_tabs = array();
$emodules = AppPlugin::getModules(1);
// echo '<pre>' . print_r($emodules, 1) . '</pre>';

foreach(array_keys($emodules) as $v) {
    if(!AppPlugin::isPlugin($v)) {
        if($related = AppPlugin::isModuleRelated($v)) {
            if(count($related) < 2) {
                $use_one_page_tabs[$v] = 1;
                $menu_link = $controller->getLink($v, $related['epage']);
                $nav->updateMenuItem($v, false, $menu_link);
            }
            
        } else {
            $nav->unsetMenuItem($v);
            if($controller->module == $v) {
                $controller->goPage('home', 'home');
            }
        }
    }
}

// exit;

$topmenu = $nav->generate('topmenu');

// ajax sidebar submenu, not implemented
// $ajax = &AppAjax::factory();
// $xajax = &$ajax->getAjax();
// $xajax->registerFunction(array('showSidebarSubMenu', $nav, 'ajaxGetSidebarSubMenu'));

$topmenu_title = '';
$topmenu_link = '';
if (!empty($top_menu_msg[$controller->module])) {
    $topmenu_title = $top_menu_msg[$controller->module];
    if($page_ = $nav->getDefaultPageByModule($controller->module)) {
        $topmenu_link = $controller->getLink('this', $page_[0], @$page_[1]);
    }
}
// <. top menu

// menu
// $nav = new AppNavigation;
$nav->setEqualAttrib('GET', 'page');
$nav->setSubEqualAttrib('GET', 'sub_page', true);
$nav->setGetParams(sprintf('%s=%s', 'module', $controller->module));
$nav->setTemplate(APP_TMPL_DIR . 'sub_menu_html3.html');
$nav->setTemplateCustomRule(true);

$menu_msg = AppMsg::getMenuMsgs($controller->module);

$new_modules = array();
$nav->setMenuMsg($menu_msg, $new_modules);
$nav->setMenuName('menu');
$nav->setMenu($controller->module);
$nav->setHighlightMenuItem('kb_autosave', 'kb_entry');
$nav->setHighlightMenuItem('news_autosave', 'news_entry');
$nav->setHighlightMenuItem('kb_draft_autosave', 'kb_draft');
$nav->setHighlightMenuItem('file_bulk', 'file_entry');


$hide_tabs = array();
if(BaseModel::isCloud()) {
    $hide_tabs = BaseModel::getCloudHideTabs();
    if(in_array($controller->page, $hide_tabs)) {
        echo $priv->errorMsg();
        exit();
    }
    
    if(!empty($nav->menu_array['menu'])) {
        foreach($hide_tabs as $v) {
            $nav->unsetMenuItem($v);
        }    
    }
}


foreach(array_keys($emodules) as $v) {
    if(!AppPlugin::isPlugin($v)) {
        
        if(!empty($nav->menu_array['menu'])) {
            $nav->unsetMenuItem($v);
            foreach(AppPlugin::getModulesPages($v) as $epage) {
                $nav->unsetMenuItem($epage);

                if($controller->page == $epage) {
                    $controller->goPage('home', 'home');
                }
            }

            if($default = AppPlugin::getModulesPagesDefault($v)) {
                $mi = key($default);
                $menu_link = $controller->getLink($default[$mi][0], $default[$mi][1], @$default[$mi][2]);
                $nav->updateMenuItem($mi, false, $menu_link);
            }
        }
    }
}

// exit;

$menu = '';
if(!empty($nav->menu_array['menu'])) {
    $use_tabs = (!empty($use_one_page_tabs[$controller->module])) ? true : (count($nav->menu_array['menu']) > 1);
    if($use_tabs) {
        $menu = $nav->generate('menu');
        $menu_title = '';
        if (!empty($menu_msg[$controller->page])) {
            $menu_title = $menu_msg[$controller->page];
        }
    }
}

// meta title
if(empty($menu_title) || $controller->page == 'home' || $topmenu_title == $menu_title) {
    $meta_title = $topmenu_title;
} elseif(in_array($controller->module, array('users', 'feedback', 'import', 'tool'))) {
    $meta_title = $menu_title;
} else {
    $meta_title = $topmenu_title . ' | ' . $menu_title;
}

$meta_title = $meta_title . ' - ' . $conf['product_name'];
// <= menu

// submenu
$submenu_display = array(
    'report_stat', 
    'admin_setting', 'public_setting', 'email_setting', 'plugin_setting', 'auth_setting',
    'field_tool', 'trigger', 'automation', 'workflow'
);
    
$submenu = '';
if(in_array($controller->page, $submenu_display)) {
    $nav->setEqualAttrib('GET', 'sub_page');
    $nav->setGetParams(sprintf('%s=%s', 'page', $controller->page));
    $nav->setTemplate(APP_TMPL_DIR . 'sub_menu_html.html');
    // $nav->setTemplate(APP_TMPL_DIR . 'btn_menu.html');
    $nav->setTemplateCustomRule(true);    
    
    $menu_msg = AppMsg::getMenuMsgs($controller->page);
    $new_modules = array();
    $nav->setMenuMsg($menu_msg, $new_modules);
    $nav->setMenuName('sub_menu');
    $nav->setMenu($controller->page);
    $nav->setHighlightMenuItem('email_box', 'am_email');
    
    if(in_array($controller->sub_page, $hide_tabs)) {
        echo $priv->errorMsg();
        exit();
    }
    
    if(!empty($nav->menu_array['menu'])) {
        foreach($hide_tabs as $v) { 
            $nav->unsetMenuItem($v);
        }    
    }
    
    foreach(array_keys($emodules) as $v) {
        if(!AppPlugin::isPlugin($v)) {
            if(!empty($nav->menu_array['menu'])) {
                
                foreach(AppPlugin::getModulesPages($v) as $sub_page) {
                    $nav->unsetMenuItem($sub_page);
                    
                    if($controller->sub_page == $sub_page) {
                        $controller->goPage('home', 'home');
                    }
                }
            }
        }
    }
    
    // exit;
    
    $submenu = $nav->generate('sub_menu') . '<br>';
}

// exit;
// <- sub_menu 


// setting sidebar
if($controller->module == 'setting') {
    $nav->setGetParams(sprintf('%s=%s', 'module', $controller->module));
    $nav->setTemplate(APP_TMPL_DIR . 'sidebar2_search.html');
    
    $options['title'] = $top_menu_msg['setting'];
    $options['equal'] = array('page', 'sub_page');
    $options['menu']  = array('menu', 'sub_menu');
    $view_left = $nav->generateSearchSidebar($nav->getSearchableSetingsMenu(), $options);
}

$reg->setEntry('nav', $nav);


$msg = AppMsg::getMsgs('common_msg.ini', 'knowledgebase', false, false);
$msg = array_merge($msg, AppMsg::getMsg('user_msg.ini', false, false, false));

$pdata = array();
$pdata['username'] = ($runame = AuthPriv::getRemoteUsername()) ? $runame : AuthPriv::getUsername();
$pdata['client_view_link'] = $conf['client_path'];
$pdata['index_link'] = APP_ADMIN_PATH . 'index.php?module=home&page=home';
$pdata['logged_msg'] = $msg['logged_msg'];
$pdata['logout_msg'] = $msg['logout_msg'];
$pdata['public_area_msg'] = $msg['public_area_msg'];
$pdata['admin_area_msg'] = $msg['admin_area_msg'];
$pdata['header_title'] = $setting['header_title'] ?: $conf['product_name'];
// $pdata['title'] = $conf['product_name'];
$pdata['trash_link'] = $controller->getLink('trash','trash');
$pdata['trash_menu'] = $top_menu_msg['trash'];
$pdata['search_menu'] = $msg['search_msg'];
$pdata['add_menu'] = $msg['add_msg'];
$pdata['pvhash'] = $conf['product_hash'];
$pdata['base_href'] = $conf['client_path'];
$pdata['admin_href'] = $conf['admin_path'];
$pdata['account_msg'] = $top_menu_msg['account'];
$pdata['account_link'] = $controller->getLink('account','account_user');

// datepicker
$pdata['datepicker_lang'] = strtolower(str_replace('_', '-', $setting['lang']));
$pdata['suggest_link'] = AppController::getAjaxLinkToFile('suggest_admin');

// generate
$page_controller = $controller->working_dir . 'PageController.php';
if(is_file($page_controller)) {
    require_once $page_controller;
} else {
    $controller->goPage('home', 'home');
}

// show no records msg in list view, rewrite $view
if(empty($_GET['filter']) && empty($_GET['bp'])  
    && !$controller->getMoreParam('popup') 
    && !$controller->getMoreParam('do_search')
    && !$controller->getMoreParam('range_id')
    && !in_array($controller->module, array('account', 'setting'))
    ) {
    
    if(!empty($view) && AppView::isNoRecords($view)) {
        $links = AppView::getNoRecordsLinks($controller, $priv);
        $view = AppView::getNoRecordsBox($links['add'], $links['default']);
    }
    
} elseif(!empty($_GET['filter'])
    && !in_array($controller->module, array('report'))) {
            
    if(!empty($view) && AppView::isNoRecords($view)) {
        $view = AppView::parseGetNoRecordsFilterBox($view);
    }
}


$page = new PageRenderer();
$page->template_dir = APP_TMPL_DIR;

$popup = ($controller->getMoreParam('popup') || $controller->getMoreParam('frame'));
$page->template = ($popup) ? 'page_popup.html' : 'page.html';

// auto auth allowed and set in admin area 
if(!$popup && !AuthPriv::isRemoteAutoAuthArea(2)) {
    $page->needed = array('logout');
}

$page->assign($conf);
$page->assign($conf['lang']);

// notification block
if(!$popup) {
    $ajax = &AppAjax::factory();
    $xajax = &$ajax->getAjax();

    $notification_block = NotificationView_common::getBlock($controller, $xajax);
    $page->assign($notification_block, 'notification_block');
    $page->setNeeded('notification_block');
}

// shortcut menu
if(!$popup) {
    $msg_shortcuts = AppMsg::getMsg('ranges_msg.ini', false, 'shortcuts');
    $menu2 = $nav->getShortcutMenu($controller, $priv, $msg_shortcuts);
    if($menu2 = $nav->getShortcutMenu($controller, $priv, $msg_shortcuts)) {
        $page->assign($menu2, 'shortcut_menu_items');
        $page->setNeeded('shortcut_menu');
    }
}

// search block
$sphinx_enabled = SphinxModel::isSphinxOn();
if($sphinx_enabled && !$popup) {
    $page->setNeeded('search_block');
    $page->setNeeded('search_block2');
    $page->setNeeded('search_block3');
}

// bottom buttons
if($button_bottom) {
    $tpl = new tplTemplatez('css/style_setting.css');
    $tpl->tplParse();
    $page->assign($tpl->tplPrint(1), 'style_setting');
}

// vars we need to parse in view pages
$view_vars = array(
    '{pvhash}' => $pdata['pvhash']
);
$view = strtr($view, $view_vars);

// assign 
$page->assign($view, 'content');
$page->assign($pdata);
$page->assign($topmenu, 'top_menu');
$page->assign($topmenu_title, 'topmenu_title');
$page->assign($topmenu_link, 'topmenu_link');
$page->assign($menu, 'menu');
$page->assign($submenu, 'submenu');
$page->assign($meta_title, 'meta_title');

// sidebar2 defined in PageController
if(!empty($view_left)) {
    $page->assign($view_left, 'module_left_block');
    $page->assign($controller->module, 'module_name');
} else {
    $view_left = false;
}

// sidebar values
$sidebar_vars = $nav->getSidebarVars($controller, $view_left);
$page->assign($sidebar_vars);
if ($sidebar_vars['sidebar_status'] && !$popup) {
    $page->setNeeded('hide_menu_tooltip');
}

// special search highlight
if (defined('IS_SPECIAL_SEARCH') && !defined('IS_SPECIAL_SEARCH_SKIP')) {
    $page->setNeeded('is_special_search');
}

$page->assign(RequestDataUtil::stripVars($msg['sure_leave_msg']), 'sure_leave_msg');
$page->assign(RequestDataUtil::stripVars($msg['ok_msg']), 'ok_msg');
$page->assign(RequestDataUtil::stripVars($msg['cancel_msg']), 'cancel_msg');
$page->assign(RequestDataUtil::stripVars($msg['show_all_msg']), 'show_all_msg');

$debug_enabled = (empty($conf['debug_info'])) ? 0 : 1;
$page->assign($debug_enabled, 'debug');

// $page->assign($msg['title'], 'growl_title');
// $page->assign($msg['body'], 'growl_body');


// msg box on page, hints, etc.
$msg_key = $controller->page;
if($controller->action)                 { $msg_key = $msg_key . '_' . $controller->action; }
if($popup)  { $msg_key = $msg_key . '_popup'; }

$msg2 = AppMsg::hintBox($msg_key, $controller->module);
$page->assign($msg2, 'module_msg');

// info msg set in PageController
if(!empty($module_info_msg)) {
    $page->assign($module_info_msg, 'info_msg');
}

// after action msg if in GET we have "show_msg"
$msg_key = $controller->getRequestVar('show_msg');
if($msg_key) {
    @list($msg_key, $msg_format) = explode('-', $msg_key);
    $msg_format = ($msg_format) ? $msg_format : 'error';
    
    if(isset($_SESSION['msg_'][$msg_key])) {
        $msg_box = BoxMsg::factory($msg_format, $_SESSION['msg_'][$msg_key]);
        unset($_SESSION['msg_'][$msg_key]);
    } else {
        $msg_vars = ($controller->getMoreParam('vars')) ?: [];
        $msg_box = AppMsg::afterActionBox($msg_key, $msg_format, false, $msg_vars);
    }
    
    $page->assign($msg_box, 'action_msg');
}

// msg in growl, replaced for good_action.php
// $msg_key = $controller->getRequestVar('msg');
@$msg_key = $_SESSION['success_msg_'];
if($msg_key) {
    @list($msg_key, $msg_format) = explode('-', $msg_key);
    $msg = AppMsg::getMsg('after_action_msg.ini', false, $msg_key);    
    $page->assign(@$msg['title'], 'growl_title');
    $page->assign($msg['body'], 'growl_body');
    $page->assign(1, 'growl_show');
    
    if($msg_format == 'error') {
        $page->assign(1, 'growl_fxed');
        $page->assign('error', 'growl_style');
    }
    
    $_SESSION['success_msg_'] = false;
}

$page->assign($license_msg);
$page->assign($controller->module, 'module_key');

$ajax2 = AppAjax::processRequests();
$page->assign($ajax2, 'xajax_js');

$debug = DebugUtil::getDebugInfo();
$debug = sprintf('<div style="padding: 20px;">%s</div>', $debug);
$page->assign($debug, 'kbp_debug_info');

$page->display();


ob_end_flush();
?>