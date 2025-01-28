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
$rp->setSkipKeys(array('data_string'));

$obj = new EmailBox;

$manager =& $obj->setManager(new EmailBoxModel());
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete': // ------------------------------
    
    if($manager->isInUse($rq->id)) {
        $controller->go('notdeleteable_entry', true);
    }
    
    $manager->delete($rq->id);
    
    $manager->setSqlParams(sprintf('AND data_key = "%s"', $obj->get('data_key')));
    $count = $manager->getCountRecords();
    if (!$count) {
        $controller->goPage('tool', 'automation', 'am_email');
    }
    
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
			$obj->set('data_string', $rp->vars);
        
        } else {
            $mailbox_data = $rp->vars;
            $mailbox_data['password'] = EncryptedPassword::encode($mailbox_data['password']);
            
            $obj->set('data_string', addslashes(serialize($mailbox_data)));
            
            $rp->stripVars();
            $obj->set($rp->vars);
            
            $mailbox_id = $manager->save($obj, $controller->action);
            
            if($controller->action == 'insert') {
                
                
                $emanager = new EmailParserEntryModel;
                
                $key = 'default_sql_automation_email';
                $default_sql = SettingModel::getQuick(20, $key);
                
                if ($default_sql) {
                    $emanager->runDefaultSql($mailbox_id, $default_sql);
                }
            }
            
            $controller->go();
        }
        
    } elseif($controller->action == 'update') {
    
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data, true);
        
        $obj->set($data);
        
        $options = unserialize($data['data_string']);
        $options['password'] = \EncryptedPassword::decode($options['password']);
        
        $obj->set('data_string', $options);
    }
    
    $view = $controller->getView($obj, $manager, 'EmailBoxView_form');

    break;
    

default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'EmailBoxView_list');
}
?>