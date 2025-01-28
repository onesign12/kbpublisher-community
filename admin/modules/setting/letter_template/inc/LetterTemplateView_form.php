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


class LetterTemplateView_form extends AppView
{
    
    var $template = 'form.html';
    
    function execute(&$obj, &$manager) {
        
        //$this->addMsg('user_msg.ini');
        $this->addMsg('common_msg.ini', 'email_setting');
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        // template tags
        $p = new AppMailParser();
        $tags = $p->getVars();
        if($obj->get('extra_tags')) {
            $tags = array_merge($tags, explode(',', $obj->get('extra_tags')));
        }
        $tags = array_diff($tags, explode(',', $obj->get('skip_tags')));
        //echo '<pre>', print_r($obj->get('skip_tags'), 1), '</pre>';
        //echo '<pre>', print_r($tags, 1), '</pre>';
        
        $tags = '[' . implode('], [', array_unique($tags)) . ']';
        $msgs = array('title'=>$this->msg['template_tags'], 'body'=>$tags);
        $tpl->tplAssign('hint_msg', BoxMsg::factory('hint', $msgs));
        
        
        if($obj->get('is_html')) {
            $tpl->tplAssign('vck_editor', $this->getHtmlArea($obj->get('body')));
        } else {
            $tpl->tplAssign('vck_editor', $this->getTextArea($obj->get('body')));
        }
        
        $skip_block = array('from', 'to', 'to_cc', 'to_bcc');
        $skip = explode(',', $obj->get('skip_field'));
        
        // for disab;e change from in cloud
        if(BaseModel::isCloud()) {
            $skip = array_unique(array_merge($skip, array('from')));
        }
        
        
        // foreach($skip_block as $k => $v) {
        //     if(!in_array(trim($v), $skip)) {
        //         $tpl->tplSetNeeded('/' . $v);
        //     } else {
        //         $obj->hidden[] = $v;
        //     }
        // }

        foreach($skip_block as $k => $v) {
            $tpl->tplSetNeeded('/' . $v);
            $opt = $v . '_options';
            if(in_array(trim($v), $skip)) {
                $tpl->tplAssign($opt, 'style="background: #F0F0F0;" readonly');
            } else {
                $tpl->tplAssign($opt, '');
            }
        }

        
        $category_admin = array(
            'article_approve_to_admin', 
            'file_approve_to_admin', 
            'comment_approve_to_admin',
            'rating_comment_added',
            'scheduled_entry'); 
                                
        if(in_array($obj->get('letter_key'), $category_admin)) {            
            $tpl->tplAssign('ca_checked', $this->getChecked(($obj->get('to_special') == 'category_admin')));
            $tpl->tplSetNeeded('/category_admin');
        }
        
        
        $subject_admin = array('contact');
        if(in_array($obj->get('letter_key'), $subject_admin)) {
            
            $more = array('list'=>'feedback_subj');
            $link = $this->controller->getLink('tool', 'list_tool', false, false, $more);
            
            $str = '<a href="%s" title="%s">%s</a>';
            $str = sprintf($str, $link, $this->msg['atitle_supervisor_msg'], $this->msg['to_feedback_admin_msg']);
            $this->msg['to_feedback_admin_msg'] = $str;
            
            $tpl->tplAssign('ca_checked', $this->getChecked(($obj->get('to_special') == 'feedback_admin')));
            $tpl->tplSetNeeded('/feedback_admin');
        }        
        
        
        $link = $this->getActionLink('default', $obj->get('id'));
        $tpl->tplAssign('default_link', $link);
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
        
    
    function getHtmlArea($value) {
        return $this->getEditor($value, 'article');
    }
    
    
    function getTextArea($value) {
        $str = '<textarea cols="60" rows="20" name="body" style="width: %s;">%s</textarea>';
        return sprintf($str, '100%', $value);
    }
}
?>