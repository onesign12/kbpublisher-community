<?php

class KBEntryAction extends AppAction
{

    function lock($obj, $manager, $controller) {
        
        $view = new KBEntryView_lock;
        
        if(isset($this->rp->submit)) {
            $manager->setEntryReleased($this->rq->id);
            $more = array('id'=>$this->rq->id);
            if(!empty($this->rq->referer)) {
                $more['referer'] = $this->rq->referer;
            }

            // on submit return to emode
            if(!empty($this->rq->referer) && $this->rq->referer == 'emode') {
                $view->entry_released = true;
                
            } else {
                $action = (isset($this->rq->back)) ? $this->rq->back : 'update';
                $controller->goPage('this', 'this', false, $action, $more);
            }
        }

        $data = $manager->getById($this->rq->id);
        $this->rp->stripVarsValues($data);
        $obj->set($data);
        
        $view = $view->execute($obj, $manager);

        return $view;
    }
    
    
    function autosave($obj, $manager, $controller) {
        
        $view = new KBEntryView_autosave;
        
        if(isset($this->rp->submit)) {

            $manager->deleteAutosave($this->rq->id);
            $more = array('id'=>$this->rq->id);
            if(!empty($this->rq->referer)) {
                $more['referer'] = $this->rq->referer;
            }

            // on submit return to emode
            if(!empty($this->rq->referer) && $this->rq->referer == 'emode') {
                $view->autosave_skipped = true;
            
            } else {
                $controller->goPage('this', 'this', false, 'update', $more);
            }
        }

        $data = $manager->getById($this->rq->id);
        $this->rp->stripVarsValues($data);
        $obj->set($data);
        $obj->set('date_updated', $data['date_updated']);
        
        $view = $view->execute($obj, $manager);
        
        return $view;
    }
    
    
    function draftRemove($obj, $manager, $controller, $priv) {
        
        if ($controller->module == 'knowledgebase') {
                
            $emanager = new KBDraftModel();
            $priv->setPrivArea('kb_draft');
            
        } else {
            
            $emanager = new FileDraftModel();
            $priv->setPrivArea('file_draft');
        }
        
        $data = $emanager->getByEntryId($this->rq->id, $manager->entry_type);
        $this->rp->stripVarsValues($data);
        
        $draft_id = $data['id'];
        
        if(isset($this->rp->submit)) {
            $emanager->checkPriv($priv, 'delete', $draft_id, $controller, $manager);
            $emanager->delete($draft_id);
            
            $more = array('id' => $this->rq->id);
            $controller->goPage('this', 'this', false, 'delete', $more);
        }
        
        $priv->use_exit_screen = false;
        $allowed = $emanager->checkPriv($priv, 'delete', $draft_id, $controller, $manager);
        
        $eobj = new KBDraft;
        $eobj->set($data);
        $eobj->set('date_updated', $data['date_updated']);
        
        // ongoing approval
        $last_event = $emanager->getLastApprovalEvent($eobj->get('id'));        
        if ($emanager->isBeingApproved($last_event)) {
            $eobj->sent_to_approval = true;
        }

        $view = new KBDraftView_note_delete_entry;
        $view = $view->execute($eobj, $emanager, $allowed);
        
        return $view;
    }
    
    
    function convert($obj, $manager, $controller) {
    
        if(!empty($_FILES['file']['name'])) {
            
            $ws = new FileToHtmlWebService;
            //$ws->ssl = true;
            
            $reg = Registry::instance();
            $conf = $reg->getEntry('conf');
            $ws->api_url = $conf['web_service_url'];
            
            $status = $ws->isFileConvertible($_FILES['file']);
            
            $data = array();
            if (!empty($status['error'])) {
                $data['error'] = $status['error'];
    
            } else {
                $response = $ws->sendFile($_FILES['file']['tmp_name'], $_FILES['file']['name']);
                $result = $ws->parseResponse($response);
    
                if (!empty($result['error'])) {
                    $data['error'] = $result['error'];
    
                } else {
                    $data = array('content' => $result['content']);
                }
            }
            
            echo json_encode($data);
            exit;
        }
        
        
        $view = new KBEntryView_convert;
        $view = $view->execute($obj, $manager);
        
        return $view;
    }


    function attachment($obj, $manager, $controller) {
        
        if(!empty($_FILES['file']['name'])) {

            $f_obj = new FileEntry;
            $f_obj->set('date_posted', null);
            $f_obj->set('author_id', null);
            $f_obj->set('updater_id', null);
            $f_obj->setSortValues(array(1 => 'sort_end'));

            $f_manager = new FileEntryModel_dir;
            $setting = SettingModel::getQuick(1);
            $setting = $f_manager->setFileSetting($setting);

            // category
            $category_id = $manager->getAttachmentCategory();
            if(!$category_id) {
                $fc_obj = new FileCategory;
                $category_id = $manager->createAttachmentCategory($fc_obj, $f_manager->cat_manager);
            }
            
            $f_obj->setCategory(array($category_id));
            
            $upload = $f_manager->upload(true, $_FILES['file']);
            
            $data = array();
            if(!empty($upload['error_msg'])) {
                header('Error: ' . $upload['error_msg'], true, 500);
                exit;

            } else {
                $content = $f_manager->getFileContent($upload['good'][1]['to_read']);
                if($content) {
                    $f_obj->populateFile($upload['good'][1], $f_manager);
                    
                    $entry_id = $f_manager->save($f_obj, 'insert', true);
                    $data = array('id' => $entry_id, 'name' => $f_obj->get('filename'));
                }
            }

            echo json_encode($data);
            exit;
        }
        
        $view = new KBEntryView_attachment;
        $view = $view->execute($obj, $manager);
        
        return $view;
    }
    
    
    function createDraftFromEntry($obj, $manager, $controller, $entry_id) {
        
        if($draft_id = $manager->isEntryDrafted($entry_id)) {
            $rlink = $controller->getCommonLink();
            $referer = WebUtil::serialize_url($rlink);
            $more = array('id' => $entry_id, 'referer' => $referer);

            $controller->goPage('this', 'this', false, 'draft_remove', $more);
        }
        
        if ($controller->module == 'knowledgebase') {
            $draft_obj = new KBDraft;
            $draft_manager = new KBDraftModel;
            
        } else {
            $draft_obj = new FileDraft;
            $draft_manager = new FileDraftModel;
        }

        $data = $manager->getById($entry_id);
        $this->rp->stripVarsValues($data, 'addslashes');

        $obj->collect($entry_id, $data, $manager, 'save');
        $obj->set('id', null);
        $obj->set('author_id', null);
        $obj->set('date_posted', null);
        $obj->unsetProperties('date_updated'); // to have current date_updated

        $draft_obj->populate($data, $obj, $manager);
        $draft_obj->set('entry_id', 0);
        $draft_id = $draft_manager->save($draft_obj);
        
        return $draft_id;
    }

}
?>