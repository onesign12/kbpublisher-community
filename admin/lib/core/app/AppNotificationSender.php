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

class AppNotificationSender
{
    
    var $letter_key;
    var $settings = array();
    
    
    function __construct($settings = array()) {

        $reg =& Registry::instance();
        $conf =& $reg->getEntry('conf');

        $this->model = new AppMailModel();
        $this->settings = array_merge($this->settings, $this->model->getSettings(), $settings);
        $this->settings['charset'] = $conf['lang']['meta_charset'];

        $this->parser = new AppMailParser();
        $this->parser->setSettingVars($this->settings);

        $this->n_manager = new NotificationModel;
    }
    
    
    // null if no one to send 
    function _send($user_ids, $vars, $type = 'info') {
        
        $parser = &$this->parser;
        $parser->assign($vars);
        $parser->assign('message', $parser->parse($parser->parse($vars['ntf_message'])));
        
        $template_msg = $parser->getTemplateMsg($this->letter_key);
        $template = $parser->getTemplate('notification', $template_msg);
        
        $user_ids = array_unique($user_ids);
        $subject = $parser->parse($template_msg['ntf_subject']);
        $message = $parser->parse($template);
       
        $title = '';
        if($template_msg['ntf_title']) {
            $title = $parser->parse($template_msg['ntf_title']);
        }
        
        // $sent = $this->sendToWebSocket($type,
        //     $subject,
        //     $message,
        //     $user_ids
        // );
        
        // echo '<pre>', print_r($vars,1), '<pre>';
        // echo '<pre>$subject: ', print_r($subject,1), '<pre>';
        // echo '<pre>$title: ', print_r($title,1), '<pre>';
        // echo '<pre>$message: ', print_r($message,1), '<pre>';
        // exit;
        
        $sent = $this->n_manager->addNotification($subject, $title, $message, $user_ids, $type, $this->letter_key);
        return $sent;
    }
    
    
    static function send($options, $vars) {
        
        $n = new AppNotificationSender;
        $n->letter_key = $options['letter_key'];
        
        $sent = true;
        $users = $n->getUsersToSend($options['ntf_users'], $options['ntf_key']);
        
        if($users) {
            $type = isset($options['ntf_type']) ? $options['ntf_type'] : 'info';
            $vars['ntf_message'] = $options['ntf_message'];
            $sent = $n->_send($users, $vars, $type);
        }
        
        return $sent;
    }    
    
    
    function sendToWebSocket($type, $subject, $message, $user_ids) {
        
        $reg =& Registry::instance();
        $conf =& $reg->getEntry('conf');
        
        $sent = false;
        
        if (!empty($conf['web_socket_port'])) { // websocket server
        
            $msg = array(
                'type' => $type,
                'title' => $subject,
                'message' => $message,
                'user_ids' => $user_ids
            );
            
            $msg = json_encode($msg);
            
            $protocol = ($conf['ssl_admin']) ? 'wss' : 'ws';
            $url = sprintf('%s://%s:%s', $protocol, 'localtest.me', $conf['web_socket_port']);
            
            \Ratchet\Client\connect($url)->then(function($conn) use ($msg) {
                $conn->send($msg);
                $conn->close();
                
                $sent = true;
                
            }, function ($e) {
                echo "Could not connect: {$e->getMessage()}\n";
            });
        }
        
        return $sent;
    }
    
    
    function shouldUserBeNotified($user_id, $group_key) {
        if($group_key == 'cron') { 
            $setting = 3; // alwais add cron messages to notifications
        } else {
            $setting = AppMailSender::getNotificationSetting($user_id, $group_key);
        }
        
        return in_array($setting, array(2,3));
    }
    
    
    function getUsersToSend($user_ids, $key) {
        
        $users = array();
        $user_ids = (is_array($user_ids)) ? $user_ids : array($user_ids);        
        
        foreach ($user_ids as $user_id) {
            if ($this->shouldUserBeNotified($user_id, $key)) {
                $users[] = $user_id;
            }
        }
        
        return $users;
    }
    
    
    // SENDERS // ------------------------
    
    
}

?>