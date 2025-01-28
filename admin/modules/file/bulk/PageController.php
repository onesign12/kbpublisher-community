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


$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);
$controller->rp = &$rp;


$obj = new BulkFileEntry;
$manager = new FileEntryModel_dir;
$manager->s3_manager = new FileEntryModel_s3;

$draft_manager = new FileDraftModel;
$draft_action = new FileDraftAction($rq, $rp);

// settings
$setting = SettingModel::getQuick(array(1, 12));
$setting = $manager->setFileSetting($setting);
$manager->s3_manager->setting = $setting;

// $manager->checkPriv($priv, 'insert'); // this from FileEntryModel
$priv->check('insert');

switch ($controller->action) {
	
case 'tags': // ------------------------------
    
    $view = new KBEntryView_tags;
    $view = $view->execute($obj, $manager);

    break;
	

case 'category': // ------------------------------
	
    $view = new FileEntryView_category;
    $view = $view->execute($obj, $manager);

    break;


default: // ------------------------------------
    
    $s3 = (isset($_POST['s3_type']));
    $foptions = array();
    if($s3) {
        $manager = $manager->s3_manager;
    }
    
	$files = array();
    
    if(isset($rp->submit) || isset($rp->submit_draft) || isset($rp->submit_approve)) {
        $is_error =$obj->validate($rp->vars, 'action',  $manager);
    
        if($is_error) {
            $rp->stripVars(true);
            $obj->populate($rp->vars, $manager, true);
    
        } else {
    
            @set_time_limit(120);
            ignore_user_abort();
            
            $rp->stripVars();
            $obj->populate($rp->vars, $manager);
    
            foreach($rp->files as $k => $file) {
                
                $upload = $manager->getFileData($file, $rp->foptions[$k]);
                $content = $manager->getFileContent($upload['to_read']);
                
                if($content) {
    
                    $file_id = $manager->saveFileData($content);
                    
                    // add file 
                    if (isset($rp->submit)) {
                        
                        $obj->populateFile($upload, $manager);
                        
                        $entry_id = $manager->save($obj, 'insert', true);
                        // $manager->setS3ParseTask($entry_id, $status_save, 2);
                        
                        $module = 'file_entry';
                        
                    // add as draft
                    } else {
                        
                        $obj->populateFile($upload, $manager, false);
                        
                        $draft_obj = new FileDraft;
                        
                        // make FileEntry object
                        $eobj = new FileEntry;
                        $vars = get_object_vars($obj);
                        foreach($vars as $name => $value) {
                            $eobj->$name = $value;
                        }
                        
                        $draft_obj->populate($rp->vars, $eobj, $manager);                        
                        
                        $draft_id = $draft_manager->save($draft_obj);
                        // $manager->setS3ParseTask($draft_id, $status_save, 8);
                        
                        if ($draft_id) {
                            $draft_obj->set('id', $draft_id);
                        }
                            
                        if (isset($rp->submit_approve)) {
                            $workflow = $draft_manager->getAppliedWorkflow();
                            $draft_action->submitForApproval($obj, $manager, $draft_obj, $draft_manager, $controller, $workflow);
                        }
                        
                        $module = 'file_draft';
                    }
                    
                }
            }
    
            $return = $controller->getLink('file', $module);
            $controller->setCustomPageToReturn($return, false);
            $controller->go();
        }
    
    } else {
    
        $obj->setAuthor($manager->getUser(AuthPriv::getUserId()));
    
        $status = ListValueModel::getListDefaultEntry('file_status');
        $status = ($status !== null) ? $status : $obj->get('active');
        $obj->set('active', $status);
    }
    
    $data = array('draft_manager' => $draft_manager);
    $view = $controller->getView($obj, $manager, 'BulkFileEntryView_form', $data);
}

?>