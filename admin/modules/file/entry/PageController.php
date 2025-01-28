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
$rp->setSkipKeys(array('schedule', 'schedule_on'));
$controller->rp = &$rp;


$obj = new FileEntry;
$action = new FileEntryAction($rq, $rp);
$manager = new FileEntryModel_dir;

// settings
$setting = SettingModel::getQuick(1);
$setting = $manager->setFileSetting($setting);
$manager->checkPriv($priv, $controller->action, @$rq->id, $controller->getMoreParam('popup'), @$rp->bulk_action);


switch ($controller->action) {

case 'delete': // ------------------------------

    // inline
    $as_related = $manager->getEntryToAttachment($rq->id, '2,3'); // inline and attached
    if($as_related) {
        $more = array('id'=>$rq->id, 'rtype'=>'remove');
        $more = array_merge($controller->getFullPageParams(), $more);
        $controller->goPage('file', 'file_entry', false, 'ref_remove', $more);
    }

    // attached only, could be safely removed from table attachment_to_entry
    if(!isset($rq->ignore_reference)) {
        $as_related = $manager->getEntryToAttachment($rq->id, '1'); // attached only
        if($as_related) {
            $more = array('id'=>$rq->id, 'rtype'=>'notice');
            $more = array_merge($controller->getFullPageParams(), $more);
            $controller->goPage('file', 'file_entry', false, 'ref_notice', $more);
        }
    }

    // draft
    if($draft_id = $manager->isEntryDrafted($rq->id)) {
        $rlink = $controller->getCommonLink();
        $referer = WebUtil::serialize_url($rlink);
        $more = array('id' => $rq->id, 'referer' => $referer);
        
        $controller->goPage('file', 'file_entry', false, 'draft_remove', $more);
    }

    
    $data = $manager->getById($rq->id);
    $obj->collect($rq->id, $data, $manager, 'save');
    
    $manager->trash($rq->id, $obj);
    
    // referer
    if(!empty($rq->referer)) {
        $link = $controller->getRefererLink($rq->referer);
        $controller->setCustomPageToReturn($link, false);
    }

    $controller->go('success', false, false, 'trash');

    break;


case 'ref_notice': // ------------------------------
case 'ref_remove': // ------------------------------

    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);

    $view = $controller->getView($obj, $manager, 'FileEntryView_reference', $controller->action);

    break;
    
    
case 'draft_remove': // ------------------------------
    
    $action = new KBEntryAction($rq, $rp);
    $view = $action->draftRemove($obj, $manager, $controller, $priv);
    
    break;
    
    
case 'edit_as_draft': // ------------------------------
    
    if(!$manager->getFileDir($manager->getFileDataById($rq->id))) {
        $view = $action->fileMissing($obj, $manager, $controller);
        return $view;
    }
    
    $more = ['entry_id' => $rq->id, 'referer' => @$rq->referer];
    $controller->goPage('this', 'file_draft', false, 'insert', $more);
    
    break;
    

case 'move_to_draft': // ------------------------------

    if(!$manager->getFileDir($manager->getFileDataById($rq->id))) {
        $view = $action->fileMissing($obj, $manager, $controller);
        return $view;
    }

    // inline
    $as_related = $manager->getEntryToAttachment($rq->id, '2,3'); // inline and attached
    if($as_related) {
        $more = array('id' => $rq->id, 'rtype' => 'move_to_draft');
        $more = array_merge($controller->getFullPageParams(), $more);
        $controller->goPage('file', 'file_entry', false, 'ref_remove', $more);
    }

    // attached only, could be safely removed from table attachment_to_entry
    if(!isset($rq->ignore_reference)) {
        $as_related = $manager->getEntryToAttachment($rq->id, '1'); // attached only
        if($as_related) {
            $more = array('id'=>$rq->id, 'rtype'=>'move_to_draft');
            $more = array_merge($controller->getFullPageParams(), $more);
            $controller->goPage('file', 'file_entry', false, 'ref_notice', $more);
        }
    }
    
    
    $action = new KBEntryAction($rq, $rp);
    $draft_id = $action->createDraftFromEntry($obj, $manager, $controller, $rq->id);
    
    $manager->delete($rq->id, false); // false not to remove file
    
    $more = array('id' => $draft_id);
    $return = $controller->getLink('file', 'file_draft', false, 'update', $more);
    $controller->setCustomPageToReturn($return, false);
    $controller->go();
    
    break;
    

case 'status': // ------------------------------

    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;


case 'category': // ------------------------------

    $view = $controller->getView($obj, $manager, 'FileEntryView_category');

    break;


case 'role': // ------------------------------

    $view = new UserView_role_private();
    $view = $view->execute($obj, $manager);

    break;


case 'file': // ------------------------------

    $view = $action->sendFile($obj, $manager, $controller, true);
    break;
    
    
case 'fopen': // ------------------------------
case 'preview': // ----------------------------

    $view = $action->sendFile($obj, $manager, $controller, false);
    break;
    

case 'text': // ------------------------------

    $view = $action->fileText($obj, $manager, $controller);
    break;
    
    
case 'tags': // ------------------------------
    
    $view = new KBEntryView_tags;
    $view = $view->execute($obj, $manager);

    break;
    
    
case 'approval_log':
    
	$obj->set('id', $rq->id);
    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);
    
    $view = new KBEntryView_approval_log;
    $view = $view->execute($obj, $manager);
    
    break;
    
    
case 'history': // ------------------------------

    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);
    
    $h_obj = new FileEntryHistory;
    $h_manager = new FileEntryHistoryModel();
    
    $view = $controller->getView($h_obj, $h_manager, 'FileEntryHistoryView_list', array($data, $obj, $manager));

    break;


case 'diff': // ------------------------------

    $live_data = $manager->getById($rq->id);
    $rp->stripVarsValues($live_data);
    $obj->set($live_data);
    $obj->set('date_updated', $live_data['date_updated']);
    $obj->setAuthor($manager->getUser($live_data['author_id']));
    $obj->setUpdater($manager->getUser($live_data['updater_id']));

    $h_obj = new FileEntryHistory;
    $h_manager = new FileEntryHistoryModel();

    $left_rev = $h_manager->getHistoryById($rq->id, $rq->vnum);
    $vnum2 = (!empty($rq->vnum2)) ? $rq->vnum2 : $h_manager->getEntryMaxVersion($rq->id);
    $right_rev = $h_manager->getHistoryById($rq->id, $vnum2);
    if(!$left_rev || !$right_rev) {
        $more = array(
            'id'=>$rq->id, 'vnum' => $rq->vnum, 'vnum2' => $vnum2, 
            'show_msg'=>'error_get_history_version');
        $controller->goPage('this', 'this', false, 'history', $more);
    }
    
    // left revision
    $left_rev['entry_data'] = unserialize($left_rev['entry_data']);
    $rp->stripVarsValues($left_rev);
    
    // right revision
    $right_rev['entry_data'] = unserialize($right_rev['entry_data']);
    $rp->stripVarsValues($right_rev);

    // left-vnum2, right-vnum
    $revisions = array('left' => $left_rev, 'right' => $right_rev);
    
    $view = $controller->getView($h_obj, $h_manager, 'FileEntryHistoryView_diff', 
                                        array($revisions, $live_data, $obj, $manager));

    break;


case 'hdelete': // ------------------------------

    $h_obj = new FileEntryHistory;
    $h_manager = new FileEntryHistoryModel();
    $h_manager->setFileDirs($manager->getSetting('file_dir'));
    
    if(isset($rq->vnum)) {
        if($h_manager->isRevisionDeletable($rq->id, $rq->vnum)) {
            $h_manager->deleteRevision($rq->id, $rq->vnum);
            //$h_manager->normalizeRevisionsNumOnDelete($rq->id, $rq->vnum);
        }        
    } else {
        $h_manager->deleteRevisionAll($rq->id);
    }
    
    if($h_manager->countEntryRevisions($rq->id)) {
        $return = $controller->getActionLink('history', $rq->id);
    } else {
        $return = $controller->getLink('this', 'this');
    }
    
    $controller->setCustomPageToReturn($return, false);
    $controller->go();
    
    break;    


case 'hfile': // ------------------------------
case 'hfopen': // ------------------------------
    
    $h_obj = new FileEntryHistory;
    $h_manager = new FileEntryHistoryModel();

    $data = $h_manager->getHistoryById($rq->id, $rq->vnum);
    $fdata = $h_manager->getVersionData($rq->id, $rq->vnum);
    $attachment = ($controller->action == 'hfile');
    
    if($data['archived']) { // for local files only
        $fdata['file'] = $data['archived'];
    } else {
        $fdata['file'] = FileEntryUtil::getFilePath($fdata, '', true);
    }
    
    if(!file_exists($fdata['file'])) {
        $more = array('id'=>$rq->id, 'vnum'=>$rq->vnum, 
            'show_msg'=>'error_get_history_file', 'vars'=>['vnum'=>$rq->vnum]);
        $controller->goPage('this', 'this', false, 'history', $more);
    }
    
    // download two revisions to compare
    if(isset($rq->vnum2) && extension_loaded('zip')) {
        $data2 = $h_manager->getHistoryById($rq->id, $rq->vnum2);
        $fdata2 = $h_manager->getVersionData($rq->id, $rq->vnum2);
        
        if($data2['archived']) { // for local files only
            $fdata2['file'] = $data2['archived'];
        } else {
            $fdata2['file'] = FileEntryUtil::getFilePath($fdata2, '', true);
        }
    
        if(!file_exists($fdata2['file'])) {
            $more = array('id'=>$rq->id, 'vnum'=>$rq->vnum, 'vnum2'=>$rq->vnum2, 
                'show_msg'=>'error_get_history_file', 'vars'=>['vnum'=>$rq->vnum2]);
            $controller->goPage('this', 'this', false, 'history', $more);
        }
        
        $right_rev = $data;
        $right_rev['fdata'] = $fdata;
        
        $left_rev = $data2;
        $left_rev['fdata'] = $fdata2;
        
        $h_manager->sendFileDownload($left_rev, $right_rev);
        exit;
    }
    
    FileEntryUtil::sendFileDownload($fdata, 'file', $attachment);
    exit;
    
    break;
    

case 'rollback': // ------------------------------

    // lock
    // if($manager->isEntryLocked($rq->id)) {
    //     $extra = array('id'=>$rq->id, 'back'=>$controller->action);
    //     if(!empty($rq->referer)) {
    //         $extra['referer'] = $rq->referer;
    //     }
    // 
    //     $controller->goPage('this', 'this', false, 'lock', $extra);
    // }

    // draft
    if($draft_id = $manager->isEntryDrafted($rq->id)) {
        $rlink = $controller->getActionLink('history', $rq->id);
        $referer = WebUtil::serialize_url($rlink);
        $more = array('id' => $draft_id, 'referer' => $referer, 'vnum' => $rq->vnum);

        $controller->goPage('this', 'file_draft', false, 'entry_update', $more);
    }


    $h_manager = new FileEntryHistoryModel();
    $h_manager->setFileDirs($manager->getSetting('file_dir'));
    
    $data = $h_manager->getHistoryById($rq->id, $rq->vnum);
    $new_data = $h_manager->getVersionData($rq->id, $rq->vnum);
    
    if(!$new_data) {
        $more = array('id'=>$rq->id, 'vnum'=>$rq->vnum, 
            'show_msg'=>'error_get_history_version', 'vars'=>['vnum'=>$rq->vnum]);
        $controller->goPage('this', 'this', false, 'history', $more);
    }
    
    // check if file exists
    $rev_file = ($data['archived']) ? $data['archived'] : FileEntryUtil::getFilePath($new_data, '', true);
    
    if(!file_exists($rev_file)) {
        $more = array('id'=>$rq->id, 'vnum'=>$rq->vnum, 
            'show_msg'=>'error_get_history_file', 'vars'=>['vnum'=>$rq->vnum]);
        $controller->goPage('this', 'this', false, 'history', $more);
    }
    
    // rollback - publish
    if($data['archived']) { // need to copy file
        $file_to = $manager->getSetting('file_dir') . $new_data['filename'];
        $copy = $manager->copyFile($rev_file, $file_to);
        $new_data['directory'] = $manager->getSetting('file_dir');
        $new_data['filename_disk'] = $copy['new_filename'];
    }
    
    $new_data['filename_index'] = $manager->getFilenameIndex($new_data['filename']);

    $old_data = $manager->getById($rq->id);
    $new_data += $old_data;

    $rp->stripVarsValues($new_data, 'addslashes');
    $obj->set($new_data);
    $obj->set('updater_id', $manager->user_id);

    $entry_id = $manager->update($obj);
    
    // archive 
    if($allowed_rev = FileEntryModel::getHistoryAllowedRevisions()) {
    
        $msg = AppMsg::getMsg('common_msg.ini', 'knowledgebase');
        $new_data['history_comment'] = str_replace('{num}', $rq->vnum, $msg['rollback_comment_msg']);
        $new_data['updater_id'] = $manager->user_id;
    
        $history_data = $h_manager->compare($new_data, $old_data);
        $rev_data = $h_manager->parseData($rq->id, $history_data, $new_data, $old_data);
    
        $h_manager->addRevision($rq->id, RequestDataUtil::addslashes($rev_data));
        $h_manager->removeExtraRevisions($rq->id, $allowed_rev);
        $move = $h_manager->moveRevisionFile($rev_data, $manager);
    }

	$return = $controller->getActionLink('history', $rq->id);
    $controller->setCustomPageToReturn($return, false);
    $controller->go();
    break;
    
    
case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();

        $ids = array_map('intval', $rp->id);
        $action = $rp->bulk_action;

        $bulk_manager = new FileEntryModelBulk();
        $bulk_manager->setManager($manager);
        
        if($bulk_manager->validate($rp->vars)) {
            $controller->go('csrf');
        }
        
        // not allowed update entries with drafts
        $drafted_entries = $manager->getDraftedEntries($manager->idToString($ids)); 
        if(!empty($drafted_entries)) {
            $ids = array_diff($ids, $drafted_entries);
        }
        
        if (!empty($ids)) {
            
            switch ($action) {
            case 'trash': // ------------------------------
                $not_deleted = $bulk_manager->trash($ids);
                if($not_deleted) {
                    $f = implode(',', $not_deleted);
                    $more = array('filter[q]'=>$f, 'show_msg2'=>'note_remove_reference_bulk');
                    $controller->goPage('file', 'file_entry', false, false, $more);
                }
    
                break;
    
            case 'status': // ------------------------------
                $bulk_manager->status($rp->value['status'], $ids);
                break;
    
            case 'category_move': // -----------------------------
                $bulk_manager->setCategoryMove($rp->value['category'], $ids);
                break;
    
            case 'category_add': // -------------------------
                $bulk_manager->setCategoryAdd($rp->value['category'], $ids);
                break;
    
            case 'private': // ------------------------------
                $pr = (isset($rp->value['private'])) ? $rp->value['private'] : 0;
                $bulk_manager->setPrivate($rp->value, $pr, $ids);
                break;
    
            case 'public': // ------------------------------
                $bulk_manager->setPublic($ids);
                break;
    
            case 'schedule': // ------------------------------
                if($rp->value['schedule_action'] == 'set') {
                    $bulk_manager->setSchedule($rp->schedule_on, $rp->schedule, $ids);
                } else {
                    $bulk_manager->removeSchedule($ids);
                }
    
                break;
    
            case 'parse': // ------------------------------
                $bulk_manager->parse($rp->value['parse'], $ids);
                $bulk_manager->addSphinxRebuildTask($manager->entry_type);
                break;
    
            case 'hits_reset': // -----------------------
                $bulk_manager->resetHits($ids);
                break;    
    
            case 'tag': // ------------------------------
                $bulk_manager->setTags($rp->tag, $ids, $rp->value['tag_action']);
                break;
    
            case 'custom': // ------------------------------
                $bulk_manager->setCustomData($rp->value['custom'], $ids, $rp->value);
                break;
            }
        }
        
        if (!empty($drafted_entries)) {
            $f = implode(',', $drafted_entries);
            $more = array('filter[q]' => $f, 'show_msg2' => 'note_drafted_entries_bulk');
            $controller->goPage('file', 'file_entry', false, false, $more);
        }

        $controller->go();
    }

    $controller->goPage('main');

    break;


case 'detail': // ------------------------------

    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->collect($rq->id, $data, $manager, $controller->action);

    $view = $controller->getView($obj, $manager, 'FileEntryView_detail');
    break;
    

case 'clone': // ------------------------------
case 'update': // ------------------------------
case 'insert': // ------------------------------

    if(isset($rp->submit) || isset($rp->submit_attach)) {

        $is_error = $obj->validate($rp->vars, $controller->action, $manager);

        if($is_error) {
            $rp->stripVars(true);
            $obj->populate($rp->vars, $manager, true);

        } else {

            $rp->stripVars();
            $obj->populate($rp->vars, $manager);

            $files = array();
            foreach($_FILES as $file) {
                if (!empty($file['name'])) {
                    $files[] = $file;
                }
            }

            if (!empty($files)) {
                $errors = array();

                foreach ($files as $f) {

                    $rename_file = true;
                    $upload = $manager->upload($rename_file, $f);
                    
                    if(!empty($upload['error_msg'])) {
                        $rp->stripVars('stripslashes_display');
                        $obj->set($rp->vars);
                        $errors = array_merge_recursive($errors, $upload['error_msg']);

                    } else {

                        $content = $manager->getFileContent($upload['good'][1]['to_read']);
                        $obj->populateFile($upload['good'][1], $manager);
                        
                        if($content) {

                            // history
                            $history_data = false;
                            if($controller->action == 'update') {
                                $old_data = $manager->getById($rq->id);
                                $skip_history = (!empty($rp->history_skip) || !empty($rp->submit_skip));
                                
                                if(!$skip_history) {
                                    if($allowed_rev = FileEntryModel::getHistoryAllowedRevisions()) {
                                        $h_manager = new FileEntryHistoryModel();
                                        $h_manager->setFileDirs($manager->getSetting('file_dir'));
                                        
                                        $new_data = RequestDataUtil::stripslashes($obj->get());
                                        // $old_data = $manager->getById($rq->id);
                                        $history_data = $h_manager->compare($new_data, $old_data);
                                    }
                                }
                            }

                            // echo '<pre>' . print_r($old_data, 1) . '</pre>';
                            // echo '<pre>' . print_r($new_data, 1) . '</pre>';
                            // echo '<pre>' . print_r($history_data, 1) . '</pre>';
                            // exit;

                            $entry_id = $manager->save($obj, $controller->action, true);

                            // history
                            if($history_data) {
                                $new_data['history_comment'] = stripslashes($rp->history_comment);
                                $rev_data = $h_manager->parseData($entry_id, $history_data, $new_data, $old_data);

                                $h_manager->addRevision($entry_id, RequestDataUtil::addslashes($rev_data));
                                $h_manager->removeExtraRevisions($entry_id, $allowed_rev);
                                $move = $h_manager->moveRevisionFile($rev_data, $manager);
                            
                            // history skiiped by some reason check 
                            // new file already uploaded and saved in kb_file directory
                            } elseif($controller->action == 'update') {
                                if(!$controller->getMoreParam('do')) { // no live file = no delete
                                    $fdata = $manager->parseFilesData($old_data);
                                    if($skip_history) {
                                        $h_manager = new FileEntryHistoryModel();
                                        // $h_manager->setFileDirs($manager->getSetting('file_dir'));
                                        $rev_data = $h_manager->isRevisionFileLive($entry_id, $old_data);
                                        if(!$rev_data) {
                                            $manager->deleteFileData([$entry_id => $fdata]);
                                        }
                                    } else {
                                        $manager->deleteFileData([$entry_id => $fdata]);
                                    }
                                }
                            }

                            // uploaded file
                            $obj->success_files[] = $obj->get('filename');

                            // referer
                            if(!empty($rq->referer)) {
                                $link = $controller->getRefererLink($rq->referer, ['files', $obj->get('category_id')]);
                                $controller->setCustomPageToReturn($link, false);
                            }

                        } else { // --> if($content)
                            $obj->errors['key'][] = array('msg'=>'not_uploaded');
                        }
                    }
                }

                // all files were uploaded
                if (empty($errors)) {

                    if ($controller->getMoreParam('popup') == 1 && isset($rp->submit_attach)) {
                        $_GET['attach_id'] = $entry_id;
                        $controller->setMoreParams('attach_id');
                    }

                    // $controller->go('success', false, $history_msg_key);
                    $controller->go('success', false);

                // some files were not uploaded
                } else {

                    // get error box for all files
                    $error_msg = Uploader::errorBox($errors);
                    if($error_msg) {
                        $obj->errors = Validator::parseError($error_msg, 'file', 'file', 'formatted');
                    }

                    if ($controller->action == 'insert') {
                        $_SESSION['formobj_'] = serialize($obj);
                        $more = array('formobj' => 1);
                        
                        if (!empty($rq->popup)) {
                            $more['popup'] = $rq->popup;
                        }
                        
                        $controller->goPage('this', 'this', false, 'this', $more);
                    }
                }


            } else { // no file - only if update possible

                //if not to change date updated
                //$data = $manager->getById($rq->id);
                //$obj->set('date_updated', $data['date_updated']);
                // $obj->unsetProperties('sub_directory');
                // echo '<pre>', print_r($obj->get(),1), '<pre>';

                $entry_id = $manager->save($obj, $controller->action, false);
                $obj->set('id', $entry_id);
                
                // referer
                if(!empty($rq->referer)) {
                    $link = $controller->getRefererLink($rq->referer, ['files', $obj->get('category_id')]);
                    $controller->setCustomPageToReturn($link, false);
                }

                $controller->go('success');
            }
        }

    } elseif(in_array($controller->action, array('update', 'clone'))) {

        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        
        $obj->collect($rq->id, $data, $manager, $controller->action);

    } elseif($controller->action == 'insert') {

        $status = ListValueModel::getListDefaultEntry('file_status');
        $status = ($status !== null) ? $status : $obj->get('active');
        $obj->set('active', $status);

        if(!empty($rq->filter['c']) && intval($rq->filter['c']) && $rq->filter['c'] != 'all') {
            $gfc = array($rq->filter['c']);
            if(!$manager->isCategoryNotInUserRole($gfc)) {
                $obj->setCategory($gfc);
            }
        }
    }


    // if redirected after upload error
    if (isset($rq->formobj) && !isset($rp->submit) && !empty($_SESSION['formobj_'])) {
        $obj = unserialize($_SESSION['formobj_']);
    }

    // in case post size exseeded
    if(isset($_SERVER['CONTENT_LENGTH'])) {
        if($post_max_size = Uploader::getIniValue('post_max_size')) {
            if($_SERVER['CONTENT_LENGTH'] > $post_max_size) {
                $msgs = AppMsg::getMsgs('error_msg.ini');
                $msg['title'] = $msgs['error_title_msg'];
                $msg['body'] = $msgs['post_max_size_msg'];
                
                $obj->errors = Validator::parseError($msg, 'file', 'file', 'parsed');
            }
        }
    }
    
    // drafts
    $actions = array('update');
    if(in_array($controller->action, $actions)  && !isset($rq->ajax)) {
        if(!isset($rp->submit) && !isset($rq->skip_draft)) {
            
            // draft
            if($draft_id = $manager->isEntryDrafted($rq->id)) {
                $rlink = $controller->getCommonLink();
                $referer = WebUtil::serialize_url($rlink);
                $more = array('id' => $draft_id, 'referer' => $referer);

                $controller->goPage('this', 'file_draft', false, 'entry_update', $more);
            }

        }
    }
    
    // if file does not exist
    $actions = array('update');
    if(in_array($controller->action, $actions) && !isset($rq->ajax) && !isset($rq->do)) {
        if(!$manager->getFileDir($obj->get())) {
            $view = $action->fileMissing($obj, $manager, $controller);
            return $view;
        }
    }
    
    
    $view = $controller->getView($obj, $manager, 'FileEntryView_form');

    break;


default: // ------------------------------------

    // sort order
    if(isset($rp->submit)) {
        $category_id = $rq->filter['c'];
        foreach ($rp->sort_id as $sort_value => $entry_id) {
            $manager->saveSortOrder($entry_id, $category_id, $sort_value);
        }
    }

    if(isset($_SESSION['formobj_'])) {
        $_SESSION['formobj_'] = array();
        unset($_SESSION['formobj_']);
    }

    $view = $controller->getView($obj, $manager, 'FileEntryView_list');
}
?>
