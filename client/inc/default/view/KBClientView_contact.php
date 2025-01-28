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


class KBClientView_contact extends KBClientView_common
{

    var $found_entries = array();

    
    function &execute(&$manager) {
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['menu_contact_us_msg'];
        $this->nav_title = $this->meta_title;
        $this->category_nav_generate = false; // not to generate categories in navigation line
        
        if($this->msg_id == 'entry_sent') {
            $data = '';
        } else {
            $data = &$this->getForm($manager);
        }
        
        return $data;
    }
    

    function &getForm(&$manager) {

        $tpl = new tplTemplatez($this->getTemplate('contact_form.html'));
        
        // subject
        $select = new FormSelect();
        $select->setFormMethod($_POST);
        $select->select_tag = false;
        //$select->setSelectWidth(200);
        $select->setSelectName('subject_id');
        
        $range = ListValueModel::getListSelectRange('feedback_subj', true);
        $select->setRange($range);
        
        if(isset($_POST['subject_id'])) {
            $subject = $_POST['subject_id'];
        } else {
            $subject = ListValueModel::getListDefaultEntry('feedback_subj');   
        }
        
        $tpl->tplAssign('subject_select', $select->select($subject));
        
        
        if(APP_DEMO_MODE) {
            $msg = BoxMsg::factory('hint');
            $msg->setMsgs('', 'To see an example of how "Quick Response" works, type "LDAP integration" in the field below.');
            $tpl->tplAssign('demo_responce_msg', $msg->get());
        }        
        
        $tpl->tplAssign('user_msg', $this->getErrors());
        $tpl->tplAssign('action_link', $this->getLink('all'));
        $tpl->tplAssign('cancel_link', $this->getLink('index', $this->category_id));
        
        
        // attachments
        $num = $manager->getSetting('contact_attachment');
        if($num) {
            $tpl->tplSetNeeded('/is_attachment');
        }
        for($i=1; $i<=$num; $i++) {
            $a['num'] = $i;
            $a['attachment_msg'] = $this->msg['attachment_msg'];
            $tpl->tplParse($a, 'attachment');
        }
        
        $allowed = $manager->getSetting('contact_attachment_ext');
        if($allowed && $num) {
            $tpl->tplAssign('allowed_extension', $allowed);
            $tpl->tplSetNeeded('/allowed_extension');
        }
        
        
        if($manager->getSetting('contact_quick_responce')) {
            //xajax
            $extra_js = array();    
            $str = '<script src="%s/OnKeyRequestBuffer.js"></script>';
            $extra_js[] = sprintf($str, $this->controller->kb_path . 'client/jscript/xajax_js');

            $str = '<script src="%s/spiner.js"></script>';
            $extra_js[] = sprintf($str, $this->controller->kb_path . 'client/jscript/xajax_js');

            $ajax = &$this->getAjax('search');
            $xajax = &$ajax->getAjax($manager);
            $xajax->extra_js = implode("\n", $extra_js);
            $xajax->registerFunction(array('requestBuffer', $ajax, 'ajaxGetQuickResponce'));
            
            $tpl->tplAssign('onkeyup_action', 'OnKeyRequestBuffer.modified(this.id);');
            
        } else {
            $tpl->tplAssign('onkeyup_action', 'return false;');
        }    
    
    
        // custom 
        $this->parseCustomFieldBlocks($tpl, $manager);
        
        
        if(!$manager->is_registered) {
            $tpl->tplSetNeeded('/not_registered');
        }
        
        if($this->useCaptcha($manager, 'contact')) {
            $tpl->tplAssign('captcha_block', $this->getCaptchaBlock($manager, 'contact'));
        }
        
        $ajax = &$this->getAjax('validate');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('validate', $ajax, 'ajaxValidateForm'));
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function parseCustomFieldBlocks(&$tpl, $manager) {
        
        if(!AppPlugin::isPlugin('fields')) {
            return;
        }
        
        $crows = $manager->cf_manager->getCustomField();
        $cvalues = $this->getFormDataByKey('custom');
        
        $options = array(
            'use_default' => (empty($_POST))
        );
        
        $options['style_select'] = 'width: 250px;';
        $options['style_text'] = 'width: 500px;';
        $options['style_textarea'] = 'width: 500px;';
        
        $inputs = CommonCustomFieldView::getCustomFields($crows, $cvalues, $manager->cf_manager, $options);
        $fd = $this->getFormData();
                       
        foreach($crows as $id => $field) {
            $field['id'] = $id;
            $field['input'] = $inputs[$id];
            
            $field['required_class'] = '';
            if ($field['is_required']) {
                $field['required_class'] = $fd['required_class'];
            }
            
            if ($field['tooltip']) {
                $tpl->tplSetNeeded('custom_row/tooltip');
            }
            
            $field['tooltip'] = htmlentities(nl2br($field['tooltip']));
             
            $tpl->tplParse($field, 'custom_row');
        }        
    }
     
    
    function validate($values, $manager) {
        $v = new Validator($values, false);
        $v->csrfCookie();
        
        // need to check if not registered
        if(!$manager->is_registered) {
            $v->required('required_msg', array('title', 'message', 'email'));
            $v->regex('email_msg', 'email', 'email');
        } else {
            $v->required('required_msg', array('title', 'message'));
        }

        if($v->getErrors()) {
            return $v->getErrors();
        } 
        
        // custom
        if(AppPlugin::isPlugin('fields')) {
            $fields = $manager->cf_manager->getCustomField();
            $error = $manager->cf_manager->validate($fields, $values);
            if($error) {
                $v->setError($error[0], $error[1], $error[2], $error[3]);
                return $v->getErrors();
            }
        }
        
        if($error = $this->validateCaptcha($manager, $values, 'contact')) {
            $v->setError($error[0], $error[1], $error[2], $error[3]);
            return $v->getErrors();
        }
        
        
        // valodate file
        if(!empty($values['_files'])) {
            
            $upload = new Uploader;    
            $upload->setAllowedExtension($this->attachment_allowed);
            //$upload->setDeniedExtension($this->setting['file_denied_extensions']);
            $upload->setMaxSize($this->attachment_max_filesize);
            $upload->setUploadedDir($this->attachment_dir);
            
            $errors = $upload->validate($values['_files']);
            
            if(!empty($errors)) {
    			$v = new Validator($values, true);
                $error_msg = Uploader::getErrorText($errors);
                $msg = implode('<br/>', $error_msg);
                // echo '<pre>', print_r($error_msg,1), '</pre>';
                // foreach($error_msg as $msg) {
    				$v->setError($msg, 'attachments', 'attachments', 'custom');
                // }
            }   
        }
        
        return $v->getErrors();
    }
    
    
    function isSpam($values) {
        if(!empty($values['s_company']) || !empty($values['s_company_set'])) {
            return true;
        }
        
        // if(!$this->spamCheckCleantalk($values)) {
            // return true;
        // }
    }
    
    
    // not implemented in settings 
    // commanted in isSpam
/*
    
require_once 'php-antispam/lib/Cleantalk.php';
require_once 'php-antispam/lib/CleantalkRequest.php';
require_once 'php-antispam/lib/CleantalkResponse.php';
require_once 'php-antispam/lib/CleantalkHelper.php';

use lib\CleantalkRequest;
use lib\Cleantalk;
use lib\CleantalkHelper;
    
    function spamCheckCleantalk($options) {
    
        $server_url = 'https://moderate.cleantalk.org';
        $access_key = 'yza2epa2ejyh';
    
        $ct_request = new CleantalkRequest(); 
        $ct_request->auth_key = $access_key; 
        $ct_request->agent = 'php-api'; 
        $ct_request->sender_email = $options['email']; 
        $ct_request->sender_ip = WebUtil::getIP();
        $ct_request->sender_nickname = $options['name']; 
        // $ct_request->submit_time = time() - (int) $options['time'];
        $ct_request->message = $options['message']; 
        $ct_request->js_on = 1; 
    
        $ct = new Cleantalk(); 
        $ct->server_url = $server_url;
        $ct_result = $ct->isAllowMessage($ct_request);

        $ret = $ct_result->allow;

        if(!$ret) {
            $loptions['f'] = array(
                'type' => 'file', 
                'filename' => APP_CACHE_DIR . 'contact_spam_check'
            );
            
            $msg = print_r($ct_result, 1);
            $msg .= print_r($options, 1);
            
            $log = new LogUtil($loptions);
            $log->put('f', $msg);
        }
        
        return $ret; 
    }*/

}
?>