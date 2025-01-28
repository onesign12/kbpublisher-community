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

$obj = new UserImport;
$eobj = new User;

//$manager = new UserModel(); 
//$manager->checkPriv($priv, $controller->action, @$rq->id);
$priv->check();

$manager = new UserImportModel( new UserModel() );

    if(isset($rp->submit)) {
        
        if(APP_DEMO_MODE) { 
            $controller->go('not_allowed_demo', true);
        }
        
        
        $is_error = $obj->validate($rp->vars, $manager);
        
        if(!$is_error) {
            $upload = $manager->upload();
            if(!empty($upload['error_msg'])) {
                $obj->errors['formatted'][]['msg'] = $upload['error_msg'];
                $is_error = true;
            }
        }
        
        
        if($is_error) {
            
            $rp->stripVars(true);
            $obj->set($rp->vars);
            $eobj->set($rp->vars);
            
            // if(!empty($rp->vars['priv'])) {
            //     $eobj->setPriv($rp->vars['priv']);
            // }
            
            if(!empty($rp->vars['role'])) {
                $eobj->setRole($rp->vars['role']);
            }
        
        } else {
        
            $rp->stripVars();
            $obj->set($rp->vars);
            
            // if(!empty($rp->vars['priv'])) {
            //     $obj->setPriv($rp->vars['priv']);
            //     $obj->set('priv', $rp->vars['priv']);
            // }
            
            if(!empty($rp->vars['role'])) {
                $eobj->setRole($rp->vars['role']);
                $eobj->set('role', $rp->vars['role']);
            }            
            
            $ret = $manager->import($rp->vars['generated'], $obj->get(), $upload['filename'], $rp->vars);
            $f = ($conf['db_driver'] === 'mysql') ? 'mysql_info' : 'mysqli_info';
            $import_result = $f($manager->model->db->_connectionID);
            
            if($_FILES['file_1']['name']) {
                unlink($upload['filename']);
            }
            
            // if error in import (returned by mysql)    
            if($ret !== true) {
                $rp->stripVars(true);
                $obj->set($rp->vars);
                
                $_POST['optionally_enclosed'] = stripslashes($_POST['optionally_enclosed']);
                $_POST['lines_terminated'] = stripslashes($_POST['lines_terminated']);
                
                $obj->errors['formatted'][]['msg'] = $ret;
            
            } else {
                
                if ($manager->isMoreFieldCompatible()) {
                    $manager->setPasswords();
                    
                } else {
                    $manager->setPasswords2();
                    $manager->setDateRegistered();
                }
                

                $parse_once = 50;
                $total = $manager->getImportedUsersCount();
                $result = $manager->getImportedUsersResult();

                for($i = 0; $i < $total; $i += $parse_once) {

                    $j = 0;
                    $user_ids = array();
                    $users = array();

                    while ($row = $result->FetchRow()) {
                        $j++;
                        $import_data = explode('|', $row['import_data']);

                        $users[$j] = $row;
                        $users[$j]['password'] = $import_data[0];
                        $users[$j]['link'] = APP_CLIENT_PATH;
                        $user_ids[$j] = $row['id'];

                        if($j == $parse_once) {
                            break;
                        }
                    }
                    
                    if($eobj->getRole()) {
                        $manager->saveRoles($user_ids, $eobj->getRole());
                    }

                    $m = new AppMailSender;
                    $m->sendUserAdded($users, true);
                }

                $manager->eraseImportData();
                
                
                $msg = AppMsg::getAfterActionMsg('import_result');
                $msg['body'] = $import_result;
                $_SESSION['msg_']['import_result'] = $msg;
                $_SESSION['import_']['user'] = $rp->vars;
                
                $controller->go('success', false, 'import_result-success');
            }
        }
    }
    
    $view = $controller->getView($obj, $manager, 'UserImportView_form', $eobj);
?>