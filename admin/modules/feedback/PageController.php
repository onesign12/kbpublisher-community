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

require_once 'eleontev/Dir/mime_content_type.php'; 


$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);
//$rp->setHtmlValues('body'); // to skip $_GET['body'] not strip html

$obj = new Feedback;

$manager =& $obj->setManager(new FeedbackModel());
$manager->checkPriv($priv, $controller->action, @$rq->id, $controller->getMoreParam('popup'), @$rp->bulk_action);

$manager->cf_manager = new CommonCustomFieldModel($manager);

// settings
$setting = SettingModel::getQuick(1);
$manager->setting = &$setting;


switch ($controller->action) {
case 'delete': // ------------------------------
     
    $manager->delete($rq->id);
    $controller->go();

    break;
    
    
case 'answer_status': // ------------------------------
     
    $manager->status($rq->status, $rq->id, 'answered');
    $controller->go();

    break;    
    
    
case 'place_status': // ------------------------------
     
    $manager->status($rq->status, $rq->id, 'placed');
    $controller->go();

    break;
    
    
case 'detail': // ------------------------------

    if(!empty($rq->id)) {

        $data = $manager->getById($rq->id);
        if($data) {
            $rp->stripVarsValues($data);
            $obj->set($data);
            $obj->setCustom($manager->cf_manager->getCustomDataById($rq->id));
        }
    }

    $view = $controller->getView($obj, $manager, 'FeedbackView_detail', $data);
    break;
        
    
case 'file': // --------------------------------
case 'file_download': // --------------------------------

    $data = $manager->getById($rq->id);    
    $file_num = isset($rq->f) ? $rq->f : false;
    $attachment = ($controller->action == 'file') ? false : true;
    
    $manager->sendFileDownload($data, $rq->type, $file_num, $attachment);
    exit;

    break;
    

case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();

        $ids = array_map('intval', $rp->id);
        $action = $rp->bulk_action;

        $bulk_manager = new FeedbackModelBulk();
        $bulk_manager->setManager($manager);
        
        if($bulk_manager->validate($rp->vars)) {
            $controller->go('csrf');
        }

        switch ($action) {
        case 'delete': // ------------------------------
            $manager->delete($ids);
            break;        

        case 'answer_status': // ------------------------------
            $manager->status($rp->value['answer_status'], $ids, 'answered');
            $bulk_manager->updateSphinxAttributes('answered', $rp->value['answer_status'], $ids);
            break;

        case 'place_status': // ------------------------------
            $manager->status($rp->value['place_status'], $ids, 'placed');
            $bulk_manager->updateSphinxAttributes('placed', $rp->value['place_status'], $ids);
            break;             
        }

        $controller->go();
    }

    $controller->goPage('main');

    break;    
        
    
case 'answer': // ------------------------------

    $data = array();
    
    if(isset($rp->submit)) {
        
        $is_error = $obj->validate($rp->vars);
        
        if(!$is_error) {
            if($_FILES['attachment_1']['name']) {
                $upload = $manager->upload();
            }
            
            if(!empty($upload['error_msg'])) {
                $obj->errors['formatted'][]['msg'] = $upload['error_msg'];
                $is_error = true;
            }
        }
        
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            $obj->setCustom($manager->cf_manager->getCustomDataById($rq->id));

            $data = &$rp->vars;
        
        } else {
            
            // attachment
            $files = array();
            if (isset($upload)) {
                foreach ($upload['good'] as $k => $v) {
                    $files[] = $setting['file_dir'] . 'contact_attachment/' . $v['name'];
                }
            }
            
            if (!empty($rp->answer_attachment)) { // from files
                
                
                
                $emanager = new FileEntryModel_dir;
                $emanager->setSqlParams(sprintf('AND e.id IN(%s)', implode(',', $rp->answer_attachment)));
                
                $attachments = $emanager->getRecords();
                
                foreach ($attachments as $attachment) {
                    $fname = (!empty($attachment['filename_disk'])) ? $attachment['filename_disk'] : $attachment['filename'];
                    $files[] = $attachment['directory'] . $fname;
                }
            }
            
            $rp->stripVars('stripslashes');
            $file = ($files) ? implode(';', $files) : false;
            $sent = $manager->sendContactAnswer($rp->vars, $file);

            if(!$sent) {
                $rp->stripVars(true);
                $obj->set($rp->vars);    
                $obj->setError('email_not_sent');
                $data = &$rp->vars;
                
                $manager->removeUploaded($file);
            
            } else {
                $rp->stripVars('addslashes');
                $obj->set($rp->vars);
                $obj->set('answer_attachment', $file);

                $manager->setEntryAnswered($rq->id, $obj->get());
                $controller->go();                
            }
        }
    
    } else {
        
        $data = $manager->getById($rq->id);
        
        if(!$data) {
            $controller->go('record_not_exists', true);
        }
        
        $data = $rp->stripVarsValues($data);
        $obj->set($data);
        $obj->setCustom($manager->cf_manager->getCustomDataById($rq->id));
    }
    
    $view = $controller->getView($obj, $manager, 'FeedbackView_form', $data);

    break;


default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'FeedbackView_list');
}
?>