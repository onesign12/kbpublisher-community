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
// $rp->setSkipKeys('cond'); // to skip not strip html
// $rp->setSkipKeys('action');


$obj = new EmailParserEntry;
$manager = new EmailParserEntryModel();

if(isset($manager->trigger_types[$controller->page]) && 
   isset($manager->entry_types[$controller->sub_page])) {
       
    $manager->trigger_type = $manager->trigger_types[$controller->page];
    $manager->entry_type = $manager->entry_types[$controller->sub_page];

    $obj->set('trigger_type', $manager->trigger_type);
    $obj->set('entry_type', $manager->entry_type);
    
} else {
    die('Wrong page');
}


$priv->setCustomAction('clone', 'insert');
$priv->setCustomAction('default', 'insert');
$priv->setCustomAction('dump', 'insert');
$priv->setCustomAction('entry', 'select');
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
    
    
case 'entry': // ------------------------------
    
    $type = substr($rq->item, 7);
    
    switch ($type) {
        case 'article':
        case 'draft':
        	
            $eobj = new KBEntry;
            $emanager = new KBEntryModel;
    
        	break;
        
        case 'news':
            
            $eobj = new NewsEntry;
            $emanager = new NewsEntryModel;
            
            break;
    }
    
    if(isset($rp->submit)) {
        if ($type == 'news') {
            $date_posted = $rp->vars['date_posted'];
            $rp->vars['date_posted'] = date('Ymd');
        }
       
        if ($type == 'draft') {
           
            $draft_obj = new KBDraft;
            $is_error = $draft_obj->validate($rp->vars, $emanager);
           
        } else {
            $is_error = $eobj->validate($rp->vars, $emanager);
        }
       
        if($is_error) {
            $rp->stripVars(true);
            $eobj->populate($rp->vars, $emanager, true);
            
        } else {
            $rp->stripVars();
            $eobj->populate($rp->vars, $emanager);
            
            $author_id = (!empty($rp->vars['author_id'])) ? $rp->vars['author_id'] : null;
            $eobj->set('author_id', $author_id);
            $eobj->set('updater_id', $author_id);
            
            if (!empty($rp->vars['email_sender'])) {
                $eobj->set('check_sender', true);
            }
            
            if (isset($date_posted)) {
                $eobj->set('date_posted', $date_posted);
            }
            
            $_SESSION['email_rule_'][$rq->field_name][$rq->field_id][$type] = serialize($eobj);
            
            $_GET['saved'] = 1;
            $controller->setMoreParams('saved');
        }
        
    } elseif (!isset($rq->ajax)) {
        $use_default = false;
        
        if (!empty($_SESSION['email_rule_'][$rq->field_name][$rq->field_id][$type])) {
            $eobj = unserialize($_SESSION['email_rule_'][$rq->field_name][$rq->field_id][$type]);
            
        } elseif (!empty($rq->id)) {
            $data = $manager->getById($rq->id);
            $data['action'] = TriggerParser::unpack($data['action']);
            
            if (!empty($data['action'][$rq->field_id]) && $data['action'][$rq->field_id]['item'] == $rq->item) {
                $eobj = TriggerParser::unpack($data['action'][$rq->field_id]['rule'][0]);
                
            } else {
                $use_default = true;
            }
            
        } else {
            $use_default = true;
        }
        
        if ($use_default) {
            $eobj->set('title', '[message.subject]');
            $eobj->set('body', '[message.content]');
            $eobj->set('author_id', AuthPriv::getUserId());
            
            if ($type == 'news') {
                 $eobj->set('date_posted', '[date_created]');
            }
        }
        
    }
    
    $view = $controller->getView($eobj, $emanager, 'EmailParserEntryView_obj_' . $type);
    
    break;
    
case 'clone': // ------------------------------
case 'update': // ------------------------------
case 'insert': // ------------------------------
    
    if(isset($rp->submit)) {
        
        $is_error = $obj->validate($rp->vars, $manager);
        
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            $obj->set('trigger_key_temp', $obj->get('trigger_key')); // to copy predefined 
        
        } else {
            
            $rp->stripVars();
            $obj->set($rp->vars);
            
            $options = array(
                'mailbox_id' => $rp->mailbox_id,
            );
            
            $obj->set('options', serialize($options));
            
            $cond = RequestDataUtil::stripslashes($rp->cond);
            $obj->set('cond', addslashes(TriggerParser::pack($cond)));
            
            
            if (!empty($rq->id)) {
                $data = $manager->getById($rq->id);
                $data['action'] = TriggerParser::unpack($data['action']);
            }
            
            $actions = RequestDataUtil::stripslashes($rp->action);
            foreach (array_keys($actions) as $k) {
                $approval = (!empty($actions[$k]['rule'][0])) ? 1 : 0;
                
                $actions[$k]['rule'] = array();
                
                if ($actions[$k]['item'] == 'stop') {
                    continue;
                }
                
                $type = substr($actions[$k]['item'], 7);
                
                if (!empty($_SESSION['email_rule_'][$rp->id_key][$k][$type])) {
                    $actions[$k]['rule'][] = $_SESSION['email_rule_'][$rp->id_key][$k][$type];
                    
                } elseif (!empty($rq->id)) {
                    $actions[$k]['rule'][] = $data['action'][$k]['rule'][0];
                }
                
                if ($approval) {
                    $actions[$k]['rule'][] = $approval; 
                }
            }
            
            unset($_SESSION['email_rule_'][$rp->id_key]);
            $obj->set('action', addslashes(TriggerParser::pack($actions)));
            
            if(in_array($controller->action, array('insert', 'clone'))) {
                $max_sort_order = $manager->getMaxSortOrder();
                $obj->set('sort_order', $max_sort_order + 1);
            }
            
            $manager->saveAddUpdate($obj);
            
            $return = $controller->getLink('this', 'this', 'this', false, array('filter[mid]' => $rp->mailbox_id));
            $controller->setCustomPageToReturn($return, false);
            
            $controller->go();
        }
        
    } elseif(in_array($controller->action, array('update', 'clone'))) {
    
        $data = $manager->getById($rq->id);
        $data['cond'] = TriggerParser::unpack($data['cond']);
        $data['action'] = TriggerParser::unpack($data['action']);
        // echo '<pre>', print_r($data, 1), '</pre>';
        
        $options = unserialize($data['options']);
        
        $rp->stripVarsValues($data);
        $obj->set($data, false, $controller->action);
        $obj->set('trigger_key_temp', $data['trigger_key']); // to copy predefined
        
        $obj->set('options', $options);
        
    } elseif($controller->action == 'insert') {

        if(!empty($rq->filter['mid'])) {
            $options = array('mailbox_id' => $rq->filter['mid']);
            $obj->set('options', $options);
        }
    }
    
    $view = $controller->getView($obj, $manager, 'EmailParserEntryView_form');

    break;
    
    
case 'default': // ------------------------------
    
    if (empty($rq->filter['mid'])) { // test mailbox
        
        $mailbox_manager = new EmailBoxModel;
        $key = $mailbox_manager->getDefaultSqlSettingKey();
        $default_sql = SettingModel::getQuick(20, $key);
        if ($default_sql) {
            $msg = AppMsg::getMsgs('trigger_msg.ini');
            $mailbox_id = $mailbox_manager->runDefaultSql($default_sql, $msg);
        }
        
    } else {
        $mailbox_id = $rq->filter['mid'];
        $manager->deleteTriggerByMailbox($mailbox_id); // empty only one mailbox
    }
    
    $key = $manager->getDefaultSqlSettingKey();
    $default_sql = SettingModel::getQuick(20, $key);
    if ($default_sql) {
        $manager->runDefaultSql($mailbox_id, $default_sql);
    }
    
    $controller->go();
    
    break;


case 'dump': // ------------------------------

    $sql = $manager->getDefaultSql();

    if (!empty($sql[2])) { // automations
        $sql = RequestDataUtil::stripVars($sql[2], array(), 'addslashes');
        $key = $manager->getDefaultSqlSettingKey();
        
        $sm = new SettingModel();
        $setting_id = $sm->getSettingIdByKey($key);
        $sm->updateDefaultValue($setting_id, $sql);
        
        echo stripslashes($sql);
    }

    exit;

    break;
    

default: // ------------------------------------

    // sort order
    if(isset($rp->submit)) {
        $manager->saveSortOrder($rp->sort_id);
    }
    
    $view = $controller->getView($obj, $manager, 'EmailParserEntryView_list');
}
?>