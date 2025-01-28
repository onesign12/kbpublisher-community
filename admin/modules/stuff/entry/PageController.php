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

                     
$obj = new StuffEntry;
$manager =& $obj->setManager( new StuffEntryModel );
                        
// settings
$setting = SettingModel::getQuick(1);
$manager->setFileSetting($setting);

$priv->setCustomAction('file', 'select');
$manager->checkPriv($priv, $controller->action, @$rq->id, $controller->getMoreParam('popup'), @$rp->bulk_action);


switch ($controller->action) {
case 'delete': // ------------------------------
    
    $manager->delete($rq->id);
    $controller->go();

    break;
        

case 'status': // ------------------------------
    
    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;
        
    
case 'update': // ------------------------------
case 'insert': // ------------------------------
                       
    if(isset($rp->submit)) {
        
        $is_error = $obj->validate($rp->vars, $controller->action);
   
        if($is_error) {
            $rp->stripVars(true);
            
            $obj->set($rp->vars);
            
            if(isset($rq->id)) {
                $data = $manager->getById($rq->id);
                $obj->setAuthor($manager->getUser($data['author_id']));
                
                $ddiff = strtotime($data['date_updated']) - strtotime($data['date_posted']);
                if($ddiff > $manager->update_diff) {
                    $obj->setUpdater($manager->getUser($data['updater_id']));                
                    $obj->set('date_updated', $data['date_updated']);
                }
            }    
        
        } else {
            
            $rp->stripVars();
            
            $obj->set($rp->vars);                        
                        
            // with file
            if($_FILES['file_1']['name']) {

                $upload = $manager->upload();
                
                if(!empty($upload['error_msg'])) {
                    $rp->stripVars('stripslashes_display');
                    $obj->set($rp->vars);
                    $obj->errors = Validator::parseError($upload['error_msg'], 'file', 'file', 'formatted');
                
                } else {
                    
                    $content = $manager->getFileContent($upload['good'][1]['to_read']);
                                              
                    if($content) {
                        
                        $obj->set('filename', addslashes($upload['good'][1]['name']));
                        $obj->set('filesize', $upload['good'][1]['size']);
                        $obj->set('filetype', addslashes($upload['good'][1]['type']));
                        $obj->set('filedata', addslashes($content)); 
                        
                        
                        $entry_id = $manager->save($obj, $controller->action);
                        $obj->set('id', $entry_id);
                        
                        $controller->go('success');
        
                    } else {
                        $obj->errors['key'][] = array('msg'=>'not_uploaded');
                    }
                }
            
            // no file - only if update possible
            } else {
                                
                $entry_id = $manager->save($obj, $controller->action, false);
                $obj->set('id', $entry_id);                                
                
                $controller->go('success', false);
            }
        }
        
    } elseif($controller->action == 'update') {
                                              
        $data = $manager->getById($rq->id);
        
        $rp->stripVarsValues($data);
        $obj->set($data);
        $obj->setAuthor($manager->getUser($data['author_id']));
        

        $ddiff = strtotime($data['date_updated']) - strtotime($data['date_posted']); 
        if($ddiff > $manager->update_diff) {
            $obj->setUpdater($manager->getUser($data['updater_id']));
            $obj->set('date_updated', $data['date_updated']);
        }
    }
    
    $view = $controller->getView($obj, $manager, 'StuffEntryView_form');

    break;    
    
    
case 'file': // ------------------------------
    
    $data = $manager->getById($rq->id);
    $manager->sendFileDownload($data);
    exit;
    
    break;

    
default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'StuffEntryView_list');
}
?>