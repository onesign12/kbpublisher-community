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

$obj = new Priv;

$manager =& $obj->setManager(new PrivModel());
$priv->setCustomAction('default', 'update');
$manager->checkPriv($priv, $controller->action, @$rq->id);


// file last generated on 23 Jan, 2023
/*$file = $controller->working_dir . 'inc/default.php';
$data  = serialize($manager->getDefaultRecords());
$data = sprintf('$data=\'%s\'', $data);
echo "<?php\n" . $data . "\n?>";
exit;*/


switch ($controller->action) {
case 'delete': // ------------------------------
    
    if(APP_DEMO_MODE) { 
        $controller->go('not_allowed_demo', true);
    }    
    
    if($manager->isPrivInUse($rq->id)) {
        $controller->go('notdeleteable_entry', true);
    }
    
    if(!$manager->isPrivEditable($rq->id)) {
        $controller->go('notdeleteable_entry', true);
    }    
    
    $manager->delete($rq->id);
    $controller->go();

    break;
    

case 'status': // ------------------------------
    
    if(!$manager->isPrivEditable($rq->id)) {
        $controller->go('', true);
    }        
    
    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;

case 'default': // ------------------------------
    
    require_once $controller->working_dir . 'inc/default.php';
    $data = unserialize($data);    
    
    $row = array();
    foreach($data['name'] as $k => $v) {
        if($v['id'] == $rq->id) {
            $row = $v;
        }
    }
    
    foreach($data['priv'] as $k => $v) {
        if($v['priv_name_id'] == $rq->id) {
            $_priv[$v['priv_module_id']] = $v;
            if(isset($v['what_priv'])) {
                $_priv[$v['priv_module_id']]['what_priv'] = explode(',', $v['what_priv']);
            }
            if(isset($v['optional_priv'])) {
                $_priv[$v['priv_module_id']]['optional_priv'] = unserialize($v['optional_priv']);
            }
        }
    }
    
    if($row) {
        $row = $rp->stripVarsValues($row, false);
        $_priv = $rp->stripVarsValues($_priv, false);
        
        $obj->set($row);
        $obj->setPriv($_priv);
        $obj->set('name', '');
        $obj->set('description', NULL);
        
        $manager->save($obj);
        $controller->go();        
    }
    
    $controller->goPage('main');

    break;    

    
case 'clone': // ------------------------------
case 'update': // ------------------------------
case 'insert': // ------------------------------
    
    $editable = true;
    if(isset($rp->submit)) {
        
        if(APP_DEMO_MODE) {
            $controller->go('not_allowed_demo', true); 
        }
        
        $is_error = $obj->validate($rp->vars);
                
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            $obj->setPriv($rp->vars['priv']);
        
        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
            $obj->setPriv($rp->vars['priv']);
            
            // set status active for not editable (admin)
            if(!$manager->isPrivEditable($rq->id)) {
                $obj->set('active', 1);
            }

            $manager->save($obj);
            
            $controller->go();
        }
        
    } elseif(in_array($controller->action, array('update', 'clone'))) {
    
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data, true);
        $obj->set($data, false, $controller->action);
        
        if($manager->isPrivEditable($rq->id)) {
            $editable = true;
            $obj->setPriv($manager->getPrivRules($rq->id));
        } else {
            $editable = false;
            $obj->set('sort_order', 1);
        }
    }
    
    $form = ($editable) ? 'PrivView_form_rule' : 'PrivView_form';
    $view = $controller->getView($obj, $manager, $form, $editable);
    
    break;


default: // ------------------------------------

    // sort order
    if(isset($rp->submit)) {
        $manager->saveSortOrder($rp->sort_id);
    }
    
    $view = $controller->getView($obj, $manager, 'PrivView_list');
}
?>