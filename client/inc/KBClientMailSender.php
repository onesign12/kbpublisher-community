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


class KBClientMailSender extends AppMailSender
{
    
    // to support about new user question
    function sendContactNotification($vars, $files = array()) {
        
        $this->letter_key = 'contact';
        
        // parser
        $parser = &$this->parser;
        $parser->assign($vars);
        
        // for sender, message submitter
        $vars['user_id'] = AuthPriv::getUserId();
        if(!empty($vars['user_id'])) {
            $parser->assignUser($this->model->getUser($vars['user_id']));
        } else {
            $user['first_name'] = $user['last_name'] = $user['username'] = '';
            $parser->assign($user);
        }
        
        
        $tvars = $this->getTemplateVars();
        $template = $this->getTemplate();
        
        
        $admins = false;
        if($vars['subject_id'] && $tvars['to_special'] == 'feedback_admin') {
            $admins = $this->model->getCategoryAdminUser($vars['subject_id'], 'feedback_user_admin');
        }
        
        if($vars['subject_id']) {
            $parser->assign('subject', $this->model->getListValue('feedback_subj', $vars['subject_id']));
        }
        
        // send to feedback admins and to support if set [to_email]
        if($admins) {
            $mail = &$this->getMailerObj($tvars);
            //$mail->ClearAddresses(); // remove [to_email]
            foreach($admins as $k => $v) {
                $this->addNotificationUser($v['user_id']);
                if($this->shouldUserBeEmailed($v['user_id'], 'contact')) {
                    $mail->AddAddress($v['email']);
                }
            }
            
        // or send to support
        } else {
            $tvars['to_email'] = ($tvars['to_email']) ? $tvars['to_email'] : '[support_email]';
            $mail = &$this->getMailerObj($tvars);
        }
        
        // notification by email, $tvars['to_email'] could be empty
        $this->addNotificationUserByEmail($tvars['to_email']);
        
        // reply to
        $mail->AddReplyTo($parser->getValue('email'), $parser->getValue('name'));
        
        // custom, if not block in template
        if(!empty($vars['custom']) && strpos($template, '[custom]') === false) {
            $message['message'] = $vars['message'] . "\n\n" . $vars['custom'];
            $parser->assign($message);
        }
        
        // no custom, add to strip [custom] block in template 
        if(!isset($vars['custom'])) {
            $parser->assign(array('custom'=>''));
        }
        
        if($files) {
            foreach($files as $file) {
                $filename = basename($file);
                $mail->AddAttachment($file, $filename);  // optional name
            }
        }        
        
        $mail->Body = $parser->parse($template);
        if($mail->Mailer == 'sendmail') {
            $mail->Body = escapeshellarg($mail->Body); //escapeshellcmd
        }
        
        // echo '<pre>', print_r($parser, 1), '</pre>';
        // echo "<pre>"; print_r($mail); echo "</pre>";
        // exit();
        
        $sent = $mail->Send();
        if(!$mail->getAllRecipientAddresses()) { // nobody to sent return true
            $sent = true;
        }
        
        return $sent;
    }    
    
    
    // to support about new user rating
    function sendRatingNotification($vars) {

        $this->letter_key = 'rating_comment_added';
        
        // parser
        $parser = &$this->parser;
        $parser->assign($vars);
        
        // for sender, comment submitter
        $vars['user_id'] = AuthPriv::getUserId();
        if(!empty($vars['user_id'])) {
            $parser->assignUser($this->model->getUser($vars['user_id']));
        } else {
            $user['email'] = $this->settings['noreply_email'];
            $user['first_name'] = $user['last_name'] = $user['username'] =  $user['name'] = '';
            $parser->assign($user);
        }
        
        $tvars = $this->getTemplateVars();
        
        // entry data, returns false on db error
        $entry = $this->model->getEntryDataByEntryType($vars['entry_id'], 1);
        if ($entry === false) {
            return false;
        }
        
        // author, if not exists wrong email [author_email] willif($this->shouldUserBeEmailed($v['user_id'], 'contact')) { be skipped in mail obj
        if ($this->isExistTo($tvars, 'author_email') && !empty($entry['author_id'])) {
            $user = $this->model->getUser($entry['author_id']);
            if ($user) {
                $this->addNotificationUser($user['user_id']);
                if($this->shouldUserBeEmailed($user['user_id'], 'comment')) {
                    $parser->assign('author_email', $user['email']);
                }
            }
        }

        // updater
        if ($this->isExistTo($tvars, 'updater_email') && !empty($entry['updater_id'])) {
            $user = $this->model->getUser($entry['updater_id']);
            if ($user) {
                $this->addNotificationUser($user['user_id']);
                if($this->shouldUserBeEmailed($user['user_id'], 'comment')) {
                    $parser->assign('updater_email', $user['email']);
                }
            }
        }
        
        $admins = false;
        if($tvars['to_special'] == 'category_admin' && isset($vars['category_id'])) {
            $admins = $this->model->getCategoryAdminUser($vars['category_id'], 'kb_category_to_user_admin');
        }    
        
        // send to feedback admins and to support if set [to_email]
        if($admins) {
            $mail = &$this->getMailerObj($tvars);
            //$mail->ClearAddresses(); // remove [to_email]
            foreach($admins as $k => $v) {
                $this->addNotificationUser($v['user_id']);
                if($this->shouldUserBeEmailed($v['user_id'], 'comment')) {
                    $mail->AddAddress($v['email']);
                }
            }
            
        // or send to support
        } else {
            $tvars['to_email'] = ($tvars['to_email']) ? $tvars['to_email'] : '[support_email]';
            $mail = &$this->getMailerObj($tvars);
        }    
        
        // notification by email, $tvars['to_email'] could be empty
        $this->addNotificationUserByEmail($tvars['to_email']);
        
        // reply to
        if($uemail = $parser->getValue('email')) {
            if($uemail !== $this->settings['noreply_email']) {
                $mail->AddReplyTo($parser->getValue('email'), $parser->getValue('name'));
            }
        }
        
        // remove noreply email from template, user block
        if(empty($vars['user_id'])) {
            $parser->assign('email', '');
        }
        
        // no rating given, replace to msg
        if(!isset($vars['rating'])) {
            $msg = $parser->getTemplateMsg($this->letter_key);
            $parser->assign('rating', $msg['no_rating_msg']);
        }
        
        
        $mail->Body = $parser->parse($this->getTemplate());
        if($mail->Mailer == 'sendmail') {
            $mail->Body = escapeshellarg($mail->Body); //escapeshellcmd
        }
        
        // echo '<pre>', print_r($parser, 1), '</pre>';
        // echo "<pre>"; print_r($mail); echo "</pre>";
        // exit();
                
        // return $this->addToPool($mail, array_search('user', $this->letter_type));

        $sent = $mail->Send();
        if(!$mail->getAllRecipientAddresses()) { // nobody to sent return true
            $sent = true;
        }
        
        return $sent;
    }
    
    
    function sendApproveCommentAdmin($vars) {
        
        $this->letter_key = 'comment_approve_to_admin';

        // parser
        $parser = &$this->parser;
        $parser->assign($vars);
        
        // for sender, comment submitter
        $vars['user_id'] = AuthPriv::getUserId();
        if(!empty($vars['user_id'])) {
            $parser->assignUser($this->model->getUser($vars['user_id']));
        } else {
            $user['email'] = (empty($vars['email'])) ? $this->settings['noreply_email'] : $vars['email'];
            $user['first_name'] = $user['last_name'] = $user['username'] = '';
            $parser->assign($user);
        }
                
        
        $tvars = $this->getTemplateVars();
        
        $admins = array();
        if($tvars['to_special'] == 'category_admin' && !empty($vars['category_id'])) {
            $admins = $this->model->getCategoryAdminUser($vars['category_id'], 'kb_category_to_user_admin');
        }
        
        
        // send to admins and to support if set [to_email]
        if($admins) {
            $mail = &$this->getMailerObj($tvars);
            //$mail->ClearAddresses(); // remove [to_email]
            foreach($admins as $k => $v) {
                $this->addNotificationUser($v['user_id']);
                if($this->shouldUserBeEmailed($v['user_id'], 'comment')) {
                    $mail->AddAddress($v['email']);
                }
            }
            
        // or send to support
        } else {
            $tvars['to_email'] = ($tvars['to_email']) ? $tvars['to_email'] : '[support_email]';
            $mail = &$this->getMailerObj($tvars);
        }
        
        // notification by email, $tvars['to_email'] could be empty
        $this->addNotificationUserByEmail($tvars['to_email']);        
        
        $mail->Body = $parser->parse($this->getTemplate());
        if($mail->Mailer == 'sendmail') {
            $mail->Body = escapeshellarg($mail->Body); //escapeshellcmd
        }
        
        //echo '<pre>', print_r($parser, 1), '</pre>';
        // echo "<pre>"; print_r($mail); echo "</pre>";
        // echo "<pre>"; print_r($this); echo "</pre>";
        // exit();
        
        $sent = $mail->Send();
        if(!$mail->getAllRecipientAddresses()) { // nobody to sent return true
            $sent = true;
        }
        
        return $sent;
    }    
    
    
    function sendToFriend($vars, $article = array()) {
        
        $this->letter_key = 'send_to_friend';

        // parser
        $parser = &$this->parser;
        $parser->assign($vars);
        $parser->assign('link', $vars['entry_link']);
        $parser->assign('message', $vars['comment']);
        $parser->assign('email', $vars['friend_email']); // to
        $parser->assign('sender_email', $vars['your_email']);
        $parser->assign('sender_name', $vars['name']);
        
        // mail object
        $mail = &$this->getMailerObj($this->getTemplateVars());
        
        // reply to
        if($parser->getValue('email')) {
            $mail->AddReplyTo($parser->getValue('sender_email'), $parser->getValue('sender_name'));
        }
        
        
        if($article) {
            // AddStringAttachment($string, $filename, $encoding = 'base64', $type = 'application/octet-stream') {
            $mail->AddStringAttachment($article['body'], $article['filename'], 'base64', 'text/html');
        }
        
        
        $mail->Body = $parser->parse($this->getTemplate());
        
        if($mail->Mailer == 'sendmail') {
            $mail->Body = escapeshellarg($mail->Body); //escapeshellcmd
        }        
        
        // echo '<pre>', print_r($_POST, 1), '</pre>';
        // echo "<pre>"; print_r($mail); echo "</pre>";
        // exit();
        
        return $mail->Send();
    }
        
    
    function sendConfirmRegistration($vars) {
        
        $this->letter_key = 'confirm_registration';

        // parser
        $parser = &$this->parser;
        $parser->assign($vars);
        $parser->assignUser($vars);
        
        // mail object
        $mail = &$this->getMailerObj($this->getTemplateVars());
        $mail->Body = $parser->parse($this->getTemplate());
        
        //echo "<pre>"; print_r($mail); echo "</pre>"; 
        //exit();    
        
        return $mail->Send();    
    }
    
    
    function sendRegistrationConfirmed($vars) {
        
        $this->letter_key = 'registration_confirmed';

        // parser
        $parser = &$this->parser;
        $parser->assign($vars);
        $parser->assignUser($vars);
        
        // mail object
        $mail = &$this->getMailerObj($this->getTemplateVars());
        $mail->Body = $parser->parse($this->getTemplate());
        
        //echo "<pre>"; print_r($mail); echo "</pre>"; 
        //exit();    
        
        return $mail->Send();
    }
    
    
    function sendApproveRegistrationAdmin($vars) {
        
        $this->letter_key = 'user_approve_to_admin';

        // parser
        $parser = &$this->parser;
        $parser->assign($vars);
        
        // mail object
        $mail = &$this->getMailerObj($this->getTemplateVars());
        $mail->Body = $parser->parse($this->getTemplate());
        
        //echo "<pre>"; print_r($mail); echo "</pre>"; 
        //exit();    
        
        return $mail->Send();        
    }
    
    
    function sendApproveRegistrationUser($vars) {
        
        $this->letter_key = 'user_approve_to_user';

        // parser
        $parser = &$this->parser;
        $parser->assign($vars);
        
        // mail object
        $mail = &$this->getMailerObj($this->getTemplateVars());
        $mail->Body = $parser->parse($this->getTemplate());
        
        //echo "<pre>"; print_r($mail); echo "</pre>"; 
        //exit();    
        
        return $mail->Send();
    }
}
?>