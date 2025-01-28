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



$rq = new RequestData($_GET, array('id', 'parent_id'));
$rp = new RequestData($_POST);

$obj = new KBCategory;

$manager = &$obj->setManager(new KBCategoryModel());

$action = new KBCategoryAction($rq, $rp);

$record_id = ($controller->action == 'insert' && !empty($rq->parent_id)) ? $rq->parent_id : @$rq->id;
$manager->checkPriv($priv, $controller->action, $record_id, $controller->getMoreParam('popup'), @$rp->bulk_action);


switch ($controller->action) {
case 'delete': // ------------------------------

    $msg = $manager->delete($rq->id, $rq->parent_id);
    
    // remove $rq->filter['c'] to redirect correct
    if($msg == 'success' && isset($rq->filter['c']) && $rq->filter['c'] == $rq->id) {
        unset($rq->filter['c']);
        $controller->setCommonLink();
    }
    
    $success = ($msg == 'success') ? false : true;
    $controller->go($msg, $success);

    break;


case 'status': // ------------------------------

    $manager->statusCategory($rq->status, $rq->id);
    $controller->go();

    break;

case 'role': // ------------------------------

    $view = new UserView_role_private();
    $view = $view->execute($obj, $manager);

    break;


case 'category': // ------------------------------
    
    $view = $controller->getView($obj, $manager, 'KBCategoryView_category');
    
    break;


case 'clone_tree': // ------------------------------

    $action->cloneTree($obj, $manager, $controller);
    
    break;
    

case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();

        $ids = array_map('intval', $rp->id);
        $action = $rp->bulk_action;

        $bulk_manager = new KBCategoryModelBulk();
        $bulk_manager->setManager($manager);
        
        if($bulk_manager->validate($rp->vars)) {
            $controller->go('csrf');
        }
        $bulk_manager->apply_child = (isset($rp->value['apply_child']));

        switch ($action) {
        //case 'delete': // ------------------------------
        //    $manager->delete($ids, true); // false to skip sort updating  ???
        //    break;

        case 'status': // ------------------------------
            $bulk_manager->statusCategory($rp->value['status'], $ids);
            break;

        case 'private': // ------------------------------
            $pr = (isset($rp->value['private'])) ? $rp->value['private'] : 0;
            $bulk_manager->setPrivate($rp->value, $pr, $ids);
            break;

        case 'public': // ------------------------------
            $bulk_manager->setPublic($ids);
            break;

        case 'admin': // ------------------------------
            $bulk_manager->setAdmin($rp->value['admin_user'], $ids);
            break;

        case 'type': // ------------------------------
            $bulk_manager->setEntryType($rp->value['type'], $ids);
            break;

        case 'sort': // ------------------------------
            $bulk_manager->setSortPublic($rp->value['sort'], $ids);
            break;

        case 'commentable': // ------------------------------
            $bulk_manager->setCommentable($rp->value['commentable'], $ids);
            break;

        case 'ratingable': // ------------------------------
            $bulk_manager->setRatingable($rp->value['ratingable'], $ids);
            break;
        }

        $controller->go();
    }

    $controller->goPage('main');

    break;


case 'clone': // ------------------------------
case 'update': // ------------------------------
case 'insert': // ------------------------------

    if(isset($rp->submit)) {

        $is_error =$obj->validate($rp->vars);

        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            $obj->set('commentable', empty($rp->vars['commentable']) ? 0 : 1);
            $obj->set('ratingable', empty($rp->vars['ratingable']) ? 0 : 1 );

            if(!empty($rp->vars['role_read'])) {
                $obj->setRoleRead($rp->vars['role_read']);
            }

            if(!empty($rp->vars['role_write'])) {
                $obj->setRoleWrite($rp->vars['role_write']);
            }

            if(!empty($rp->vars['admin_user'])) {
                $ids = implode(',', $rp->vars['admin_user']);
                $obj->setAdminUser($manager->getAdminUserByIds($ids));
            }

        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
            $obj->set('commentable', empty($rp->vars['commentable']) ? 0 : 1);
            $obj->set('ratingable', empty($rp->vars['ratingable']) ? 0 : 1 );
            $obj->setAdminUser(@$rp->vars['admin_user']);
            $obj->setRoleRead(@$rp->vars['role_read']);
            $obj->setRoleWrite(@$rp->vars['role_write']);

            $id = $manager->save($obj, $controller->action);

            if(!empty($rq->referer)) {
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
        
    } elseif(in_array($controller->action, array('update', 'clone'))) {

        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        
        $obj->set($data, false, $controller->action);
        $obj->setAdminUser($manager->getAdminUserById($rq->id));
        $obj->setRoleRead($manager->getRoleReadById($rq->id));
        $obj->setRoleWrite($manager->getRoleWriteById($rq->id));
        
    // child clicked in list records or filter applied
    } else {

        $parent_id = 0;
        if(!empty($rq->parent_id)) {
            $parent_id = (int) $rq->parent_id;
        } elseif(!empty($rq->filter['c'])) {
            $parent_id = (int) $rq->filter['c'];
        }

        if($parent_id) {
            $data = $manager->getById($parent_id);
            $obj->set('parent_id', $parent_id);
            $obj->set('private', $data['private']);
            $obj->set('commentable', $data['commentable']);
            $obj->set('ratingable', $data['ratingable']);
            $obj->set('category_type', $data['category_type']);
            $obj->set('active', $data['active']);
            $obj->setAdminUser($manager->getAdminUserById($obj->get('parent_id')));
            $obj->setRoleRead($manager->getRoleReadById($obj->get('parent_id')));
            $obj->setRoleWrite($manager->getRoleWriteById($obj->get('parent_id')));
        }
        
        if (!empty($rq->category_name)) {
            $action->setCategoryParams($obj, $manager);
        }
    }
    
    $view = $controller->getView($obj, $manager, 'KBCategoryView_form');

    break;


default: // ------------------------------------

    // sort order
    if(isset($rp->submit)) {
        $manager->saveSortOrder($rp->sort_id);
    }

    $view = $controller->getView($obj, $manager, 'KBCategoryView_list');
}
?>