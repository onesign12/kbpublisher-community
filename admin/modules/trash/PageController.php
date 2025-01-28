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
$rp->setSkipKeys(array('entry_obj'));

$obj = new TrashEntry;

$manager =& $obj->setManager(new TrashEntryModel());
$priv->setCustomAction('empty', 'delete');
$priv->setCustomAction('restore', 'update');
$priv->setCustomAction('preview', 'select');
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete': // ------------------------------
    
    $data = $manager->getEntryData($rq->id);
    $class_name = $manager->record_type[$data['entry_type']];
    $action = TrashAction::factory($class_name);
    
    $manager->delete($rq->id);
    $action->deleteOnTrashEntry($data['entry_id'], $data);
    
    UserActivityLog::add($class_name, 'delete', $rq->id);
    
    $controller->go();

    break;
    

case 'empty': // ------------------------------
    
    if (!empty($rq->filter['entry_type']) && $rq->filter['entry_type'] != 'all') {
        $entry_type = (int) $rq->filter['entry_type'];
        $types = array($entry_type);
        $manager->setSqlParams("AND entry_type = '$entry_type'");
        
    } else {
        $types = $manager->getEntryTypes();
    }
    
    $news_type = array_flip($manager->record_type)['news'];
    if(isset($types[$news_type]) && !AppPlugin::isPlugin('news')) {
        unset($types[$news_type]);
        $manager->setSqlParams("AND entry_type != $news_type");
    }
    
    $rows_by_entry_type = $manager->getRecordsByEntryType();
    
    $manager->truncate();

    foreach($types as $entry_type) {
        $class_name = $manager->record_type[$entry_type];
        $action = TrashAction::factory($class_name);
        $action->deleteOnTrashEmpty($rows_by_entry_type[$entry_type]);
        
        $ids = $manager->getValuesArray($rows_by_entry_type[$entry_type], 'entry_id');
        UserActivityLog::add($class_name, 'delete', $ids);
    }
    
    $controller->goPage('main'); // remove all filters, bp, etc.

    break;
    
    
case 'restore': // ------------------------------

    // to parse articltes/files
    $rp_eobj = new RequestData($_POST);
    $rp_eobj->setHtmlValues('body'); // to skip $_GET['body'] not strip html
    $rp_eobj->setCurlyBracesValues('body');
    $rp_eobj->setSkipKeys(array('schedule', 'schedule_on'));

    $data = $manager->getById($rq->id);
    
    $class_name = $manager->record_type[$data['entry_type']];
    $action = TrashAction::factory($class_name);
    
    $entry_obj = unserialize($data['entry_obj']);
    
    $is_error = false;
    if (isset($rp->submit)) {
        $is_error = $action->validate($entry_obj, $rp->vars);
        $rp->stripVars('stripslashes');
        unset($rp->vars['id']); // not to assign wrong id to $entry_obj
        $action->setNewValues($entry_obj, $rp->vars);
    } 
    
    if(!$is_error) {
        $is_error = $action->validateObj($entry_obj);
    }
    
    if($is_error) {
        $rp->stripVarsValues($data);
        $obj->set($data);

        $rp_eobj->stripVarsValues($entry_obj->properties);
        $view = $controller->getView($obj, $manager, 'TrashEntryViewIncomplete', $entry_obj);
     
    } else {
        $rp_eobj->stripVarsValues($entry_obj->properties, false);
        $action->restore($entry_obj);

        $manager->delete($rq->id);
        $controller->go();
    }
    
    break;
    
    
case 'preview': // ------------------------------
    
    $data = $manager->getById($rq->id);
    
    $class_name = $manager->record_type[$data['entry_type']];
    $action = TrashAction::factory($class_name);
    
    $view = $action->getPreview($data['entry_obj'], $controller);
    break;
    

default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'TrashEntryView_list');
}
?>