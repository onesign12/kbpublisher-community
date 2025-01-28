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

$obj = new Plugin;

$manager =& $obj->setManager(new PluginModel());

// $priv->setCustomAction('default', 'update');
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {

// case 'default': // ------------------------------
// 
//     require_once $controller->working_dir . 'inc/default.php';
//     $data = unserialize($data);    
//     $row = array();
//     foreach(array_keys($data) as $k) {
//         if($data[$k]['id'] == $rq->id) {
//             $row = $data[$k];
//             break;
//         }
//     }
// 
//     if($row) {
//         $row = $rp->stripVarsValues($row, false);
//         $obj->unsetProperties(array('title','description',
//                                     'letter_key','group_id',
//                                     'skip_field','extra_tags','skip_tags',
//                                     'is_html','in_out','predifined','active','sort_order'));
//         $obj->set($row);
//         $obj->set('subject', '');
//         $obj->set('body', NULL);
// 
//         // $manager->save($obj);
//         $manager->update($obj);
// 
//         $link = $controller->getLink('this', 'this', 'this', 'update', array('id'=>$rq->id));
//         $controller->setCustomPageToReturn($link, false);
// 
//         $controller->go();        
//     }
// 
//     $controller->goPage('main');
// 
//     break;


case 'update': // ------------------------------
case 'insert': // ------------------------------

    if(isset($rp->submit) || isset($rp->submit_new)) {
        
        $is_error = $obj->validate($rp->vars, $manager);
                
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

    $view = $controller->getView($obj, $manager, 'PluginView_form'); 

    break;


default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'PluginView_list');
}
?>