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

$obj = new Tag;
$manager =& $obj->setManager(new TagModel());

$priv->setCustomAction('tag', 'select'); 
$priv->setCustomAction('ref_remove', 'delete'); 
$manager->checkPriv($priv, $controller->action, @$rq->id);

$controller->setMoreParams('show_msg2');

switch ($controller->action) {
case 'delete': // ------------------------------
    
    // has related but ignoring it
    if(isset($rp->submit)) {
    	$manager->delete($rq->id);
    	$manager->addTagSyncTask($rq->id, 1);
		$controller->go();
    }
    
    // no related
    $related = $manager->getReferencedEntriesNum($rq->id);
    if(!$related) {
    	$manager->delete($rq->id);
		$controller->go();        
    }
    
    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);

    $view = $controller->getView($obj, $manager, 'TagView_delete', $related);

    break;
    
        
case 'status': // ------------------------------

    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;    
    
    
case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();

        $ids = array_map('intval', $rp->id);
        $action = $rp->bulk_action;

        $bulk_manager = new TagModelBulk();
        $bulk_manager->setManager($manager);
        
        if($bulk_manager->validate($rp->vars)) {
            $controller->go('csrf');
        }

        switch ($action) {
        case 'delete': // ------------------------------
            
            $force = (!empty($rp->value['delete_force']));
            $ref = $bulk_manager->getReferences($ids);
            
            if($force) {
                $bulk_manager->delete($ids);
                if($ref['taken']) {
                    $manager->addTagSyncTask($ref['taken'], 1);
                }
                
                // not to show msg and filter results if force 
                $controller->removeMoreParams(array('show_msg2','filter'));
                
            } else {
                if($ref['free']) {
                    $bulk_manager->delete($ref['free']);
                }
                
                if($ref['taken']) {
                    $f = implode(',', $ref['taken']);
                    $more = array('filter[q]'=>$f, 'show_msg2'=>'note_remove_tag_bulk');
                    $controller->goPage('this', 'this', false, false, $more);
                }
            }            
			
            break;
            
        case 'status': // ------------------------------
            $manager->status($rp->value['status'], $ids);
            $bulk_manager->updateSphinxAttributes('active', $rp->value['status'], implode(',', $ids));
            break;            
        }

        $controller->go();
    }

    $controller->goPage('main');

    break;    
    
    
case 'update': // ------------------------------
case 'insert': // ------------------------------

    if(isset($rp->submit) || isset($rp->submit_new)) {
        
        $is_error = $obj->validate($rp->vars, $manager);
                
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
        
        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
            
            $manager->save($obj);
            
            if($controller->action == 'update') {
                if($manager->isInUse($obj->get('id'))) {
                    $manager->addTagSyncTask($obj->get('id'), 2);
                }
            }
            
            $controller->go();
        }
        
        
    } elseif($controller->action == 'update') {
    
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        $obj->set($data);    
    }
    

    $view = $controller->getView($obj, $manager, 'TagView_form'); 

    break;


default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'TagView_list');
}
?>