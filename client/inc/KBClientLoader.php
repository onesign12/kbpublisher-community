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

class KBClientLoader
{
    
    static function &getManager(&$setting, $controller, $force_view = false, $user = array()) {
        
        require_once $controller->working_dir . 'KBClientModel_common.php';
        
        $file_views = array('files', 'file', 'download');
        $news_views = array('news', 'print-news');
        
        
        $view_id = $controller->view_id;
        if($force_view !== false) {
            $view_id = $force_view;
        }
        
        // files
        if(in_array($view_id, $file_views)) {
                            
            require_once  $controller->common_dir . 'KBClientFileModel.php';
            
            $setting = array_merge($setting, KBClientModel::getSettings(200));
            $manager = new KBClientFileModel;
        
        // news
        } elseif(in_array($view_id, $news_views)) {
            
            require_once  $controller->common_dir . 'KBClientNewsModel.php';
            
            $manager = new KBClientNewsModel;
             
        // articles
        } else {
        
            $manager = new KBClientModel_common;
        }
        
        $manager->setting = &$setting;
        $manager->setCustomSettings($controller);
        
        // sort order, removed from settings
        $manager->setting['category_sort_order'] = 'sort_order';

        if(isset($user['user_id'])) {
            $manager->is_registered = (!empty($user['user_id'])) ? true : false;
        } else {
            $manager->is_registered = AuthPriv::isAuthSession($setting['auth_check_ip']);
        }
        
        $manager->user_id =      (isset($user['user_id'])) ? $user['user_id'] : AuthPriv::getUserId();
        $manager->user_priv_id = (isset($user['priv_id'])) ? $user['priv_id'] : AuthPriv::getPrivId();
        $manager->user_role_id = (isset($user['role_id'])) ? $user['role_id'] : AuthPriv::getRoleId();        
        
        $manager->setCategories();
        $manager->setEntryRolesSql();
        $manager->setEntryPublishedStatus();
        
        //echo ($manager->is_registered) ? 'Registered' : 'Not Registered';
        //echo "<pre>"; print_r($manager->setting); echo "</pre>";
        // echo "<pre>"; print_r($manager->categories); echo "</pre>";
        // echo "<pre>"; print_r($manager); echo "</pre>";
        //exit;
                
        return $manager;
    }
    
    
    static function &getView(&$controller, &$manager) {
                
        require_once $controller->working_dir . 'KBClientView_common.php';
        require_once $controller->working_dir . 'KBClientAction_common.php';
        
        // $view_id to action
        // to KBClientAction_default
        $default_actions = array(
            'files', 'map', 'pool', 'tags', '404'
        );
        
        // to KBClientAction_{$view_id}
        $actions = array(
            'index', 'entry', 'glossary', 'comment', 
            'contact', 'send', 'search', 'rate',
            'register', 'confirm', 'login', 'logout', 'sso', 'mfa',
            'password', 'password_rotation',
            'account',
            'success_go', 'afile', 'news', 'rssfeed',
            'file', 'download', 'entry_add',
            'subscribe', 'unsubscribe', 'pdf',
            'search_category'
            );
        
        $view_id = $controller->view_id;
        $action = (in_array($view_id, $actions)) ?  $view_id : false;
        
        if(strpos($view_id, 'print') !== false) {
            $action = 'print';
        
        } elseif(strpos($view_id, 'pdf') !== false) {
            $action = 'pdf';
        
        } elseif(in_array($view_id, array('recent', 'popular', 'featured'))
              || in_array($controller->page_id, array('recent', 'popular'))) { // in files
            $action = 'dynamic';
        }
        
        if(!$action) {
            $action = (in_array($view_id, $default_actions)) ? 'default' : false;
            if(!$action) {
                $controller->goStatusHeader('404');
            }
        }
        
        $action_class = 'KBClientAction_' . $action;
        $action_file  = 'KBClientAction_' . $action . '.php';
        
        $file = $controller->working_dir . 'action/' . $action_file;
        if(!file_exists($file)) {
            $file = $controller->default_working_dir . 'action/' . $action_file;
        }
        
        $action = new $action_class;
        $action->setVars($controller);
        $action->setCategoryId($controller, $manager);
        $action->checkPrivate($controller, $manager);
        return $action->execute($controller, $manager);
    }
        
    
    function _getViewCommon() {
        
    }
    
    
    function _getViewFile() {
        
    }
}
?>