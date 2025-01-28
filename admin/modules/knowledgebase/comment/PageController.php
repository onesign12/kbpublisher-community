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


$rq = new RequestData($_GET, array('id', 'entry_id'));
$rp = new RequestData($_POST);

$obj = new KBComment;

$manager =& $obj->setManager(new KBCommentModel());

$priv->setPrivArea('kb_comment');
$manager->checkPriv($priv, $controller->action, @$rq->id, $controller->getMoreParam('popup'), @$rp->bulk_action);

$controller->setMoreParams('entry_id');


switch ($controller->action) {
case 'delete': // ------------------------------
    
    $entry_id = $manager->getEntryIdById($rq->id);
    $manager->delete($rq->id);
    $manager->updateCommentDateForEntry($entry_id);
    $controller->go();

    break;
    
case 'delete_entry': // ------------------------

    $manager->deleteByEntryId($rq->entry_id);
    $manager->updateCommentDateForEntry($rq->entry_id, NULL);
    $controller->go();

    break;    
    

case 'status': // ------------------------------
    
    $entry_id = $manager->getEntryIdById($rq->id);
    $manager->status($rq->status, $rq->id);
    $manager->updateCommentDateForEntry($entry_id);
    $controller->go();

    break;
    

case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();

        $ids = array_map('intval', $rp->id);
        $action = $rp->bulk_action;

        $entry_ids = array();
        foreach($ids as $comment_id) {
            $entry_ids[] = $rp->entry_id[$comment_id];
        }

        $bulk_manager = new KBCommentModelBulk();
        $bulk_manager->setManager($manager);
        
        if($bulk_manager->validate($rp->vars)) {
            $controller->go('csrf');
        }

        switch ($action) {
        case 'delete': // ------------------------------
            $manager->delete($ids);
            foreach($entry_ids as $entry_id) {
                $manager->updateCommentDateForEntry($entry_id);
            }
            
            break;        

        case 'status': // ------------------------------
            $manager->status($rp->value['status'], $ids);
            foreach($entry_ids as $entry_id) {
                $manager->updateCommentDateForEntry($entry_id);
            }
            
            $bulk_manager->updateSphinxAttributes('active', $rp->value['status'], implode(',', $ids));

            break;          
        }

        if(!empty($rq->referer)) {
            $controller->setCustomPageToReturn($rq->referer);
        }

        $controller->go();
    }

    $controller->goPage('main');

    break;    
    
    
case 'update': // ------------------------------
case 'insert': // ------------------------------
    
    $data = array();
    if(isset($rp->submit)) {
        
        $is_error = $obj->validate($rp->vars);
        
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            $data = $rp->vars;
        
        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
        
            $manager->save($obj);
            $manager->updateCommentDateForEntry($obj->get('entry_id'));
            
            $controller->go();
        }
        
        
    } elseif($controller->action == 'update') {
    
        $data = $manager->getById($rq->id);
        
        if(!$data) {
            $controller->go('record_not_exists', true);
        }        
        
        $rp->stripVarsValues($data);
        $obj->set($data);    
    
    } else {

        $data = $manager->getArticleData($rq->entry_id);
        $rp->stripVarsValues($data);
        $obj->set('user_id', AuthPriv::getUserid());
        $obj->set('entry_id', $rq->entry_id);
    } 
    
    
    $view = $controller->getView($obj, $manager, 'KBCommentView_form', $data);

    break;
    

default: // ------------------------------------
    
    if(isset($rq->entry_id)) {
        $obj->set('entry_id', $rq->entry_id);
        $view = $controller->getView($obj, $manager, 'KBCommentView_list_entry');
    
    } else {
        $view = $controller->getView($obj, $manager, 'KBCommentView_list');
    }
}
?>