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

$obj = new UserList;

$manager =& $obj->setManager(new UserListModel());

// skip check priv for account
// add this to not strip  actions/buttons and allow bulk
$priv->skip_check_priv = true;
//$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete': // ------------------------------
    $entry_id = (int) $rq->id;
    $entry_type = (int) $rq->type;

    $manager->deleteEntry($entry_id, $entry_type);
    $controller->go();

    break;
    
case 'mail': // ------------------------------
    $entry_id = (int) $rq->id;
    $entry_type = (int) $rq->type;
    $value = (int) $rq->mail;
    
    $manager->emailStatus($entry_id, $entry_type, $value);
    
    $controller->go();
    
    break;

case 'preview': // ------------------------------

    $rp->setHtmlValues('body'); // to skip $_GET['body'] not strip html
    $rp->setCurlyBracesValues('body');
    $rp->setSkipKeys(array('schedule', 'schedule_on'));
    
    $obj = new KBEntry;
    $manager = new KBEntryModel();

    if(!empty($rq->id)) {

        $data = $manager->getById($rq->id);
        if($data) {
            $rp->stripVarsValues($data);
            $obj->set($data);
            $obj->setCustom($manager->cf_manager->getCustomDataById($rq->id));
        }
    }

    $view = new KBEntryView_preview();
    $view = $view->execute($obj, $manager);
    break;

case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {
        $rp->stripVars();
        
        $action = $rp->bulk_action;
        
        $data = array();
        foreach ($rp->id as $id) {
            list($entry_type, $entry_id) = explode('_', $id);
            $entry_type = (int) $entry_type;
            $data[$entry_type][] = (int) $entry_id;
        }

        $bulk_manager = new UserListModelBulk();
        $bulk_manager->setManager($manager);
        
        if($bulk_manager->validate($rp->vars)) {
            $controller->go('csrf');
        }

        switch ($action) {
        case 'remove': // ------------------------------
            foreach(array_keys($data) as $entry_type) {
                $manager->deleteEntry($data[$entry_type], $entry_type);
            }
            break;
        
        case 'enable_mail': // ------------------------------
            foreach(array_keys($data) as $entry_type) {
                $manager->emailStatus($data[$entry_type], $entry_type, 1);
            }
            break;
        
        case 'disable_mail': // ------------------------------
            foreach(array_keys($data) as $entry_type) {
                $manager->emailStatus($data[$entry_type], $entry_type, 0);
            }
            break;
            
        }

        $controller->go();
    }

    $controller->goPage('main');

    break;
    
default: // ------------------------------------
    
    $view = new UserListView_list();
    $view = $view->execute($obj, $manager);
    break;
}
?>