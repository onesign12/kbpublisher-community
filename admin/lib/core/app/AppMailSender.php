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


if(!defined('APP_ADMIN_DIR')) {
    exit;
}

class AppMailSender
{
    
    var $mail;
    var $noreply_email;
    var $from_mailer;
    var $is_html;
    var $alt_body_msg = 'Your email client does not support HTML email.';
    var $letter_key;
    
    // added March 11, 2020 to keep all users we calculated in mail obj
    var $notification_users = array();
    
    var $settings = array(
        'mailer'        => 'mail',
        'content_type'  => 'text/plain',
        'charset'       => 'iso-8859-1',

        'from_email'    => '',
        'from_name'     => '',
        'reply_to'      => '',
        'from_mailer'   => '',
        'sender'        => 'KBMailer',

        'sendmail_path' => '/usr/sbin/sendmail',
        'smtp_host'     => 'localhost',
        'smtp_port'     => '25',
        'smtp_auth'     => false,
        'smtp_user'     => '',
        'smtp_pass'     => '',
        'smtp_secure'   => '',
        'smtp_keep_alive'=> false,
        'smtp_debug'    => false
        );

    var $letter_type = array(
        1 => 'scheduled_article',
        2 => 'scheduled_file',
        3 => 'scheduled_news',
        
        5 => 'mustread',
        // 6 => 'scheduled_file',
        // 7 => 'scheduled_news',
        
        10 => 'subscription_entry',    // article+file updates are sent in single letter
        13 => 'subscription_news',
        14 => 'subscription_comment',
        20 => 'automation',
        21 => 'workflow',
        // 25 => 'rating_comment_added',
        30 => 'user'
        );
    
    
    var $protected_get_to_func = array(
        'to' => 'getToAddresses',
        'cc' => 'getCcAddresses',
        'bcc' => 'getBccAddresses',
        'ReplyTo' => 'getReplyToAddresses'
    );
      
    var $protected_add_to_func = array(
        'to' => 'AddAddress',
        'cc' => 'AddCC',
        'bcc' => 'AddBCC',
        'ReplyTo' => 'addReplyTo'
    );
        
    
    function __construct($settings = array()) {
        
        $reg =& Registry::instance();
        $conf =& $reg->getEntry('conf');
        
        $this->model = new AppMailModel();
        $this->settings = array_merge($this->settings, $this->model->getSettings(), $settings);        
        $this->settings['charset'] = $conf['lang']['meta_charset'];
                
        if(empty($this->settings['noreply_email'])) {
            $this->settings['noreply_email'] = 'noreply@'.preg_replace(array('#^www\.#', '#:\d+#'), '', $_SERVER['HTTP_HOST']);        
        }
        
        $this->parser = new AppMailParser();
        $this->parser->setSettingVars($this->settings);
        
        //if(AuthPriv::getUserId()) {
        //    $this->parser->assign($this->model->getAdminData(AuthPriv::getUserId()));
        //}
        
        $this->noreply_email = $this->settings['noreply_email'];
        $this->from_mailer = $this->settings['from_mailer'];
        
        //echo "<pre>"; print_r($this->settings); echo "</pre>";
        //exit;
    }
    
    
    static function & instance() {
        static $sender;
        if (!$sender) { 
            $class_name = __CLASS__;
            $sender = new $class_name;
        }
        
        return $sender;
    }
    
    
    function addNotificationUser($user_id) {
        if(is_array($user_id)) {
            $this->notification_users += $user_id;
        } else {
            $this->notification_users[] = $user_id;
        }
    }
    
    
    function getNotificationUsers() {
        return $this->notification_users;
    }
    
    
    function addNotificationUserByEmail($to_email) {
        if($to_email) {
            $to_email = explode(',', $to_email);
            foreach($to_email as $email) {
                $to_email = ($email == '[support_email]') ? $this->parser->getValue('support_email') : $email;
                if($user_id = $this->model->getUserIdByEmail($email)) {
                    $this->addNotificationUser($user_id);
                }
            }
        }
    }
    
    
    function getTemplate() {
        
        $template = $this->model->getTemplate($this->letter_key);
    
        if(!$template) {
            $template = $this->parser->getTemplate($this->letter_key);
        }
    
        if($this->is_html) {
            $vars['charset'] = $this->settings['charset'];
            $template = $this->parser->parseHtmlTemplate($template, $vars);
        }
    
        return $template;
    }
    
    
    function getTemplateVars() {
        
        $data = $this->model->getTemplateVars($this->letter_key);
        
        // reading from msg
        if(empty($data['subject'])) {
            $data1 = $this->parser->getTemplateMsg($this->letter_key);
            $data['subject'] = (!empty($data1['subject'])) ? $data1['subject'] : '';
        }        
        
        return $data;
    }    
    
    
    // April 1, 2021, added static to not call for every email 
    // not tested 
    // function getTemplateVars2() {
    //
    //     static $data;
    //     $lk = $this->letter_key;
    //     if(isset($data[$lk])) {
    //         return $data[$lk];
    //     }
    //
    //     $data[$lk] = $this->model->getTemplateVars($lk);
    //
    //     // reading from msg
    //     if(empty($data[$lk]['subject'])) {
    //         $data1 = $this->parser->getTemplateMsg($lk);
    //         $data[$lk]['subject'] = (!empty($data1['subject'])) ? $data1['subject'] : '';
    //     }
    //
    //     return $data[$lk];
    // }
    
    
    function &getMailerObj($template_vars, $values = array()) {
        
        $values = array_merge($this->settings, $values);
        // echo "<pre>"; print_r($values); echo "</pre>";
        
        // $mail = new PHPMailer();
        $mail = new PHPMailer\PHPMailer\PHPMailer();
        $mail->SetLanguage("en", APP_LIB_DIR . 'phpmailer/language/');
        $mail->SMTPKeepAlive = $values['smtp_keep_alive'];
        $mail->SMTPDebug = $values['smtp_debug'];
        //$mail->Timeout = 10;
        
        $mail->CharSet = $values['charset'];
        $mail->ContentType = $values['content_type'];
        $mail->Mailer = $values['mailer'];
        $mail->AddCustomHeader('X-Mailer:' . $values['from_mailer']);
        
/*
        // Sets the Sender email (Return-Path) of the message. 
        // If not empty, will be sent via -f to sendmail or as 'MAIL FROM' in smtp mode.
        // in some hosting when it is enabled email was not sent ... ?
        // or it will require some time to validate sender
        $sender = $values['from_email'];
        if(strpos($sender, ',') !== false) {
            $sender = explode(',', $sender);
            $sender = trim($sender[0]);
        }
        
        $mail->Sender = escapeshellcmd($sender);
*/        
        
        $mail->Sendmail = $values['sendmail_path'];
        $mail->Host = $values['smtp_host'];
        $mail->Port = $values['smtp_port'];
        $mail->SMTPAuth = ($values['smtp_auth']); //($values['mailer'] == 'smtp');
        $mail->Username = $values['smtp_user'];    
        $mail->Password = EncryptedPassword::decode($values['smtp_pass']);
        
        if(!empty($values['smtp_secure']) && $values['smtp_secure'] != 'none') {
            $mail->SMTPSecure  = $values['smtp_secure'];
        }
        
        //Provides the ability to have the TO field process individual
        //emails, instead of sending to entire TO addresses
        //$mail->SingleTo = false; // need to test        
        
        //default values
        $mail->From = $values['from_email'];
        $mail->FromName = $values['from_name'];
        
        // if set empty reply to in client - email will be empty
        //$mail->AddReplyTo($values['reply_to']);
        
                
        //echo "<pre>"; print_r($mail); echo "</pre>";
        //echo "<pre>"; print_r($values); echo "</pre>";
        //echo "<pre>"; print_r($this->parser); echo "</pre>";
        // echo "<pre>"; print_r($template_vars); echo "</pre>";
        //exit;
        
        // what defined for each letter template
        if($template_vars) {
            $this->parseTemplateValues($mail, $template_vars);
        }
        
        //echo "<pre>"; print_r($mail); echo "</pre>";
        //exit;
        
        return $mail;
    }

    // defined for every email template
    function parseTemplateValues(&$mail, $vars, $empty_to = false) {
            
        $values = &$vars;
        foreach($values as $k => $v) {
            $values[$k] = $this->parser->parse($v);
        }
    
        $this->is_html = (!empty($values['is_html']));
        $mail->IsHTML($this->is_html);
    
        // defined in php mailer 
        //if($this->is_html) {
        //    $mail->AltBody = $this->alt_body_msg;
        //}
    
        $mail->Subject = $values['subject'];            
    
        if(!empty($values['from_email'])) {  
            $from = explode(',', $values['from_email']);
            foreach($from as $k => $email) {
                if($k === 0) {
                    $from_name = (!empty($values['from_name'])) ? $values['from_name'] : '';
                    $mail->setFrom($email, $from_name, true);
                } else {
                    $this->addEmail($mail, $email, false, 'AddCC');
                }
            }
        }
    
        $this->parseTemplateValuesTo($mail, $values);
    }


    function parseTemplateValuesTo($mail, $vars, $parsed = true) {
        
        $values = &$vars;
        if(!$parsed) {
            foreach($values as $k => $v) {
                $values[$k] = $this->parser->parse($v);
            }
        }
        
        if(!empty($values['to_email'])) {
            $to_name = (isset($values['to_name'])) ? $values['to_name'] : false;
            $this->addEmail($mail, $values['to_email'], $to_name, 'AddAddress');
        }

        if(!empty($values['to_cc_email'])) {
            $to_cc_name = (isset($values['to_cc_name'])) ? $values['to_cc_name'] : false;
            $this->addEmail($mail, $values['to_cc_email'], $to_cc_name, 'AddCC');
        }            

        if(!empty($values['to_bcc_email'])) {
            $to_bcc_name = (isset($values['to_bcc_name'])) ? $values['to_bcc_name'] : false;
            $this->addEmail($mail, $values['to_bcc_email'], $to_bcc_name, 'AddBCC');
        }
        
        // if(!empty($values['reply_to'])) {
            // $this->AddReplyTo($values['reply_to']);
        // }
    }


    function addEmail(&$obj, $emails, $names = false, $func = 'AddAddress') {
        
        $emails = explode(',', $emails);
        $names = explode(',', $names);
        
        foreach($emails as $k => $email) {
            
            $email = trim($email);
            if(empty($email)) {
                continue;
            }
            
            $name = false;
            if(isset($names[$k])) {
                $name = $names[$k];            
            } elseif($names[0]) {
                $name = $names[0];
            }
            
            //call_user_func_array(array($obj, $func), array($email, $name));
            $obj->$func($email, $name);
        }        
    } 


    function populateMailFromArray($obj, $message) {
                
        foreach (array_keys($message) as $k) {
            if(isset($this->protected_add_to_func[$k])) {
                $func = $this->protected_add_to_func[$k];
                                
                foreach($message[$k] as $to) {
                    $email = trim($to[0]);
                    $name = (!empty($to[1])) ? trim($to[1]) : '';
                    
                    $obj->$func($email, $name);
                }                
                
            } else {
                $obj->$k = $message[$k];
            }
        }
    }


/*
    [to] => Array
            (
                [0] => Array
                    (
                        [0] => eleontev@kbpublisher.com
                        [1] => 
                    )
    
*/

    // remove emails from $mail obj
    function removeEmail(&$obj, $emails, $from = array('to', 'cc', 'bcc')) {

        if(!$emails) {
            return;
        }

        $recipients = array();
        
        // get all recipients
        foreach($from as $kind) {
            $func = $this->protected_get_to_func[$kind];
            $recipients[$kind] = $obj->$func();
        }
        
        // remove recipients
        foreach($from as $kind) {
            foreach($recipients[$kind] as $k => $v) {                
                if(in_array($v[0], $emails)) {
                    unset($recipients[$kind][$k]);
                }
            }
        }
        
        //populate recipients
        $obj->clearAllRecipients();
        foreach($from as $kind) {
            foreach($recipients[$kind] as $k => $v) {
                $func = $this->protected_add_to_func[$kind];
                $obj->$func($v[0], $v[1]);
            }
        }
        
        //populate recipients
        $obj->clearAllRecipients();
        foreach($from as $kind) {
            foreach($recipients[$kind] as $k => $v) {
                $func = $this->protected_add_to_func[$kind];
                $obj->$func($v[0], $v[1]);
            }
        }
    }
    
    
    // if some string in some to fields, in template vars
    function isExistTo($tvars, $string, $from = array('to', 'cc', 'bcc')) {
        foreach($from as $from_) {
            $to = ($from_ == 'to') ? $from_ . '_email' : 'to_' . $from_ . '_email';
            if(strpos($tvars[$to], $string) !== false) {
                return true;
            }
        }
    
        return false;
    }


    static function getNotificationSetting($user_id, $group_key) {
        $setting_key = 'notification_' . $group_key;
        $options = array('user_module_id' => 1);
        return SettingModel::getQuickUser($user_id, 0, $setting_key, false, $options);
    }



    function shouldUserBeEmailed($user_id, $group_key) {
        $setting = self::getNotificationSetting($user_id, $group_key);
        return in_array($setting, array(1,3));
    }


    // EMAILS // -------------------------------
    
    function sendGeneratedPassword($user, $password, $link) {
        
        $this->letter_key = 'generated_password';

        // parser
        $parser = &$this->parser;
        $parser->assignUser($user);
        $parser->assign('password', $password);
        $parser->assign('link', $link);
        
        // mail object
        $mail = &$this->getMailerObj($this->getTemplateVars());
        $mail->Body = $parser->parse($this->getTemplate());
        
        //echo "<pre>"; print_r($mail); echo "</pre>"; 
        //exit();
        
        return $mail->Send();
    }
    
    
    function sendResetPasswordLink($user, $code, $link) {
        
        $this->letter_key = 'reset_password';

        // parser
        $parser = &$this->parser;
        $parser->assignUser($user);
        $parser->assign('link', $link);
        $parser->assign('code', $code);
        
        // mail object
        $mail = &$this->getMailerObj($this->getTemplateVars());
        $mail->Body = $parser->parse($this->getTemplate());
        
        //echo "<pre>"; print_r($mail); echo "</pre>";
        //exit();
        
        return $mail->Send();
    }


    // not in use 2018-11-13
    function sendSetPasswordLink($user, $code, $link) {
        
        $this->letter_key = 'set_password';

        // parser
        $parser = &$this->parser;
        $parser->assignUser($user);
        $parser->assign('link', $link);
        $parser->assign('code', $code);
        
        // mail object
        $mail = &$this->getMailerObj($this->getTemplateVars());
        $mail->Body = $parser->parse($this->getTemplate());
        
        //echo "<pre>"; print_r($mail); echo "</pre>";
        //exit();
        
        return $mail->Send();
    }
    
    
    function sendContactAnswer($vars, $file) {
        
        $this->letter_key = 'answer_to_user';

        // parser
        $parser = &$this->parser;
        $parser->assign($vars);
        
        // mail object
        $mail = &$this->getMailerObj($this->getTemplateVars());
        $mail->Body = &$vars['answer'];
        
        if(!empty($file)) {
            $files = explode(';', $file);
            
            foreach ($files as $v) {
                $data = FileUtil::read($v);
                $name = basename($v);
                
                $mail->AddStringAttachment($data, $name);
            }
        }
        
        // echo "<pre>"; print_r($mail); echo "</pre>";
        // exit();
        
        return $mail->Send();
    }
    
    
    /* USER */
    
    function _getUserMailObj($vars, $letter_key, $func = []) {
        
        $this->letter_key = $letter_key;

        // parser
        $parser = &$this->parser;
        $parser->assign($vars);
        $parser->assignUser($vars);
        
        foreach($func as $v) {
            call_user_func_array(array($parser, $v[0]), $v[1]);
        }
        
        // mail object
        $mail = &$this->getMailerObj($this->getTemplateVars());
        $mail->Body = $parser->parse($this->getTemplate());
        
        // echo "<pre>"; print_r($mail); echo "</pre>";
        // exit();
        
        return $mail;
    }

    
    function sendUserApproved($vars) {
    
        $letter_key = 'user_approved';
        $func = [];
        if(empty($vars['password'])) {
            $msg = $this->parser->getTemplateMsg($letter_key);
            $func = array(
                array('assign', array('password', $msg['password_previous_msg'])),
                array('assign', array('password_note_msg', ''))
            );
        }
    
        $mail = $this->_getUserMailObj($vars, $letter_key, $func);
        return $mail->Send();
    }


    function sendUserInvited($vars) { // to user
        $mail = $this->_getUserMailObj($vars, 'user_invited');
        return $mail->Send();
    }
    
    
    function sendUserAdded($vars, $pool = false) {        
        
        if(empty($vars['email'])) {
            foreach(array_keys($vars) as $k) {
                $mail[] = $this->_getUserMailObj($vars[$k], 'user_added');
            }
        } else {
            $mail = $this->_getUserMailObj($vars, 'user_added'); 
        }
        
        if ($pool) {
            return $this->addToPool($mail, array_search('user', $this->letter_type));
            
        } else {
            return $mail->Send();
        }
    }
    
    
    function sendUserUpdated($vars) {
        
        $letter_key = 'user_updated';
        $func = [];
        if(empty($vars['password'])) {
            $msg = $this->parser->getTemplateMsg($letter_key);
            $func = array(
                array('assign', array('password', $msg['password_previous_msg']))
            );
        }
    
        $mail = $this->_getUserMailObj($vars, $letter_key, $func);
        return $mail->Send();
    }


    function sendUserDeleted($vars) { // to user
        $mail = $this->_getUserMailObj($vars, 'user_deleted');
        return $mail->Send();
    }    


    function sendPasswordChanged($vars) {
        $mail = $this->_getUserMailObj($vars, 'password_changed');
        return $mail->Send();
    }


    // when user update his account profile/data
    function sendAccountChanged($vars) {
        $mail = $this->_getUserMailObj($vars, 'account_changed');
        return $mail->Send();
    }


    function sendRememberAuthSet($vars) {
        $mail = $this->_getUserMailObj($vars, 'remember_auth');
        return $mail->Send();
    }

    
    function sendAccountDeleteRequest($vars) {
        $mail = $this->_getUserMailObj($vars, 'account_delete_request');
        $mail->AddReplyTo($vars['email']);
        return $mail->Send();
    }


    function sendAccountDeletedUser($vars) {
        $mail = $this->_getUserMailObj($vars, 'account_deleted_to_user');
        return $mail->Send();
    }


    function sendAccountDeletedAdmin($vars) {
        $mail = $this->_getUserMailObj($vars, 'account_deleted_to_admin');
        $mail->AddReplyTo($vars['email']);
        return $mail->Send();
    }


    // <- USER

    // return false if cannot get getEntryDataByEntryType or cannot addToPool
    // return 'no_user_to_send' if no user 
    function sendScheduledEntryNotification($vars, $entry) {

        $this->letter_key = 'scheduled_entry';

        // parser
        $parser =& $this->parser;
        $parser->assign($vars);
        
        // template vars
        $tvars = $this->getTemplateVars();
        
        // author, if not exists wrong email [author_email] will be skipped in mail obj
        if ($this->isExistTo($tvars, 'author_email') && !empty($entry['author_id'])) {
            
            // false means not to use die, returns false on db error
            $user = $this->model->getUser($entry['author_id'], false);
            if($user === false) {
                return false;
            }
            
            if ($user) {
                $this->addNotificationUser($user['user_id']);
                if($this->shouldUserBeEmailed($user['user_id'], 'schedule')) {
                    $parser->assign('author_email', $user['email']);
                }
            }
        }
        
        // updater
        if ($this->isExistTo($tvars, 'updater_email') && !empty($entry['updater_id'])) {
            
            // false means not to use die, returns false on db error
            $user = $this->model->getUser($entry['updater_id'], false);
            if($user === false) {
                return false;
            }            
            
            if ($user) {
                $this->addNotificationUser($user['user_id']);
                if($this->shouldUserBeEmailed($user['user_id'], 'schedule')) {
                    $parser->assign('updater_email', $user['email']);
                }
            }
        }        
        
                
        $admins = array();
        if ($tvars['to_special'] == 'category_admin' && !empty($entry['category_id'])) {
            $admin_rule = ($vars['entry_type'] == 1) ? 'kb_category_to_user_admin' 
                                                     : 'file_category_to_user_admin';
            
            // false means not to use die, returns false on db error
            $admins = $this->model->getCategoryAdminUser($entry['category_id'], $admin_rule, false);
            if($admins === false) {
                return false;
            }
        }
        
        // send to admins and to support if set [to_email]
        if ($admins) {
            $mail =& $this->getMailerObj($tvars);
            //$mail->ClearAddresses(); // remove [to_email]
            foreach($admins as $k => $v) {
                $this->addNotificationUser($v['user_id']);
                if($this->shouldUserBeEmailed($v['user_id'], 'schedule')) {
                    $mail->AddAddress($v['email']);
                }
            }
            
            
        } else { // or send to support
            $tvars['to_email'] = ($tvars['to_email']) ? $tvars['to_email'] : '[support_email]';
            $mail =& $this->getMailerObj($tvars);
        }
        
        // notification by email, $tvars['to_email'] could be empty
        $this->addNotificationUserByEmail($tvars['to_email']);
        
        $mail->Body =& $parser->parse($this->getTemplate());
        $entry_type = $this->letter_type[$vars['entry_type']];    // get string representation        

        //check if any user to send
        if(!$mail->getAllRecipientAddresses()) {    
            return 'no_user_to_send';
        }
    
        // echo "<pre>"; print_r($mail); echo "</pre>";
        // exit();
        
        $send = $this->addToPool($mail, array_search($entry_type, $this->letter_type));
        
        // $send = $mail->Send();
        // if (!$send) {
        //     trigger_error("Cannot send mail: {$mail->ErrorInfo}");
        // }
        
        return $send;
    }

    
    function sendMustreadEntryNotification($user_id, $vars) {

        $this->letter_key = 'mustread_entry';

        // user, false means not to use die, returns false on db error
        $user = $this->model->getUser($user_id, false);
        if($user === false) {
            return false;
        }

        // parser
        $parser = &$this->parser;
        $parser->assign($vars);
        $parser->assignUser($user);
        
        // template vars
        $tvars = $this->getTemplateVars();

        // initialize once $mail object 
        if(!$this->mail) {
            $s = array('smtp_keep_alive'=>true);
            $this->mail =& $this->getMailerObj($this->getTemplateVars(), $s);
        }
        
        $mail = &$this->mail;
        $mail->ClearAddresses();
        $this->parseTemplateValuesTo($mail, $this->getTemplateVars(), false); // add new addresses 
        $mail->Body = $parser->parse($this->getTemplate());
        
        
        if(!$this->shouldUserBeEmailed($user_id, 'mustread')) {
            return 'no_user_to_send';
        }
    
        // echo "<pre>"; print_r($mail); echo "</pre>";
        // exit();
        
        $send = $this->addToPool($mail, array_search('mustread', $this->letter_type));
        
        // $send = $mail->Send();
        // if (!$send) {
        //     trigger_error("Cannot send mail: {$mail->ErrorInfo}");
        // }
        
        return $send;
    }


    function _sendSubscription($user_id, $data, $view) {
        
        // user, false means not to use die, returns false on db error
        $user = $this->model->getUser($user_id, false);
        if($user === false) {
            return false;
        }
        
        // parser
        $parser = &$this->parser;
        $parser->assignUser($user);
        $parser->assign($data); // entries parsed
        
        $cc = &AppController::getClientController();
        $link = $cc->getFolowLink('member');
        $parser->assign('account_link', $link);
        
        $more = array(
            'ec' => md5($user['email']), 
            'et' => $view
            );
        $link = $cc->getFolowLink('unsubscribe', false, false, false, $more);
        $parser->assign('unsubscribe_link', $link);

        // initialize once $mail object 
        if(!$this->mail) {
            $s = array('smtp_keep_alive'=>true);
            $this->mail =& $this->getMailerObj($this->getTemplateVars(), $s);
        }
        
        $mail = &$this->mail;
        $mail->ClearAddresses();
        $this->parseTemplateValuesTo($mail, $this->getTemplateVars(), false); // add new addresses 
        $mail->Body = $parser->parse($this->getTemplate());
        
        // echo "<pre>"; print_r($mail); echo "</pre>";
        // exit();
        
        $send = $this->addToPool($mail, array_search($this->letter_key, $this->letter_type));

        // $send = $mail->Send();        
        // if (!$send) {
        //     trigger_error("Cannot send mail: {$mail->ErrorInfo}");
        // }
        
        return $send;        
    }


    function sendNewsSubscription($user_id, $data) {
        $this->letter_key = 'subscription_news';
        $data = array('content' => $this->parser->parseSubscriptionRow($data, 'news'));
        return $this->_sendSubscription($user_id, $data, 'news');
    }


    function sendCommentSubscription($user_id, $data) {
        $this->letter_key = 'subscription_comment';
        $data = array('content' => $this->parser->parseSubscriptionRow($data, 'comment'));
        return $this->_sendSubscription($user_id, $data, 'comment');
    }


    function sendEntrySubscription($user_id, $data) {
        $this->letter_key = 'subscription_entry';
        $msg = $this->parser->getTemplateMsg($this->letter_key);
        
        $data_parsed = array();
        foreach (array_keys($data) as $type) {
            if (!empty($data[$type]) && is_array($data[$type])) {
                $view = (strpos($type, 'article') !== false) ? 'entry' : 'download';
                $view = (strpos($type, 'comment') !== false) ? 'comment' : $view;
                $data_parsed[$type] = $this->parser->parseSubscriptionRow($data[$type], $view);
            } else {
                $data_parsed[$type] = $msg['no_entries_msg'];
            }            
        }
        
        return $this->_sendSubscription($user_id, $data_parsed, 'entry');
    }    
    
    
    /**
     * Send plain text message.
     */
    function sendPlain($to, $subj, $body) {
        
        $this->letter_key = 'plain';

        $mail =& $this->getMailerObj($this->getTemplateVars(false));
        $mail->From = $this->noreply_email;
        $mail->FromName = $this->from_mailer;
        $mail->ClearAddresses();

        $addresses = explode(',', $to);
        foreach ($addresses as $adr) {
            $mail->AddAddress(trim($adr));
        }
        $mail->Subject = $subj;
        $mail->Body = $body;
        
        // echo "<pre>"; print_r($mail); echo "</pre>";
        // exit();
        
        $send = $mail->Send();
        if (!$send) {
            trigger_error("Cannot send mail: {$mail->ErrorInfo}");
        }
        
        return $send;
    }    


    function getPoolMessage($mail) {
        
        // NOTE names of fields should be equivalent to $mail
        // TODO make sure all of needed fields are captured
        $message = array(
            'to' => $mail->getToAddresses(),
            'cc' => $mail->getCcAddresses(),
            'bcc' => $mail->getBccAddresses(),
            'ReplyTo' => $mail->getReplyToAddresses(),
            'From' => $mail->From,
            'FromName' => $mail->FromName,
            'Subject' => $mail->Subject,
            'Body' => $mail->Body,
            'ContentType' => $mail->ContentType
        );
        
        return $message;             
    }
    

    function addToPool($mail, $letter_type) {
        if(is_array($mail)) {
            $message = array();
            foreach(array_keys($mail) as $k) {
                $message[] = serialize($this->getPoolMessage($mail[$k]));
            }
            
        } else {
            $message = serialize($this->getPoolMessage($mail));
        }
        
        return $this->model->insertIntoPool($letter_type, $message);
    }


    function _testMail() {

        // // news subscription
        // $vars = array(1=>array('title'=>'title 1', 'date'=>'12.12.12'), 
        //               2=>array('title'=>'title 2', 'date'=>'11.11.11'));
        // // $this->sendNewsSubscription(1, $vars);
        // $this->sendCommentSubscription(1, $vars);
        // exit;

        // // entry subscription
        // $vars['new_article'][1] = array('title'=>'title', 'date'=>'12.12.12'); 
        // $vars['new_article'][2] = array('title'=>'title', 'date'=>'12.12.12'); 
        // $vars['updated_article'][3] = array('title'=>'title', 'date'=>'12.12.12'); 
        // $vars['commented_article'][3] = array('title'=>'title', 'date'=>'12.12.12'); 
        // $vars['new_file'] = array(); 
        // $vars['updated_file'] = array(); 
        // $this->sendEntrySubscription(1, $vars);
        
        // // schedule
        // $vars = array('entry_id'=>5, 'entry_type'=>1);
        // $this->sendScheduledEntryNotification($vars);    
    }


    // used for testing in email settings
    function testMail() {
        
        // $this->_testMail();
        
        $this->letter_key = 'test_email';

        // parser
        $parser = &$this->parser;
        
        // template vars
        $tvars = $this->getTemplateVars(false);
        $tvars['to_email'] = '[support_email]';
        
        // mail object
        $mail = &$this->getMailerObj($tvars);
        // $mail->SMTPDebug = $this->settings['smtp_debug'];
        $mail->SMTPDebug = 3; //SMTP::DEBUG_CONNECTION;
        
        $mail->From = $this->noreply_email;
        $mail->FromName = $this->from_mailer;
        $mail->Body = $parser->parse($this->getTemplate(false));
        
        $ret = array();
        if($mail->SMTPDebug) {
            $GLOBALS['kbp_smtp_debug'] = '';
            $mail->Debugoutput = function($str, $level) {
                $GLOBALS['kbp_smtp_debug'] .= "$level: $str<br/>";
            };
        }
        
        $send = $mail->Send();
        
        $ret['debug'] = $GLOBALS['kbp_smtp_debug'];
        
        if(!$send) {
            $ret['error'] = $mail->ErrorInfo;
        }
        
        return $ret;
    }
    
}
?>