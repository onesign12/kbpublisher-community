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


class KBClientAction_entry_add extends KBClientAction_common
{
    
    function &execute($controller, $manager) {
        
        // no category id
        if(!$controller->category_id) {
            $controller->go();
        }        
        
        // does not matter why no category, deleted, or inactive or private
        if(!isset($manager->categories[$controller->category_id])) {
            $controller->goStatusHeader('404');
        }
        
        // if allowed
        $allowed = $manager->isEntryAddingAllowedByUser($controller->category_id);
        if($allowed !== true) {
            $controller->goAccessDenied('entry', $controller->category_id);
        }
        
        $view = &$controller->getView();
        
        $this->rp->setHtmlValues('body');
        $this->rp->setCurlyBracesValues('body');
        $this->rp->setSkipKeys(array('schedule', 'schedule_on'));
        
        $obj = new KBEntry;
        $emanager = new KBEntryModel;
        
        $view->emanager = $emanager;
        $view->eobj = $obj;
        
        if(isset($this->rp->category)) {
            
            $errors = $obj->validate($this->rp->vars, $emanager);
            
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
            
            } else {
                $this->rp->stripVars();
                
                $obj->set($obj->get());
                $obj->populate($this->rp->vars, $emanager);
                $obj->set('body_index', $emanager->getIndexText($this->rp->vars['body']));
                
                $entry_id = $emanager->save($obj);
                
                if(!empty($this->rp->vars['id_key'])) {
                    $emanager->deleteAutosaveByKey($this->rp->vars['id_key']);
                }
                
                UserActivityLog::add('article', 'create', $entry_id);
                if(!in_array($obj->get('active'), explode(',', $manager->entry_published_status))) { // not visible anymore
                    $controller->go('index', $this->category_id, false, 'entry_created', 0, 1);
                }
                
                $controller->go('success_go', $this->category_id, $entry_id, 'entry_created');
            }
        }
        
        return $view;
    }
        
}
?>