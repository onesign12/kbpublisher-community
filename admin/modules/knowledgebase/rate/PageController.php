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

$obj = new KBRate;

$manager =& $obj->setManager(new KBRateModel());

$priv->setPrivArea('kb_rate');
$manager->checkPriv($priv, $controller->action, @$rq->id, $controller->getMoreParam('popup'), @$rp->bulk_action);

$controller->setMoreParams('entry_id');


switch ($controller->action) {
case 'delete': // ------------------------------
    
    $manager->delete($rq->id);
    $controller->go();

    break;
    
case 'delete_entry': // ------------------------

    $manager->deleteByEntryId($rq->entry_id);
    $controller->go();

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

        $bulk_manager = new KBRateModelBulk();
        $bulk_manager->setManager($manager);
        
        if($bulk_manager->validate($rp->vars)) {
            $controller->go('csrf');
        }

        switch ($action) {
        case 'delete': // ------------------------------
            $manager->delete($ids);
            break;        

        case 'status': // ------------------------------
            $manager->status($rp->value['status'], $ids);
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
    
    
    $view = $controller->getView($obj, $manager, 'KBRateView_form', $data);

    break;
    

default: // ------------------------------------
    
    if(isset($rq->entry_id)) {
        $obj->set('entry_id', $rq->entry_id);
        $view = $controller->getView($obj, $manager, 'KBRateView_list_entry');
    
    } else {
        $view = $controller->getView($obj, $manager, 'KBRateView_list');
    }
}
?>