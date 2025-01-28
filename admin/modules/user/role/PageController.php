<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KnowledgebasePublisher package                   |
// | KnowledgebasePublisher - web based knowledgebase publishing tool          |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

$obj = new Role;

$manager =& $obj->setManager(new RoleModel());
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete': // ------------------------------
    
    $msg = $manager->delete($rq->id, $rq->parent_id);
    
    $success = ($msg == 'success') ? false : true;
    $controller->go($msg, $success);

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
        
            $id = $manager->save($obj, $controller->action);
            
            if(!empty($rq->referer)) {
                $controller->setCustomPageToReturn($rq->referer);
                if(strpos($rq->referer, 'client') !== false) {
                    $link = $controller->getClientLink(array('index', $id));
                    $controller->setCustomPageToReturn($link, false);
                    
                } else {
                    $referer = WebUtil::unserialize_url($rq->referer);
                    $referer .= '&amp;category_id=' . $id;
                    $controller->setCustomPageToReturn($referer, false);
                }
            }            
            
            $controller->go();
        }
        
    } elseif($controller->action == 'update') {
    
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data, true);
        $obj->set($data);
    
    // child clicked in list records or filter applied
    } else {

        $parent_id = 0;
        if(!empty($rq->parent_id)) {
            $parent_id = (int) $rq->parent_id;
        } elseif(!empty($rq->filter['c'])) {
            $parent_id = (int) $rq->filter['c'];
        }
        
        $obj->set('parent_id', $parent_id);
        
        if (!empty($rq->category_name)) {
            $action = new KBCategoryAction($rq, $rp);
            
            $delim = (strpos($rq->referer, 'knowledgebase')) ? '::' : '->';
            $action->setCategoryParams($obj, $manager, $delim, 'title');
        }
    }    
    
    $view = $controller->getView($obj, $manager, 'RoleView_form');

    break;


default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'RoleView_list');
}
?>