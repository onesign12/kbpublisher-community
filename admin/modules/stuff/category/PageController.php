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

$obj = new StuffCategory;

$manager =& $obj->setManager(new StuffCategoryModel());
$manager->checkPriv($priv, $controller->action, @$rq->id);



switch ($controller->action) {
case 'delete': // ------------------------------
    
    $manager->delete($rq->id);
    $controller->go();

    break;
    

case 'status': // ------------------------------
    
    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;
    
    
case 'update': // ------------------------------
case 'insert': // ------------------------------

    if(isset($rp->submit)) {
        
        $is_error = $obj->validate($rp->vars);
        
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
        
        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
        
            $manager->save($obj);
            $controller->go();
        }
        
        
    } elseif($controller->action == 'update') {
    
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        $obj->set($data);    
    }
    

    $view = $controller->getView($obj, $manager, 'StuffCategoryView_form'); 

    break;


default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'StuffCategoryView_list');
}
?>