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


$controller->setMoreParams('list');
$controller->setMoreParamsAjax('list');
$list_key = $controller->getMoreParam('list');

if(empty($list_key)) {
    $obj = new ListGroup;
    $manager =& $obj->setManager(new ListGroupModel());
    
    $list_view = 'ListGroupView_list';
    $form_view = 'ListGroupView_form';

} else {
    $obj = ListValue::factory($list_key, $controller);
    $rp->setHtmlValues($obj->getHtmlField()); //not to strip html
    
    $manager = $obj->getManager($list_key, $controller);
    
    $group_msg = ParseListMsg::getGroupMsg();
    $group_list = $manager->getGroupList($list_key);
    
    $manager->setSqlParams('AND list_id = ' . $group_list['id']);
    $obj->set('list_id', $group_list['id']);
    $obj->group_key = $list_key;
    
    $obj->group_title = (empty($group_list['title'])) ? $group_msg[$list_key] : $group_list['title'];
    //$obj->setGroupList($group_list);
    
    $list_view = $obj->getListView($list_key, $controller);
    $form_view = $obj->getFormView($list_key, $controller);
}

$priv->priv_area = 'list_tool';
$priv->setCustomAction('update_group', 'update');
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete': // ------------------------------
    
    if($manager->inUse($rq->id)) {
        $controller->go('notdeleteable_entry', true);
    }
    
    $data = $manager->getById($rq->id);
    $manager->deleteAdminUserToCategory($data['list_value']);
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
            
            if(!empty($rp->vars['admin_user'])) {
                $ids = implode(',', $rp->vars['admin_user']);
                $ids = array_map('intval', $ids);
                $obj->setAdminUser($manager->getAdminUserByIds($ids));
            }            
            
        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
            $obj->setAdminUser(@$rp->vars['admin_user']);
            
            // reset default values, custom_4, available for status lists
            if($obj->get('custom_4')) {
                $manager->resetDefaults($obj->get('list_id'));
            }
            
            $manager->save($obj, $controller->action);
            
            $controller->go();
        }
    
    } elseif($controller->action == 'update') {
    
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        $obj->set($data);
        $obj->setAdminUser($manager->getAdminUserById($data['list_value']));
    
    } elseif($controller->action == 'insert') {
        
        $obj->set('sort_order', $manager->getMaxListOrder($obj->get('list_id')) + 1);
        $obj->set('list_value', $manager->getMaxListValue($obj->get('list_id')) + 1);
    } 
    

    $view = $controller->getView($obj, $manager, $form_view);

    break;

    
case 'update_group': // ------------------------------
    
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
    }
    
    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);

    $view = $controller->getView($obj, $manager, $form_view);

    break;    
    

default: // ------------------------------------
    // sort order
    if(isset($rp->submit)) {
        $manager->saveSortOrder($rp->sort_id);
    }
    
    $view = $controller->getView($obj, $manager, $list_view);
}
?>