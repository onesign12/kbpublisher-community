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

$obj = new UserBan;

$manager =& $obj->setManager(new UserBanModel());
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
    
    
case 'detail': // --------------------------------
     
    $data = $manager->getById($rq->id);
    $obj->set($data);
    $view = $controller->getView($obj, $manager, 'UserBanView_detail');

    break;

case 'clone': // -------------------------------
case 'update': // ------------------------------
case 'insert': // ------------------------------

    $data = array();
    
    if(isset($rp->submit)) {
                                      
        $is_error = $obj->validate($rp->vars);
        
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            
            $data['date_end_num'] = $rp->date_end_num;
        
        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
            
            if ($obj->get('ban_rule') == 1) {
                $obj->set('ban_value', $rp->user_id);
            }
            
            if(in_array($controller->action, array('insert', 'clone'))) {
                $obj->set('date_end', NULL); 
                if ($rp->date_end != 'perm') {
                    $time_str = '+%d %s';
                    $time_str = sprintf($time_str, $rp->date_end_num, $rp->date_end);
                                                                             
                    $date_end = strtotime($time_str);
                    $obj->set('date_end', date('Y-m-d H:i:s', $date_end)); 
                } 
            }

            $manager->save($obj, $controller->action);
            $controller->go();
        }
        
    } elseif(in_array($controller->action, array('update', 'clone'))) {
    
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data, true);
        $obj->set($data, false, $controller->action);
    }
    
    $view = $controller->getView($obj, $manager, 'UserBanView_form', $data);

    break;


default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'UserBanView_list');
}
?>