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


class AppMailParser
{

    var $template_dir;
    var $templates = array('send_to_friend'     => 'kb_friend.txt',
                           'answer_to_user'     => 'kb_answer.txt',
                           'contact'            => 'kb_contact.txt'
                           );
    
    var $replacer;
    var $vars      = array('support_name', 
                           'support_email', 
                           'support_mailer',
                           'noreply_email',
                           'admin_email',
                           'name',
                           'username',
                           'first_name',
                           'last_name',
                           'middle_name',
                           'email',
                           'link'
                           //'admin_username',
                           //'admin_first_name',
                           //'admin_last_name',
                           //'admin_middle_name',
                           //'admin_email'
                           
                           );

    
    
    function __construct() {
        $this->template_dir = APP_EMAIL_TMPL_DIR;
        $this->setReplaser();
    }

    
    function setReplaser() {
        $this->replacer = new Replacer;
        $this->replacer->s_var_tag = "[";
        $this->replacer->e_var_tag = "]";
        $this->replacer->strip_var = false;
    }
    
    
    function setSettingVars($setting) {
        $this->assign('support_name', $setting['from_name']);
        $this->assign('support_email', $setting['from_email']);
        $this->assign('support_mailer', $setting['from_mailer']);
        $this->assign('noreply_email', $setting['noreply_email']);
        $this->assign('admin_email', $setting['admin_email']);
    }
    
    
    function assignUser($user) {
        $this->assign($user);
        $this->assign('name', $user['first_name'] . ' ' . $user['last_name']);
        $this->assign('login', $user['username']);
    }    
    
    
    function getVars() {
        return $this->vars;
    }
    
    
    function getValue($key) {
        if(isset($this->replacer->vars[$key])) {
            return $this->replacer->vars[$key];
        }
    }
    
    
    function assign($var, $value = false) {
        $this->replacer->assign($var, $value);
    }
    
    
    function parse($str) {
        return $this->replacer->parse($str);
    }
    
    
    function setTemplate($key, $value) {
        $this->templates[$key] = $value;
    }
    
    
    static function getTemplateMsg($letter_key = false) {
        $msg = AppMsg::getMsg('template_msg.ini', 'email_setting', 'common');
        return array_merge(AppMsg::getMsg('template_msg.ini', 'email_setting', $letter_key), $msg);
    }
    
    
    function getTemplate($letter_key, $msg = array()) {

        if(!$msg) {
            $msg = $this->getTemplateMsg($letter_key);
        }
        
        $template = $this->template_dir . $letter_key. '.txt';
        if(isset($this->templates[$letter_key])) {
            $template = $this->template_dir . $this->templates[$letter_key];
        }
        
        
        $etmpl = AppMsg::parseMsgs(APP_EMAIL_TMPL_DIR . '_extended_templates.ini');
        
        $extended_content = '';
        if(isset($etmpl['templates'][$letter_key])) {
            $custom = (isset($etmpl[$letter_key])) ? $etmpl[$letter_key] : [];
            $df = 'template_' . $letter_key . '.txt'; // default file
            $tmpl_lang = (isset($custom['template_lang'])) ? $custom['template_lang'] : $df; 
        
            $df = 'tmpl_default.txt'; // default file
            $tmpl_file = (isset($custom['template_file'])) ? $custom['template_file'] : $df; 
            $tmpl_file = ($tmpl_file == 'none') ? false : $tmpl_file; 
        
            // echo '<pre>' . print_r($tmpl_lang, 1) . '</pre>';
            // echo '<pre>' . print_r($tmpl_file, 1) . '</pre>';
            // echo '<pre>' . print_r($etmpl, 1) . '</pre>';
            // exit;
        
            $file = AppMsg::getModuleMsgFileSingle('email_setting', $tmpl_lang);
            $tpl = new tplTemplatez($file);
            $tpl->clean_html = false;
            $tpl->strip_vars = false;
                
            $tpl->tplParse($msg);
            $extended_content = $tpl->tplPrint(1);    
            $template = $this->template_dir . $tmpl_file;
            
            if (!$tmpl_file) { // we got just a lang file
                return $content;   
            } else {
                $template = $this->template_dir . $tmpl_file;
            }
        }
        
        
        $tpl = new tplTemplatez($template);
        $tpl->clean_html = false;
        //$tpl->strip_vars = false;
        
        $tpl->tplAssign('template', $extended_content);
        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }
    
    
    function parseHtmlTemplate($template, $vars) {
        
        $tpl = new tplTemplatez($this->template_dir . 'page_html.html');
        $tpl->clean_html = false;
        $tpl->strip_vars = true;
        
        $tpl->tplAssign('content', $template);
        $tpl->tplAssign($vars);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function &parseSubscriptionRow($data, $view) {
        
        $tpl = new tplTemplatez($this->template_dir . 'subscription_row.html');
        $tpl->clean_html = false;
        $tpl->strip_vars = false;
        
        $cc = &AppController::getClientController();
        
        foreach(array_keys($data) as $entry_id) {
            $a['title'] = $data[$entry_id]['title'];
            $a['link'] = $cc->getFolowLink($view, false, $entry_id);
            @$a['date'] = $data[$entry_id]['date'];
            
            $tpl->tplParse($a, 'row');
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }    
}
?>