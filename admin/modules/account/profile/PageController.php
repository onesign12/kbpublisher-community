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

$obj = new User;
array_push($obj->hidden, 'active');

$manager =& $obj->setManager(new UserModel());
//$manager->checkPriv($priv, $controller->action, @$rq->id);
$manager->use_old_pass = SettingModel::getQuick(1, 'account_password_old');
$manager->account_updateable = UserModel::isAccountUpdateable();

// only admin allowed to chenge roles in account view
if($manager->is_admin) {
    $manager->use_role = true;
}

$rq->id = AuthPriv::getUserId();
$user_id = AuthPriv::getUserId();


switch ($controller->action) {
case 'update': // ------------------------------

    if(isset($rp->submit)) {

        if(APP_DEMO_MODE) {
            $controller->go('not_allowed_demo', true);
        }

        $rp->vars['not_change_pass'] = 1;
        $is_error = $obj->validate($rp->vars, $manager);

        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);

            if(!empty($rp->vars['role'])) {
                $obj->setRole($rp->vars['role']);
            }

        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
            $obj->set('id', $user_id);
            $obj->setUsername();
            $obj->setPassword(1); // mean not insert in db if not_change_pass = 1
            $obj->unsetProperties(array('user_comment', 'admin_comment', 'grantor_id'));

            if(!empty($rp->vars['role'])) {
                $obj->setRole($rp->vars['role']);
            }
            
            $manager->save($obj, $user_id);
            $sent = $manager->sendAccountChanged($user_id);
            
            $controller->go();
        }

    } else {
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        $obj->set($data);
        $obj->set('id', $user_id);
        //$obj->setPriv($manager->getPrivById($rq->id));
        $obj->setRole($manager->getRoleById($rq->id));
    }

    $view = new UserView_form();
    $view->account_view = true;
    if(AppView::isAdminView()) {
        $view->template_dir = APP_MODULE_DIR . 'user/user/template/';
    }
    $view = $view->execute($obj, $manager);

    break;


case 'api': // ------------------------------

    $obj->setExtra($manager->getExtraById($rq->id));

    $api_rule_id = $manager->extra_rules['api'];
    $api_data = $obj->getExtraValues($api_rule_id);
    $api_access = $api_data['api_access'];

    // no access to update it
    if(!$api_access && !$manager->is_admin) {
        echo AuthPriv::errorMsg();
        exit;
    }

    if(isset($rp->submit)) {

        if(APP_DEMO_MODE) {
            $controller->go('not_allowed_demo', true);
        }

        $obj->setExtra($rp->vars['extra']);
        $api_data = $obj->getExtraValues($api_rule_id);
        $api_data['atoken'] = $rp->vars['atoken'];

        $is_error = $obj->validateApiKeys($api_data);

        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            $obj->setExtra($rp->vars['extra']);

        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
            $obj->set('id', $user_id);
            
            @$puser = $rp->vars['extra'][$api_rule_id]['puser'];
            unset($rp->vars['extra'][$api_rule_id]['puser']);
            
            // access checkbox
            if(empty($rp->vars['extra'][$api_rule_id]['value1'])) {
                $rp->vars['extra'][$api_rule_id]['value1'] = 0;
                
            } elseif (!empty($puser)) {
                $rp->vars['extra'][$api_rule_id]['value1'] = 2;
            }
            
            $obj->setExtra($rp->vars['extra']);

            $manager->saveExtra($obj->getExtra(), $obj->get('id'));
            $controller->go();
        }

    } else {

        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        $obj->set($data);

        $extra = $manager->getExtraById($rq->id);
        $rp->stripVarsValues($extra);
        $obj->setExtra($extra);
    }

    $view = new UserView_api();
    $view->account_view = true;
    if(AppView::isAdminView()) {
        $view->template_dir = APP_MODULE_DIR . 'user/user/template/';
    }
    $view = $view->execute($obj, $manager);

    break;


default: // ------------------------------------

    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);

    $extra = $manager->getExtraById($rq->id);
    $rp->stripVarsValues($extra);
    $obj->setExtra($extra);

    $obj->set('id', $user_id);
    $obj->setPriv($manager->getPrivById($rq->id));
    $obj->setRole($manager->getRoleById($rq->id));

    $sso = $manager->getSso($rq->id);
    $rp->stripVarsValues($sso);
    foreach($sso as $v) {
        $obj->setSso($v['sso_provider_id'], $v['sso_user_id']);
    }
    
    $view = new UserView_detail();
    $view->account_view = true;
    if(AppView::isAdminView()) {
        $view->template_dir = APP_MODULE_DIR . 'user/user/template/';
    }
    $view = $view->execute($obj, $manager);
}

?>