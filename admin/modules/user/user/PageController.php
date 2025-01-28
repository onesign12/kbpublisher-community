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
$controller->rp = &$rp;

$obj = new User;

$manager =& $obj->setManager(new UserModel());
$manager->checkPriv($priv, $controller->action, @$rq->id, @$rp->bulk_action, $rp->vars);
$manager->use_priv = true;
$manager->use_role = true;
$manager->use_old_pass = false; // always false in user module

if ($controller->getMoreParam('popup')) {
    $controller->setMoreParams('limit');
}
      
// include '/home/eleontev/dev/www/kbp/populate/populate_user.php';
                   
switch ($controller->action) {
case 'delete': // ------------------------------
    
    $a_manager = new UserModel_activity();
    $activities = $a_manager->getUserActivities($rq->id)[$rq->id];
    $critical_activities = $a_manager->getCritActivities($activities);
    
    if(isset($rp->submit) && !$critical_activities) {
        
        if (!empty($rp->supervisor_id)) {
            $a_manager->updateSupervisor($rp->supervisor_id, $rq->id);
        }
        
        $data = $manager->getById($rq->id);
        $obj->collect($rq->id, $data, $manager, 'save');
        
        $manager->trash($rq->id, $obj);
        
        if (!empty($rp->notify)) {
            $manager->sendUserDeleted($data);
        }
        
        $controller->go('success', false, false, 'trash');
    
    }
    
    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);
    
    $data = [
        'activities' => $activities, 
        'critical_activities' => $critical_activities
        ];
    $view = $controller->getView($obj, $manager, 'UserView_delete', $data);
    
    break;
    

case 'status': // ------------------------------

    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;    
    
    
case 'export': // ------------------------------
          
    $export = new AppExportList($rp->vars);
    $export->export($obj, $manager, new UserView_list);          
          
    break;    
    
    
case 'login': // ------------------------------
    
    $user_id = intval($rq->id);
    $username = ''; // empty to insert to log
    
    $auth = new AuthPriv();
    //$auth->setUserStatus(false);
    $ret = $auth->getAuthByValue(array('id' => $user_id));

    $log = new LoggerModel;
    $log->putLogin('Initializing "As User" authentication');
    $log->putLogin(sprintf('User (ID: %d) is trying to login as user (ID: %d)', $manager->user_id, $user_id));

    if($ret) {
        $auth->logout();
        $auth->doAuthByValue(array('id' => $user_id ));
        
        $log->putLogin('Login successful');
        $log->addLogin($user_id, $username, 3, 1);
        
        if($auth->getPrivId()) {
            $link = $controller->getLink();
        } else {
            $cc = &$controller->getClientController();
            $link = $cc->getRedirectLink();
        }
        
        $controller->setCustomPageToReturn($link, false);
        $controller->setRequestVar('id', 0); // to skip log id to UserActivityLog::addAction
        $controller->go();            
        
    } else {
        $msg = AppMsg::afterActionMsg('unable_login_as_user');
        $log->putLogin(implode(' - ', $msg));
        $log->addLogin($user_id, $username, 3, 2);    
    }
    
    $controller->go('unable_login_as_user', true, false, 'skip');

    break;    
    
    
case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();
        
        $ids = array_map('intval', $rp->id);
        $action = $rp->bulk_action;
        
        $bulk_manager = new UserModelBulk();
        $bulk_manager->setManager($manager);
        
        if($bulk_manager->validate($rp->vars)) {
            $controller->go('csrf');
        }
        
        
        switch ($action) {
        case 'trash': // ------------------------------
            $a_manager = new UserModel_activity;
            $user_stuff = $a_manager->getUserActivities($ids);
            
            $skipped_ids = array();
            foreach ($ids as $id) {
                if (array_sum($user_stuff[$id])) {
                    $skipped_ids[] = $id;
                }
            }
            
            $ids = array_diff($ids, $skipped_ids);
            if (!empty($ids)) {
                // $manager->delete($ids);
                $bulk_manager->trash($ids);
            }
            
            if($skipped_ids) {
                $f = implode(',', $skipped_ids);
                $more = array('filter[q]'=>$f, 'show_msg2' => 'note_remove_user_bulk');
                $controller->goPage('users', 'user', false, false, $more);
            }
            
            break;
        
        case 'status': // ------------------------------
            $manager->status($rp->value['status'], $ids);
            $bulk_manager->updateSphinxAttributes('active', $rp->value['status'], implode(',', $ids));
            break;        
        
        case 'role': // ------------------------------
            $role_action = $rp->value['role_action'];
            if($role_action == 'remove') {
                $bulk_manager->removeRole($rp->value['role'], $ids);
                
            } elseif($role_action == 'set') {
                $bulk_manager->setRole($rp->value['role'], $ids);
                
            } elseif($role_action == 'add') {
                $bulk_manager->addRole($rp->value['role'], $ids);
            }
            
            break;        
            
        case 'priv': // ------------------------------
            $bulk_manager->setPriv($rp->value['priv'], $ids);
            break;            
        
        case 'comp': // ------------------------------
            $bulk_manager->setCompany($rp->value['comp'], $ids);
            break;
            
        case 'subscription': // ------------------------------
            $subs_action = $rp->value['subscription_action'];
            if($subs_action == 'remove') {
                $bulk_manager->removeSubscription($rp->value['subscription'], $ids);
                
            } elseif($subs_action == 'set') {
                $bulk_manager->setSubscription($rp->value['subscription'], $ids);
                
            } elseif($subs_action == 'add') {
                $bulk_manager->addSubscription($rp->value['subscription'], $ids);
            }
            break;
        }
        
        $controller->go();
    }
    
    $controller->goPage('main');
    
    break;
    
    
case 'role': // ------------------------------

    $view = $controller->getView($obj, $manager, 'UserView_role');
    break;    
    
    
case 'detail': // ------------------------------

    $data = $manager->getById($rq->id);

    if(!$data) {
        $controller->go('record_not_exists', true);
    }        

    $rp->stripVarsValues($data);
    $obj->set($data);
    $obj->setPriv($manager->getPrivById($rq->id));
    $obj->setRole($manager->getRoleById($rq->id));
    
    $extra = $manager->getExtraById($rq->id);
    $rp->stripVarsValues($extra);
    $obj->setExtra($extra);

    $sso = $manager->getSso($rq->id);
    $rp->stripVarsValues($sso);
    foreach($sso as $v) {
        $obj->setSso($v['sso_provider_id'], $v['sso_user_id']);
    }
    
    $view = $controller->getView($obj, $manager, 'UserView_detail');
    
    break;
    
    
case 'activity': // ------------------------------

    $data = $manager->getById($rq->id);

    if(!$data) {
        $controller->go('record_not_exists', true);
    }        

    $rp->stripVarsValues($data);
    $obj->set($data);
    $obj->setPriv($manager->getPrivById($rq->id));
    $obj->setRole($manager->getRoleById($rq->id));
    
    $extra = $manager->getExtraById($rq->id);
    $rp->stripVarsValues($extra);
    $obj->setExtra($extra);

    $view = $controller->getView($obj, $manager, 'UserView_activity');
    
    break;


case 'api': // ------------------------------

    $api_rule_id = $manager->extra_rules['api'];

    if(isset($rp->submit)) {

        if(APP_DEMO_MODE) {  $controller->go('not_allowed_demo', true);  }

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
            
            $more = array('id' => $obj->get('id'));
            $return = $controller->getLink('this', 'this', '', 'detail', $more);
            $controller->setCustomPageToReturn($return, false);
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

    // to have it after submit
    array_push($obj->hidden, 'username');
    
    $view = $controller->getView($obj, $manager, 'UserView_api');

    break;
    
    
case 'password': // ------------------------------

    if(isset($rp->submit)) {

        if(APP_DEMO_MODE) {  $controller->go('not_allowed_demo', true);  }

        $is_error = $obj->validatePassword($rp->vars, $manager);

        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);

        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
            $obj->setPassword(); // hash it

            $manager->updatePassword($obj->get('password'), $obj->get('id'), $obj->pass_changed);
            $manager->resetRememberAuth($obj->get('id'));
            $manager->resetAuthSessionId($obj->get('id'));// concurent if use
            
            if(!empty($rp->notify)) {
                $sent = $manager->sendUserUpdated($rp->vars);
            }            
            
            $more = array('id' => $obj->get('id'));
            $return = $controller->getLink('this', 'this', '', 'detail', $more);
            $controller->setCustomPageToReturn($return, false);
            $controller->go();
        }
    
    } else {
        
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        $obj->set($data);
    }

    // to have it after submit
    array_push($obj->hidden, 'username', 'first_name', 'last_name', 'email');
    
    $view = $controller->getView($obj, $manager, 'UserView_password');

    break;
    

case 'invite': // ------------------------------
    exit('Disabled');
    
    if(isset($rp->submit)) {
        
        $is_error = $obj->validateInvite($rp->vars, $manager);
        
        if($is_error) {
            $rp->stripVars(true);
            
            $obj->set($rp->vars);
            if(!empty($rp->vars['priv'])) {
                $obj->setPriv($rp->vars['priv']);
            }
            
            if(!empty($rp->vars['role'])) {
                $obj->setRole($rp->vars['role']);
            }
            
        } else {
                        
            $rp->stripVars();
            $cc = &$controller->getClientController();
            
            foreach($rp->email as $email) {
                $eobj = new User();
                $eobj->setEmail($email);
                $eobj->setGrantor();
                $eobj->set('username', $obj->get('email'));
            
                if(!empty($rp->vars['priv'])) {
                    $obj->setPriv($rp->vars['priv']);
                }
                
                if(!empty($rp->vars['role'])) {
                    $obj->setRole($rp->vars['role']);
                }
            
                $values = [
                    'rule_id' => UserModelExtra::$temp_rules['user_invited'],
                    'value2' => addslashes(serialize($obj))
                ];        
                $manager->extra_manager->addTemp($values, 0);
                
                
                $values = $rp->vars;
                $values['link'] = $cc->getLink('register');
                
                $sent = $manager->sendUserInvited($rp->vars);
            }
            
            $controller->go();
        }
    } 
    
    
    $view = $controller->getView($obj, $manager, 'UserView_invite');

    break;
    

case 'update': // ------------------------------
case 'insert': // ------------------------------
    
    if(isset($rp->submit)) {
        
        $is_error = $obj->validate($rp->vars, $manager);
        
        if($is_error) {
            $rp->stripVars(true);
            
            $obj->set($rp->vars);
            if(!empty($rp->vars['priv'])) {
                $obj->setPriv($rp->vars['priv']);
            }
            
            if(!empty($rp->vars['role'])) {
                $obj->setRole($rp->vars['role']);
            }

            $subs = (!empty($rp->vars['subscription'])) ? $rp->vars['subscription'] : array(); 
            $obj->setSubscription($subs);
                    
            if(!empty($rp->vars['extra'])) {
                $obj->setExtra($rp->vars['extra']);
            }
            
        } else {
                        
            $rp->stripVars();
            $obj->set($rp->vars);
            $obj->setGrantor();
            $obj->setUsername();
            $obj->setPassword(isset($rp->not_change_pass)); // mean not insert in db if not_change_pass = 1
            
            if(!empty($rp->vars['priv'])) {
                $obj->setPriv($rp->vars['priv']);
            }
            
            if(!empty($rp->vars['role'])) {
                $obj->setRole($rp->vars['role']);
            }

            $subs = (!empty($rp->vars['subscription'])) ? $rp->vars['subscription'] : array(); 
            $obj->setSubscription($subs);
            
            
            // extra api, add on insert
            $api_key = $manager->extra_rules['api'];
            if($controller->action == 'insert') {
                if(!empty($rp->vars['extra'][$api_key]['value1'])) {
                    $rp->vars['extra'][$api_key]['value2'] = $manager->generateApiKey();
                    $rp->vars['extra'][$api_key]['value3'] = $manager->generateApiKey();
                }
                
            // update on edit
            } elseif(empty($rp->vars['extra'][$api_key]['value1'])) {
                $rp->vars['extra'][$api_key]['value1'] = 0; // disable api access
            }
            
            if(!empty($rp->vars['extra'])) {
                $obj->setExtra($rp->vars['extra']);
            }
            
            
            // mail
            $old_status = false;
            $publish_status_ids = array();
            if($controller->action == 'update' || $controller->action == 'approve') {
                $old_status = $manager->getStatusKey($obj->get('id'));
                $publish_status_ids = $manager->getEntryStatusPublished();    
            }
            
            $user_id = $manager->save($obj, $manager->user_id);
            $obj->set('id', $user_id);
            $new_status = $manager->getStatusKey($user_id);
            
            $controller->setRequestVar('id', $user_id);
            
            //approved
            if(in_array($obj->get('active'), $publish_status_ids) && $old_status == 'approve') {
                $sent = $manager->sendUserApproved($rp->vars);
            
            // added
            } elseif($controller->action == 'insert' && !empty($rp->notify)) {
                $sent = $manager->sendUserAdded($rp->vars);
        
            // updated
            } elseif($controller->action == 'update' && !empty($rp->notify)) {
                $sent = $manager->sendUserUpdated($rp->vars);
            }
            
            $controller->go();
        }
    
    } elseif($controller->action == 'update') {
        
        $data = $manager->getById($rq->id);
        
        if(!$data) {
            $controller->go('record_not_exists', true);
        }        
        
        $rp->stripVarsValues($data);
        $obj->set($data);
        $obj->more_info = true;
        
        $obj->setPriv($manager->getPrivById($rq->id));
        $obj->setRole($manager->getRoleById($rq->id));
        
        $extra = $manager->getExtraById($rq->id);
        $rp->stripVarsValues($extra);
        $obj->setExtra($extra);

    } elseif($controller->action == 'insert') {
    
        $status = ListValueModel::getListDefaultEntry('user_status');
        $status = ($status !== null) ? $status : $obj->get('active');
        $obj->set('active', $status);
    }
    
    $view = $controller->getView($obj, $manager, 'UserView_form');

    break;


default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'UserView_list');
}
?>