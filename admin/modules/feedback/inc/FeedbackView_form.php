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



class FeedbackView_form extends AppView
{
    
    var $template = 'form.html';
    
    
    function execute(&$obj, &$manager, $data) {
        
        $this->addMsg('letter_template_msg.ini');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $subject = $manager->getSubjectSelectRange();
        $subject = (isset($subject[$obj->get('subject_id')])) ? $subject[$obj->get('subject_id')] : '';
        $tpl->tplAssign('subject', $subject);        
        
        $obj->set('question', nl2br($obj->get('question')));
        $obj->set('date_formatted', $this->getFormatedDate($obj->get('date_posted')));
        
        if($obj->get('user_id')) {
            $name = $data['first_name'] . ' ' . $data['last_name'];
            $tpl->tplAssign('first_name', $data['first_name']);
            $tpl->tplAssign('last_name', $data['last_name']);
            $tpl->tplAssign('username', $data['username']);
            $tpl->tplAssign('show_name', $name . ' - ' . $data['username']);
            $obj->set('name', $name);
        } else {
            $tpl->tplAssign('show_name', $obj->get('name'));
        }
        
        if ($data['email']) {
            $tpl->tplSetNeeded('/send_button');
            $tpl->tplAssign('cancel_button_margin', 25);
        }
        
        
        // attachment
        if($obj->get('attachment')) {
            $files = explode(';', $obj->get('attachment'));
            foreach($files as $k => $file) {
                $v = array();
                
                $v['filename'] = basename($file);
                $more = array('type' => 'question', 'f' => $k);
                
                $v['open_link'] = $this->getActionLink('file', $obj->get('id'), $more);
                $v['download_link'] = $this->getActionLink('file_download', $obj->get('id'), $more);
                
                $tpl->tplParse($v, 'file');
            }
        }


        // answer
        $sender = new AppMailSender();
        $sender->letter_key = 'answer_to_user';
        $vars = array_merge($obj->get(), $data);
        $vars['answer'] = '';
        
        $sender->parser->assign($vars);    
        $sender->parser->assign('subject', $subject);    
        
        $tvars['body'] = $sender->getTemplate();
        $obj->set('answer', $sender->parser->parse($tvars['body']));
        
        $popup_link = $this->getLink('file', 'file_entry');
        $tpl->tplAssign('popup_link', $popup_link);
        
        
        // custom  
        $custom = CommonCustomFieldView::getCustomData($obj->getCustom(), $manager->cf_manager);
        foreach($custom as $v) {
            $tpl->tplParse($v, 'custom_row');
        }
        
        
        // place to kb
        $more_param = array('question_id' => $obj->get('id'), 
                            'referer' => WebUtil::serialize_url($this->controller->getCommonLink()));
        $link = $this->controller->getLink('knowledgebase', 'kb_entry', false, 'question', $more_param);
        $tpl->tplAssign('place_link', $link);        
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormFile'));
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj, '', $this->msg['answer_to_user_msg']));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxValidateFormFile($values, $options = array()) {
        $objResponse = $this->ajaxValidateForm($values, $options);
        
        if (!$this->obj->errors && !empty($values['_files'])) {
            $options['func'] = 'getValidateFile';
            $objResponse = $this->ajaxValidateForm($values['_files'], $options);            
        }
        
        return $objResponse;
    }
}
?>