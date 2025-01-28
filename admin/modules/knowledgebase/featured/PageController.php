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

$obj = new KBFeaturedEntry;

$manager =& $obj->setManager(new KBFeaturedEntryModel());
$priv->setCustomAction('category', 'select');
$priv->setCustomAction('delete_category', 'delete');
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete': // ------------------------------
    
    $manager->deleteByEntryId($rq->id);
    $controller->go();

    break;
    
    
case 'category': // ------------------------------
    
    $view = new KBEntryView_category;
    
    $msg = AppMsg::getMsgs('common_msg.ini', 'knowledgebase');
    $msg2 = AppMsg::getMsgs('common_msg.ini', 'export');
    
    $msg = array_merge($msg, $msg2);
    
    $options = array(
        'sortable' => false,
        'non_active_state' => 'disabled',
        'creation' => false,
        'secondary_block' => false,
        'status_icon' => true,
        'cancel_button' => true,
        'mode' => 'entry',
        'select_id' => 'category',
        'handler_name' => 'selHandler',
        'popup_title' => $msg['assign_category_msg'],
        'main_title' => $msg['assigned_category_msg'],
        'msg' => array(
            'non_active_category_msg' => $msg['export_non_active_category_msg']
        )
    );
    
    $categories = $manager->emanager->getCategoryRecordsUser();  // private removed
    
    $view = $view->parseCategoryPopup($manager->emanager->cat_manager, $categories, $options);
    break;
    
    
case 'delete_category': // ------------------------------
    $data = $manager->getById($rq->id);
    $category_id = $data['category_id'];
    
    $manager->delete($rq->id);
    
    if (!$manager->getEntryCount($category_id)) {
        $return = $controller->getLink('knowledgebase', 'kb_featured');
        $controller->setCustomPageToReturn($return, false);
    }
    $controller->go();
    

    break;


case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();

        $ids = array_map('intval', $rp->id);
        $action = $rp->bulk_action;

        $bulk_manager = new KBFeaturedEntryModelBulk;
        $bulk_manager->setManager($manager);
        
        if($bulk_manager->validate($rp->vars)) {
            $controller->go('csrf');
        }

        switch ($action) {
        case 'remove': // ------------------------------
            $entry_ids = $manager->getEntryIds($ids);
            $manager->deleteByEntryId($entry_ids);
            break;
        }

        $controller->go();
    }

    $controller->goPage('main');

    break;
    

case 'insert': // ------------------------------
case 'update': // ------------------------------

    if(isset($rp->submit)) {
        $rp->stripVars(true);
        
        $obj->set('entry_type', $manager->entry_type);
        $obj->set('entry_id', $rq->id);
        
        $manager->deleteByEntryId($rq->id);
        
        $categories = $rp->category;
        
        if (!empty($rp->index_page)) {
            $categories[] = 0;
        }
        
        if (!empty($categories)) {
            $ids = implode(',', $categories);
            $manager->emanager->increaseFeaturedEntrySortOrder($ids);
            
            foreach ($categories as $category) {
                $obj->set('category_id', $category);
                $manager->save($obj);
            }
        }
        
        
        if($controller->getMoreParam('popup')) {
            $more = array('popup' => 3);
            $link = $controller->getLink('this', 'kb_entry', false, false, $more);
            $controller->setCustomPageToReturn($link, false);
        }
        
        $controller->go();
    }
    
    $view = $controller->getView($obj, $manager, 'KBFeaturedEntryView_form');
    break;
    

default: // ------------------------------------
    
    // sort order
    if(isset($rp->submit)) {
        $manager->saveSortOrder($rp->sort_id, $rp->lowest_sort_order);
    }
    
    $view = $controller->getView($obj, $manager, 'KBFeaturedEntryView_list');
}
?>