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
       

$rq = new RequestData($_GET);
$rp = new RequestData($_POST);
$rp->setHtmlValues('message');

$obj = new Notification;

$manager =& $obj->setManager(new NotificationModel());

// skip check priv for account
// add this to not strip  actions/buttons and allow bulk
$priv->skip_check_priv = true;
//$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete': // ------------------------------
    $manager->delete($rq->id);
    $controller->go();

    break;
    
case 'detail': // --------------------------------
     
    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);
    
    $manager->mark(0, $rq->id);
    
    $view = $controller->getView($obj, $manager, 'NotificationView_detail', $data);

    break;

case 'read': // ------------------------------
    $manager->mark(0, $rq->id);
    $controller->go();

    break;
    
case 'unread': // ------------------------------
    $manager->mark(1, $rq->id);
    $controller->go();

    break;

case 'mark_all': // ------------------------------
    $manager->mark(0);
    $controller->go();

    break;

case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();
        
        $ids = array_map('addslashes', $rp->id);
        $action = $rp->bulk_action;

        $bulk_manager = new NotificationModelBulk();
        $bulk_manager->setManager($manager);
        
        if($bulk_manager->validate($rp->vars)) {
            $controller->go('csrf');
        }

        switch ($action) {
        case 'delete': // ------------------------------
            $manager->delete($ids);
            break;

        case 'read': // ------------------------------
            $manager->mark(0, $ids);
            break;
            
        case 'unread': // ------------------------------
            $manager->mark(1, $ids);
            break;
            
        }

        $controller->go();
    }

    $controller->goPage('main');

    break;
    
default: // ------------------------------------
    
    $view = new NotificationView_list();
    $view = $view->execute($obj, $manager);
    break;
}
?>