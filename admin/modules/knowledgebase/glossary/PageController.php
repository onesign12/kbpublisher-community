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
$rp->setHtmlValues('definition'); // to skip $_GET['definition'] not strip html

$obj = new KBGlossary;

$manager =& $obj->setManager(new KBGlossaryModel());
$manager->checkPriv($priv, $controller->action, @$rq->id, @$rp->bulk_action);



switch ($controller->action) {
case 'delete': // ------------------------------
    
    $manager->delete($rq->id);
    AppSphinxModel::updateAttributes('is_deleted', 1, $rq->id, $manager->entry_type);
    $controller->go();

    break;
    

case 'status': // ------------------------------
    
    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;    


case 'preview': // ------------------------------

    $view = $controller->getView($obj, $manager, 'KBGlossaryView_preview');
    break;    


case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();

        $ids = array_map('intval', $rp->id);
        $ids_str = implode(',', $ids);
        $action = $rp->bulk_action;

        $bulk_manager = new KBGlossaryModelBulk();
        $bulk_manager->setManager($manager);
        
        if($bulk_manager->validate($rp->vars)) {
            $controller->go('csrf');
        }

        switch ($action) {
        case 'delete': // ------------------------------
            $manager->delete($ids);
            AppSphinxModel::updateAttributes('is_deleted', 1, $ids_str, $manager->entry_type);
            break;

        case 'status': // ------------------------------
            $manager->status($rp->value['status'], $ids);
            $bulk_manager->updateSphinxAttributes('active', $rp->value['status'], $ids_str);
            break;

        case 'glossary_case': // ------------------------------
            $bulk_manager->updateCase($rp->value['case'], $ids);
            break;
            
        case 'glossary_display': // ------------------------------
            $bulk_manager->updateHighlight($rp->value['display'], $ids);
            break;
        }        

        $controller->go();
    }

    $controller->goPage('main');

    break;

    
case 'update': // ------------------------------
case 'insert': // ------------------------------
    
    if(isset($rp->submit)) {
        
        $is_error = $obj->validate($rp->vars, $manager);
        
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
        
        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
            $obj->set('definition', RequestDataUtil::stripJs($rp->vars['definition']));
            
            $display_once = $rp->vars['display_once']['highlight'] | $rp->vars['display_once']['case'];
            $obj->set('display_once', $display_once);
            
            $manager->save($obj);
            
            $controller->go();
        }
        
        
    } elseif($controller->action == 'update') {
    
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        $obj->set($data);    
    }

    $view = $controller->getView($obj, $manager, 'KBGlossaryView_form');

    break;
    
    
default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'KBGlossaryView_list');
}
?>