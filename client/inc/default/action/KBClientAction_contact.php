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


class KBClientAction_contact extends KBClientAction_common
{

    var $attachment_dir;
    var $attachment_max_filesize;
    var $attachment_allowed;
    

    function &execute($controller, $manager) {
        
        // need to login
        if(!$manager->is_registered && $manager->getSetting('allow_contact') == 2) { 
            $controller->go('login', $this->category_id, $this->entry_id, 'contact'); 
        }
    
        $view = &$controller->getView();

        // attachment
        if($manager->getSetting('contact_attachment')) {
            $file_settings = SettingModel::getQuick(1);
            $this->attachment_dir = $file_settings['file_dir'] . 'contact_attachment/';
            $this->attachment_max_filesize = WebUtil::getIniSize('upload_max_filesize')/1024; // in kb
            
            $allowed = ($a = $manager->getSetting('contact_attachment_ext')) ? explode(',', $a) : array();
            $this->attachment_allowed = $allowed;
            
            $view->attachment_dir = $this->attachment_dir;
            $view->attachment_max_filesize = $this->attachment_max_filesize;
            $view->attachment_allowed = $this->attachment_allowed;
        }
        
        $manager->cf_manager = new CommonCustomFieldModel();
        $manager->cf_manager->etype = 20;
        $manager->cf_manager->etable = $manager->tbl->feedback_custom_data;
                
        if(isset($this->rp->submit)) {
            
            $errors = $view->validate($this->rp->vars, $manager);
            //$flood = $manager->isFlood();
                
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);
                
            } elseif($view->isSpam($this->rp->vars)) {
                $controller->go('success_go', $this->category_id, $this->entry_id, 'entry_sent');
            
            
            //} elseif($flood) {
            
            //    $rp->stripVars(true);
            //    $view->setFormData($rp->vars);
            //    $view->msg_id = 'flood_comment';
            
            } else {
            
                // with file
                $files = array();
                if(!empty($_FILES['file_1']['name']) && $manager->getSetting('contact_attachment')) {
                    
                    $upload = $this->upload($manager);
                    //echo "<pre>"; print_r($upload); echo "</pre>";
                    //exit;
    
                    if(!empty($upload['error_msg'])) {
                        $errors['formatted'][]['msg'] = $upload['error_msg'];
                        $view->setErrors($errors);
                        $view->setFormData($this->rp->vars);
                    
                    } else {
                        foreach($upload['good'] as $k => $v) {
                            $files[] = $this->attachment_dir . $v['name'];
                        }
                    }
                }
                
                if(!$errors) {

                    if(!empty($this->rp->vars['custom'])) {
						$custom_submited = $this->rp->vars['custom']; // use later if not sent
					}
                
                    $this->rp->vars['attachment'] = implode(';', $files);
                    $this->rp->setHtmlValues('attachment');
                    $this->rp->setSubstrValues(['name' => 100, 'email' => 100, 'title' => 255]);
                    $this->rp->stripVars();
                    
                    $message_id = $manager->addContactMessage($this->rp->vars);
                    
                    // custom data
                    if(!empty($this->rp->vars['custom'])) {
                        $custom = $this->rp->vars['custom'];
                        $manager->cf_manager->save($custom, $message_id);
                        
                        $file = $view->getMsgFile('common_msg.ini', false);
                        $msg = AppMsg::parseMsgs($file, false, false);
                        
                        $ch_value = array();
                        $ch_value['on'] = $msg['yes_msg'];
                        $ch_value['off'] = $msg['no_msg'];

                        $custom = CommonCustomFieldView::getCustomData($custom, $manager->cf_manager, $ch_value);
                        $custom_data = array();
                        foreach($custom as $k => $v) {
                            $custom_data[] = sprintf("%s: %s", $v['title'], $v['value']);
                        }
                        
                        $this->rp->vars['custom'] = implode("\n", $custom_data);
                    }
                    
                                                        
                    $this->rp->vars['attachment'] = $files;
                    $this->rp->stripVars('stripslashes');
                    $sent = $manager->sendContactNotification($message_id, $this->rp->vars);
                    
                    if($sent) {
                        $controller->go('success_go', $this->category_id, $this->entry_id, 'entry_sent');
                    
                    } else {
                        $manager->deleteContactMessage($message_id);
                        $this->removeUploaded($files);
                        
						if(!empty($custom_submited)) {
							$this->rp->vars['custom'] = $custom_submited; // to reset to submited
						}
						
                        $this->rp->stripVars(true);
                        $view->setFormData($this->rp->vars);
                        $view->msg_id = 'entry_not_sent';
                    }
                }
            }
        }
        
        return $view;
    }
    
    
    function upload($manager) {
            
        
        
        $upload = new Uploader;
        $upload->store_in_db = false;
        $upload->safe_name = false;
        $upload->safe_name_extensions = array();
        $upload->setRenameValues('date');
        
        $upload->setAllowedExtension($this->attachment_allowed);
        //$upload->setDeniedExtension($this->setting['file_denied_extensions']);
        $upload->setMaxSize($this->attachment_max_filesize);
        $upload->setUploadedDir($this->attachment_dir);
        
        $f = $upload->upload($_FILES);
    
        if(isset($f['bad'])) {
            $f['error_msg'] = $upload->errorBox($f['bad']);
        }

        return $f;
    }
    
    
    function removeUploaded($files) {
        foreach($files as $file) {
            @unlink($file);
        }
    }
}
?>