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

$obj = new KBGlossaryImport;

//$manager->checkPriv($priv, $controller->action, @$rq->id);
$priv->check();

$manager = new KBGlossaryImportModel( new KBGlossaryModel() );


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
        
        } else {
        
            $rp->stripVars();
            $obj->set($rp->vars);        

            
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
                
                $msg2 = AppMsg::getAfterActionMsg('import_result');
                $msg2['body'] = $import_result;
                $_SESSION['msg_']['import_result'] = $msg2;
                $_SESSION['import_']['glossary'] = $rp->vars;
                
                $controller->go('success', false, 'import_result-success');                
            }
        }
    }
    
    $view = $controller->getView($obj, $manager, 'KBGlossaryImportView_form');
?>