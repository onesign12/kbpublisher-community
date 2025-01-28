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
$rp->setHtmlValues('body'); // to skip $_GET['body'] not strip html

$obj = new ArticleTemplate;
//$obj->setHtmlValues('body');

$manager =& $obj->setManager(new ArticleTemplateModel());

$priv->setCustomAction('preview', 'select');
$priv->setCustomAction('browse', 'select');

// skip priv check if browse popup to choose template
if($controller->getMoreParam('popup') && in_array($controller->action, array('preview', 'browse'))) {
	
} else {
	$manager->checkPriv($priv, $controller->action, @$rq->id);
}


$manager->setEntryType($controller->module);
$obj->set('entry_type', $manager->entry_type);

switch ($controller->action) {
case 'delete': // ------------------------------
    
    $manager->delete($rq->id);
    $controller->go();

    break;
    

case 'status': // ------------------------------
    
    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;


case 'preview': // ------------------------------
    
    $data = $manager->getById($rq->id); 

    $rp->stripVarsValues($data);
    $obj->set($data);
    
    $view = $controller->getView($obj, $manager, 'ArticleTemplateView_preview'); 

    break;    
    
    
case 'clone': // ------------------------------
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
            $controller->go();
        }
        
        
    } elseif(in_array($controller->action, array('update', 'clone'))) {
    
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        $obj->set($data, false, $controller->action);    
    }
    

    $view = $controller->getView($obj, $manager, 'ArticleTemplateView_form'); 

    break;


default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'ArticleTemplateView_list');
}
?>