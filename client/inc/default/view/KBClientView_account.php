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

// Account module code located in admin/modules/account
// but displayed in public area parameter View=account 
// added to admin url, it uses this page to dispaly admin content 
// example: http://domain.com/kb/account/?page=account_user
// popups using $view->page_popup = true;


class KBClientView_account extends KBClientView_common
{
    
    function &execute(&$manager_client) {
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['my_account_msg'];
        $this->nav_title = $this->msg['my_account_msg'];
        
        $this->setMenuItems();
        $page = false;
        if(isset($_GET['page']) && isset($this->menu_items[$_GET['page']])) {
            $page = $_GET['page'];
        }
        
        if($page) {
            $data = $this->getAdminContent();
        } else {
            $data = $this->getIndexPage($manager_client);
        }
        
        $data = $this->getContent($manager_client, $data, $page);

        return $data;        
    }
    
    
    function getIndexPage($manager) {
        
        $tpl = new tplTemplatez($this->getTemplate('account_home.html'));
        $tpl->tplAssign('account_menu', $this->getMenu($manager));
        
        foreach ($this->menu_items as $k => $v) {
            if($k == 'account') {
                continue;
            }

            $more = ['page' => $k];
            $v['link'] = $this->controller->getLink('account', false, false, false, $more);
            
            $tpl->tplParse($v, 'row');
        }        

        $tpl->tplParse();
        return $tpl->tplPrint(1);    
    }


    function getAdminContent() {
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        
        $priv = Auth::factory('Priv');
        $reg->setEntry('priv', $priv);
        
        $_GET['View'] = 'account';
        
        $controller = new AppController();
        $controller->module = 'account';
        $controller->setMoreParams('View');
        $controller->setWorkingDir();

        // $reg =& Registry::instance();
        $reg->setEntry('acontroller', $controller);
        // settings, rewrite with user values 
        $setting = SettingModel::getQuickUser(AuthPriv::getUserId(), array(0,1,2,12,150,141));

        $reg->setEntry('setting', $setting);
        $reg->setEntry('limit', $setting['num_entries_per_page_admin']);
        
        // generate
        $page_controller = $controller->working_dir . 'PageController.php';
        if(is_file($page_controller)) {
            require_once $page_controller;
        } else {
            $this->controller->go('account');
        }
        
        
        // vars we need to parse in view pages
        $view_vars = array(
            '{pvhash}' => $conf['product_hash'],
            // '{admin_href}' => APP_ADMIN_PATH
        );
        
        $view = strtr($view, $view_vars);

    
        return $view;
    }
    
    
    function getContent($manager, $content, $page) {
        $tpl = new tplTemplatez($this->getTemplate('account_tmpl.html'));
        
        if(!$this->page_popup) {
            $tpl->tplAssign('account_menu', $this->getMenu($manager));
        }
        
        $tpl->tplAssign('content_tmpl', $content);

        // $title = (isset($this->menu_items[$page])) ? $this->menu_items[$page]['title'] : '';
        // $tpl->tplAssign('account_title', $title);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getMenu($manager) {
    
        $nav = new Navigation;
        $nav->setEqualAttrib('GET', 'page');
        $nav->setTemplate($this->getTemplate('account_menu_tmpl.html'));
        $nav->setDefault('account');

        if(!AppPlugin::isPlugin('mustread')) {
            unset($this->menu_items['account_mustread']);
        }

        if(!$manager->isSubscribtionAllowed('entry') && !$manager->isSubscribtionAllowed('news')) {
            unset($this->menu_items['account_subsc']);
        }

        $order = 0;
        foreach ($this->menu_items as $k => $v) {
            
            $str = '%s (%s)';
            if($k == 'account_notification') {
                $m = new NotificationModel();
                if($num = $m->getUserNotificationsCount()) {
                    $v['title'] = sprintf($str, $v['title'], $num);
                }
            
            } elseif ($k == 'account_mustread') {
                $m = new MustreadModel();
                if($num = $m->getUserMustreadsCount(AuthPriv::getUserId())) {
                    $v['title'] = sprintf($str, $v['title'], $num);
                }
            }
            
            $more = ['page' => $k];
            $link = $this->controller->getLink('account', false, false, false, $more);
            $nav->setMenuItem($v['title'], $link);
            $nav->auxilary[$nav->menu_name][$order++] = $k;
        }
        
        return $nav->generate();    
    }
    
    
    function setMenuItems() {
        $this->menu_items = array(
            'account' => array(
                'title' => $this->msg['member_home_msg']),  
                
            'account_user' => array(
                'title' => $this->msg['member_account_msg'], 
                'desc'  => $this->msg['member_account_desc_msg']),
            
            'account_security' => array(
                'title' => $this->msg['member_security_msg'],
                'desc'  => $this->msg['member_security_desc_msg']),            
            
            'account_message' => array(
                'title' => $this->msg['member_notification_msg'],
                'desc'  => $this->msg['member_notification_msg_desc']),
                
            'account_list' => array(
                'title' => $this->msg['member_list_msg'],
                'desc'  => $this->msg['member_list_desc_msg']),  
                              
            'account_mustread' => array(
                'title' => $this->msg['member_mustread_msg'],
                'desc'  => $this->msg['member_mustread_desc_msg']),
            
            'account_subsc' => array(
                'title' => $this->msg['member_subsc_msg'],
                'desc'  => $this->msg['member_subsc_desc_msg']),
                
            // 'account_setting' => array(
            //     'title' => $this->msg['member_subsc_msg'],
            //     'desc'  => $this->msg['member_subsc_desc_msg'])
            );
    }

}
?>