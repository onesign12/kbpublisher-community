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


class NotificationView_common
{
    
    static $client = null;
    static $controller = null;
    static $list_limit = 20;
    static $max_badge_num = 99;
    

    static function getBlock($controller, $xajax) {
        
        self::$controller = $controller;
        self::$client = isset($controller->entry_id);
        
        $manager = new NotificationModel;
        
        $tpl = new tplTemplatez(APP_MODULE_DIR . 'account/notification/template/notification_block.html');
        
        $msg = AppMsg::getMsg('user_msg.ini');
        $tpl->tplAssign($msg);
    
        $msg_num = $manager->getUserNotificationsCount();
        $msg_num_after_view = $msg_num - 1;
        $msg_num = ($msg_num > self::$max_badge_num) ? self::$max_badge_num : $msg_num;
        $tpl->tplAssign('msg_num', $msg_num);
        
        $msg_num_after_view = ($msg_num_after_view > self::$max_badge_num) ? self::$max_badge_num : $msg_num_after_view;
        $tpl->tplAssign('msg_num_after_view', $msg_num_after_view);
        
        $empty_display = ($msg_num) ? 'none' : 'block';
        $tpl->tplAssign('empty_display', $empty_display);
        
        // all link
        if(self::$client) {
            $more = ['page' => 'account_message'];
            $link = $controller->getLink('account', false, false, false, $more);
        } else {
            $link = $controller->getLink('account', 'account_message');
        }
        
        $tpl->tplAssign('notification_link', $link);
                
        // notification xajax
        $xajax->registerFunction(array('loadNotificationList', 'NotificationView_common', 'ajaxLoadNotificationList'));
        $xajax->registerFunction(array('dismissNotification', 'NotificationView_common', 'ajaxDismissNotification'));
    
        // websockets
        $reg =& Registry::instance();
        $conf =& $reg->getEntry('conf');
        if (!empty($conf['web_socket_port'])) {
            $tpl->tplSetNeeded('/websocket');
            $websocket_url = sprintf('%s:%s%s', $_SERVER['HTTP_HOST'], $conf['web_socket_port'], $conf['client_home_dir']);
            $tpl->tplAssign('websocket_url', $websocket_url);
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);

    }


    static function ajaxLoadNotificationList() {

        // logout in client view
        if(self::$client && !AuthPriv::getUserId()) {
            $str = sprintf('<html>%s</html>', KBClientAjax::getlogout());
            echo $str;
            ob_end_flush();
            exit;
        }
        
        $objResponse = new xajaxResponse();

        $common_msg = AppMsg::getMsg('common_msg.ini');
        
        $reg =& Registry::instance();
        $conf = &$reg->getEntry('conf');
                
        $manager = new NotificationModel;
        $notifications = $manager->getUserNotifications(self::$list_limit);
        
        // notifications
        if (!empty($notifications)) {
            $tpl = new tplTemplatez(APP_MODULE_DIR . 'account/notification/template/notification_block.html');
            
            $icons = array(
                1 => 'info_light',
                2 => 'error_red',
                3 => 'attention'
            );
            
            foreach ($notifications as $id => $v) {
                $v['id'] = $id;
                
                $method = (self::$client) ? 'getAdminRefLink' : 'getLink';
                $change_num = 1;
                $more = array('id' => $id);
                $v['view_link'] = self::$controller->$method('account', 'account_message', false, 'detail', $more);
                // $v['view_link'] = KBClientController::getAdminRefLink('account', 'account_message', false, 'detail', $more);
                
                $v['change_num'] = $change_num;
                $v['icon'] = $icons[$v['notification_type']];
                $v['date'] = date('M d', strtotime($v['date_posted']));
                $v['title'] = nl2br($v['title']);
                
                $v['view_msg'] = $common_msg['view_msg'];
                $v['dismiss_msg'] = $common_msg['dismiss_msg'];
                $v['base_href'] = $conf['client_path'];
                
                $tpl->tplParse($v, 'row');
            }
            
            $html = $tpl->parsed['row'];
            
            $objResponse->call('NotificationManager.updateList', $html);
        }
    
        return $objResponse; 
    }
    

    static function ajaxDismissNotification($id) {
        
        $objResponse = new xajaxResponse();
        
        $manager = new NotificationModel;
        $manager->status(0, $id);
        
        $msg_num = $manager->getUserNotificationsCount();
        $msg_num = ($msg_num > self::$max_badge_num) ? self::$max_badge_num : $msg_num;
        
        $objResponse->call('NotificationManager.remove', $id, $msg_num);
        
        return $objResponse; 
    }

}
?>