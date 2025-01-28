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

$obj = new UserSecurity;
$manager = new UserModel();
$manager->use_old_pass = SettingModel::getQuick(1, 'account_password_old');
$manager->account_updateable = UserModel::isAccountUpdateable();

// skip check priv for account
// add this to not strip  actions/buttons and allow bulk
$priv->skip_check_priv = true;

$rq->id = AuthPriv::getUserId();
$user_id = AuthPriv::getUserId();
$mfa_rule_id = $manager->extra_rules['mfa'];


switch ($controller->action) {
case 'delete': // ------------------------------

    $a_managaer = new UserModel_activity();
    $activities = $a_managaer->getUserActivities($rq->id)[$rq->id];
    $deleteable = ($manager->isAccountDeleteable() === true);
    $request_only = ($activities || !$deleteable);

    if(isset($rp->submit) || isset($rp->submit_request)) {
     
        $is_error = $obj->validateDelete($rp->vars);

        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            $obj->set('message', $rp->vars['message']);
        
        } elseif($request_only) { 
        
            $manager->sendAccountDeleteRequest($rq->id, $rp->vars);
            
            $_GET['saved'] = 1;
            $controller->setMoreParams('saved');
            
            // $controller->go(); // this is popup js close snd reload parent
            
        } else {
            
            $data = $manager->getById($rq->id);
            $obj->collect($rq->id, $data, $manager, 'save');

            $manager->trash($rq->id, $obj);
            
            $rp->stripVars();
            $manager->sendAccountDeleted($data, $rp->vars); // to user and admin

            AuthPriv::logout();
            AuthPriv::removeAllCookie();
            
            $_GET['saved'] = 2;
            $controller->setMoreParams('saved');
            
            // $controller->goUrl(APP_CLIENT_PATH); // this is popup js close snd reload parent
        }
    }
    
    $view = $controller->getView($obj, $manager, 'UserSecurityView_delete', $request_only);
    
    break;
    
    
case 'mfa_disable': // ------------------------------

    if(isset($rp->mfa_off2)) {
        $is_error = $obj->validateDisableMfa($rp->vars);
        
        if($is_error) {
            $controller->go('csrf');
            
        } else {
            $manager->deleteExtraRule($mfa_rule_id, $user_id);
            $controller->go();
        }
    }
    
    break;


case 'mfa_enable': // ------------------------------

    if(isset($rp->submit)) {
        
        $is_error = $obj->validateMfa($rp->vars);
        
        if($is_error) {
            // $rp->stripVars(true);
            // $obj->set($rp->vars);
            // $obj->setExtra($rp->vars['extra']);

        } else {
            $rp->stripVars();
            
            $scratch = MfaAuthenticator::generateScratchCode();
            $obj->set('scratch_code', $scratch['code']);
            
            $mfa = MfaAuthenticator::factory('app');
            $mfa->save($user_id, $rp->vars['secret'], $scratch['hash']);
            
            $_GET['saved'] = 1;
            $controller->setMoreParams('saved');
            
            // $controller->go(); // this is popup js close snd reload parent
        }

    } else {
        
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        $obj->set($data);
        
        $extra = $manager->getExtraById($rq->id);
        $rp->stripVarsValues($extra);
        $obj->setExtra($extra);
    }

    $view = $controller->getView($obj, $manager, 'UserSecurityView_mfa');
    
    break;


case 'password': // ------------------------------

    if(!$manager->account_updateable) {
        $controller->go('access_denied', true);
    }

    if(isset($rp->submit)) {

        if(APP_DEMO_MODE) {
            $controller->go('not_allowed_demo', true);
        }

        $is_error = $obj->validatePassword($rp->vars, $manager);

        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            $obj->set('id', $user_id);

        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
            $obj->set('id', $user_id);
            $obj->setPassword(); // hash it
            
            $manager->updatePassword($obj->get('password'), $user_id, $obj->pass_changed);
            $manager->resetRememberAuth($user_id);
            $manager->resetAuthSessionId($user_id);// concurent if use
            $sent = $manager->sendPasswordChanged($user_id);

            if(SettingModel::getQuick(1, 'account_password_logout')) {
                AuthPriv::logout();
                $cc = &$controller->getClientController();
                $url = $cc->getLink('login');
                $cc->goUrl($url);
            
            } else { // relogin user
                AuthPriv::logout();
                $auth = new AuthPriv();
                $auth->doAuthByValue(array('id' => $user_id));
                
                $controller->go();
            }
        }

    } else {
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        $obj->set($data);
        $obj->set('id', $user_id);
    }

    $view = new UserView_password();
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
    
    $view = new UserSecurityView_list();
    $view = $view->execute($obj, $manager);
    break;
}
?>
