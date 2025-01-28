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



class KBClientView_success_go extends KBClientView_common
{
    
    var $timeout = 3000;
    
    function &execute(&$manager) {
        
        $tpl = new tplTemplatez($this->getTemplate('success_go.html'));
        
        $msg_key = $this->controller->getRequestVar('msg');
        $view_id = $this->getViewId($msg_key);
        $msg_id = $this->getMsgId($msg_key);
        
        $entry_id = $this->entry_id;

        if($view_id == 'entry' && $this->controller->mod_rewrite == 3) {
            if($this->controller->entry_title) {
                $entry_id = $this->controller->getEntryLinkParams($entry_id, false, $this->controller->entry_title);
            } else {
                $data = $manager->getEntryTitles($entry_id);
                $data = $data[$entry_id];
                $entry_id = $this->controller->getEntryLinkParams($entry_id, $data['title'], $data['url_title']);
            }
        }

        
        $cat_id = ($msg_id && !$this->category_id) ? 0 : $this->category_id; //set to 0 correct display msg
        
        $more = array();
        $bp = $this->controller->getRequestVar('bp');
        if ($bp) {
            $more = array('bp' => $bp);
        }
        
        $hash = '';
        $message_id = $this->controller->getRequestVar('message_id');
        if ($message_id) {
            $hash = 'c' . $message_id;
        }
        
        $url = $this->controller->getRedirectLink($view_id, $cat_id, $entry_id, $msg_id, $more);
        
        if ($hash) {
            $url .= '#' . $hash;
        }
        
        $tpl->tplAssign('go_url', $url);
        $tpl->tplAssign('timeout', $this->getTimeout($msg_key));    
        
        $this->msg_id = $this->getMsgIdToDisplay($msg_key);
        $format = $this->getMsgFormat($msg_key);
        $tpl->tplAssign('msg', $this->getActionMsg($format));
        
        $tpl->tplAssign($this->css);
        
        $r = new Replacer();
        $msg = $r->parse($this->msg['redirect_msg'], array('go_url'=>$url));
        
        $tpl->tplAssign('redirect_msg', $msg);
        $tpl->tplAssign('meta_charset', $this->conf['lang']['meta_charset']);
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);            
    }
    
    
    // where to return after message 
    function getViewId($key) {
        
        $view = array(
            'comment_posted'  => 'comment',
            'comment_wait'    => 'entry',
            'comment_updated' => 'comment',
            'comment_deleted' => 'comment',
            
            'entry_sent'      => 'contact',
            'friend_sent'     => 'entry',

            'password_reset_sent'    => 'password',
            'password_reset_success' => 'login',

            'entry_updated'   => 'entry',
            'entry_created'   => 'entry',
            'entry_rated'     => 'entry',

            'account_updated'  => 'account',
            'password_updated' => 'account',
            'api_updated'      => 'account',

            'confirmation_sent'                => 'confirm', // after registration to confirm it
            'registration_not_confirmed'       => 'confirm', // unable to confirm
            'registration_confirmed'           => 'login',
            'registration_confirmed_approve'   => 'confirm' // after confirm, send to approve
            );
                      
        return (isset($view[$key])) ? $view[$key] : false;
    }
    
    
    // what msg to display after (redirect)
    // these msgs will be displayed on page, no growl
    static function getMsgId($key, $return_msg_format = false) {
        
        $view = array(
            'comment_wait'   => 'hint',
            'entry_sent'     => 'success',
            'entry_not_sent' => 'error',
            
            'password_reset_sent'     => 'hint',
            'password_reset_not_sent' => 'error',
            'password_reset_error'    => 'error',
            'password_reset_success'  => 'success',
            
            'confirmation_sent'       => 'hint',
            'confirmation_not_sent'   => 'error',
            
            'registration_confirmed'         => 'success',
            'registration_not_confirmed'     => 'error',
            'registration_confirmed_approve' => 'hint'
            );
        
        if($return_msg_format) {
            $ret = (isset($view[$key])) ? $view[$key] : false;
        } else {
            $ret = (isset($view[$key])) ? $key : false;
        }
        
        return $ret;
    }
    
    
    // set js delay time on after action screen
    // for keys use 0.5, 1, 2 so on 
    // it will set delay time = $this->delay_time*key
    function getTimeout($key) {
        $multilplier = array(
            'comment_posted'                    => 0.5,
             'comment_wait'                      => 1,
             'entry_sent'                        => 2,
             'registration_not_confirmed'        => 2,
             'registration_confirmed_approve'    => 2,
             'registration_confirmed'            => 2,
             'confirmation_sent'                 => 2,
             'entry_updated'                     => 0.5,
             'entry_created'                     => 0.5,
             'password_reset_sent'               => 2,
             'access_denied'                     => 2,
             'entry_rated'                       => 0.5
             );
                      
        return (isset($multilplier[$key])) ? $this->timeout*$multilplier[$key] : $this->timeout;
    }
    
    
    // we map some msg here to display correct one on success page
    // comment_posted for example to record_posted
    function getMsgIdToDisplay($key) {
        $map = array(
            'comment_updated' => 'updated',
            'comment_deleted' => 'deleted',
            'message_deleted' => 'deleted',
            'account_updated' => 'data_updated'
            );
                      
        return (isset($map[$key])) ? $map[$key] : $key;        
    }
    
    
    // what format to display message
    function getMsgFormat($key) {
        $error_format = array(
            'access_denied'
        );
        
        return (in_array($key, $error_format)) ? 'error' : 'success';        
    }
}
?>