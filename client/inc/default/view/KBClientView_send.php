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

class KBClientView_send extends KBClientView_common
{

    function &execute(&$manager) {
        
        $row = $manager->getEntryById($this->entry_id, $this->category_id);
        $row = $this->stripVars($row);
        if(!$row) { $a = ''; return $a; }
        
        $this->parse_form = false;
        $this->meta_title = $this->msg['send_link_msg'];
        
        $type = ListValueModel::getListRange('article_type', false);        
        $prefix = $this->getEntryPrefix($row['id'], $row['entry_type'], $type, $manager);
        $title = $prefix . $this->getSubstring($row['title'], 50, '...');
        
        $entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
        $link = $this->getLink('entry', false, $entry_id);
        $this->nav_title = array($link => $title, $this->msg['send_link_msg']);
        
        $related = $manager->getEntryRelatedInline($this->entry_id);
        
        $data = $this->getForm($manager, $row['title']);
        $data .= $this->getEntry($manager, $row, $related);
        
        return $data;
    }
    

    function getForm($manager, $entry_title) {
        
        $tpl = new tplTemplatez($this->getTemplate('send_form.html'));
        $tpl->tplAssign('user_msg', $this->getErrors());
        $tpl->tplAssign('action_link', $this->getLink('all'));
                                                              
        if($manager->is_registered && !$_POST) {
            $data = $this->stripVars($manager->getUserInfo($manager->user_id));
            $tpl->tplAssign('your_email', $data['email']);
            $tpl->tplAssign('name', $data['first_name'] . ' ' . $data['last_name']);        
        }      
        
        $link = $this->getLink('entry', $this->category_id, $this->entry_id);
        
        // hidden    
        $tpl->tplAssign('entry_link', $link);
        $tpl->tplAssign('entry_title', $entry_title);
        
        // article content
        if($manager->getSetting('show_send_link_article')) {
            $tpl->tplSetNeeded('/attach_article');
            $tpl->tplAssign('ch_attach_article', $this->getChecked((isset($_POST['attach_article']))));
        }
        
        // captcha
        if($this->useCaptcha($manager, 'send_link')) {
            $tpl->tplAssign('captcha_block', $this->getCaptchaBlock($manager, 'send_link'));
        }
        
        $ajax = &$this->getAjax('validate');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('validate', $ajax, 'ajaxValidateForm'));
        
        $tpl->tplAssign('cancel_link', $link);
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEntry(&$manager, $row, $related)  {
    
        if(DocumentParser::isTemplate($row['body'])) {
            DocumentParser::parseTemplate($row['body'], array($manager, 'getTemplate'));
        }
    
        if(DocumentParser::isLink($row['body'])) {
            DocumentParser::parseLink($row['body'], array($this, 'getLink'), $manager, $related, $this->entry_id, $this->controller);    
        }
        
        if(DocumentParser::isCode($row['body'])) {
            DocumentParser::parseCode($row['body'], $manager, $this->controller);    
        }

        DocumentParser::parseCurlyBraces($row['body']);
    
        $tpl = new tplTemplatez($this->template_dir . 'article.html');
        $tpl->tplParse($row);
        return $tpl->tplPrint(1);
    }
    
    
    function validate($values, $manager) {
        
        
        $v = new Validator($values, false);
        $v->required('required_msg', array('name', 'your_email', 'friend_email'));
        $v->regex('email_msg', 'email', 'your_email');
        //$v->regex('email_msg', 'email', 'friend_email');
        
        $friend_email = explode(',', $values['friend_email']);
        foreach($friend_email as $email) {
            if($email = trim($email)) {
                if(!$ret = Validate::email($email)) {
                    $v->setError('email_msg', 'friend_email', 'email');
                    break;
                }                
            }
        }
        
        if($error = $this->validateCaptcha($manager, $values, 'send_link')) {
            $v->setError($error[0], $error[1], $error[2], $error[3]);
        }
        
        return $v->getErrors();
    }
    
}
?>