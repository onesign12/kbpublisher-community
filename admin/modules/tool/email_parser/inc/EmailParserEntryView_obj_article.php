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



//class EmailParserEntryView_obj_article extends KBEntryView_form
class EmailParserEntryView_obj_article extends AppView
{
    
    var $template = 'form_obj_article.html';
    
    
    function execute(&$obj, &$manager) {
        $tpl = $this->_executeTpl($obj, $manager);
        
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    // return tplTemplatez obj
    function _executeTpl(&$obj, &$manager) {
        
        // tag
        $tags = $obj->getTag();
        if (!empty($tags)) {
            $ids = implode(',', $tags);
            $obj->setTag($manager->tag_manager->getTagByIds($ids));
        }
        
        $f = new KBEntryView_form;
        $tpl = $f->_executeTpl($obj, $manager, $this->template_dir . $this->template);
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('trigger_msg.ini');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        $tpl->tplAssign('popup_title', $this->msg['article_option_msg']);
        
        
        if(!empty($_GET['saved']) && !$obj->errors) {
            $tpl->tplSetNeeded('/close_window');
            // $tpl->tplAssign('action_num', $_GET['field_id']);
            $tpl->tplAssign('action_num', htmlentities($_GET['field_id']));
        }
        
        $tpl->tplAssign('msg', EmailParserEntryView_obj_article::getPlaceholderBlock());
        
        $author_id = $obj->get('author_id');
        if ($author_id) {
            $tpl->tplSetNeeded('/author');
            $author = $manager->cat_manager->getAdminUserByIds($author_id);
            
            $tpl->tplAssign('author_id', $author_id);
            $tpl->tplAssign('name', $author[$author_id]); 
        }
        
        $check_sender = @$obj->get('check_sender');
        $tpl->tplAssign('email_sender_checked', ($check_sender) ? 'checked' : '');

        
        // user link
        $more = array('filter[s]' => 1, 'limit' => 1, 'close' => 1);
        $link = $this->getLink('users', 'user', false, false, $more);
        $tpl->tplAssign('user_popup_link', $link);
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $more = array('item' => $this->controller->getMoreParam('item'));
        $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        return $tpl;
    }
    
    
    static function getPlaceholderBlock() {
        $tags = TriggerParserAction_email_automation::$placeholders;
        $msg = AppMsg::getMsg('common_msg.ini', 'email_setting');
        
        foreach ($tags as $tag) {
            $_tags[] = sprintf('[<a href="#" onclick="insertPlaceholder(\'[%s]\');">%s</a>]', $tag, $tag);
        }
        
        $tags = implode(', ', $_tags);
        $msgs = array('title' => $msg['template_tags'], 'body' => $tags);
        
        return BoxMsg::factory('hint', $msgs);
    }

}
?>