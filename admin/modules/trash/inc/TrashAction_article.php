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


class TrashAction_article extends TrashAction
{
    
    function __construct() {
        $this->emanager = new KBEntryModel;
    }
    
    
    function validate($entry_obj, $values) {

        $v = new Validator($values);
        $v->display_all = false;
        $v->required_set = true;
        
        $v->csrf();
        $v->required('required_msg', array('category'));
     
        if($v->getErrors()) {
            $entry_obj->errors =& $v->getErrors();
            return true;
        }
    }
    
    
    function validateObj($entry_obj) {
        
        $manager =& $this->emanager;
        $v = new Validator($values);
        
        $category_ids = implode(',', $entry_obj->getCategory());
        $manager->cat_manager->setSqlParams(sprintf('AND c.id IN (%s)', $category_ids));
        $categories = $manager->cat_manager->getRecords();
        
        if (empty($categories)) {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_record_incomplete'];
            $msg['body'] = $msgs['note_entry_incomplete'];
            
            $v->setError(BoxMsg::factory('error', $msg, array()), 'category', 'category', 'formatted');
        }
        
        if($v->getErrors()) {
            $entry_obj->errors =& $v->getErrors();
            return true;
        }
    }
    
    
    function restore($entry_obj) {
        
        $id = $entry_obj->get('id');
        
        $manager =& $this->emanager;
        
        $category_ids = implode(',', $entry_obj->getCategory());
        $manager->cat_manager->setSqlParams(sprintf('AND c.id IN (%s)', $category_ids));
        $categories = $manager->cat_manager->getRecords();
        
        $entry_obj->setCategory(array_keys($categories));
        $entry_obj->set('body_index', $manager->getIndexText($entry_obj->get('body')));
        
        $sort_values = $manager->updateSortOrder($id, $entry_obj->getSortValues(),
                                                 $entry_obj->getCategory(), 'insert');
        // checking related entries
        $related_saved = $entry_obj->getRelated();
        if (!empty($related_saved)) {
            $related_ids = implode(',', array_keys($related_saved));
            $manager->setSqlParams(sprintf('AND e.id IN (%s)', $related_ids));
            $related_actual = $manager->getRecords();
            
            $related = array();
            foreach ($related_actual as $v) {
                $related[$v['id']] = $related_saved[$v['id']];
            }
            
            $entry_obj->setRelated($related);
        }
        
        $schedule = $entry_obj->getSchedule();
        foreach (array_keys($schedule) as $num) {
            $schedule[$num]['date'] = date('YmdHi00', $schedule[$num]['date']);
            $entry_obj->setSchedule($num, $schedule[$num]);
        }
        
        TrashAction_article::setEntryRoles($entry_obj, $manager);
        TrashAction_article::setEntryTags($entry_obj, $manager);
        
        $manager->addRecord($entry_obj, $sort_values);
        
        AppSphinxModel::updateAttributes('is_deleted', 0, $id, $manager->entry_type);
        
        return true;
    }
    
    
    function setNewValues(&$entry_obj, $values) {        
        if (!empty($values['category'])) {
            $entry_obj->setCategory($values['category']);
        }
    }
    
    
    function getPreview($entry_obj, $controller) {
        
        $entry_obj = unserialize($entry_obj);
        
        
        $view = new KBEntryView_preview;        
        $view = $view->execute($entry_obj, $this->emanager);
        
        return $view;
    }


    static function setEntryRoles(&$entry_obj, $manager) {
        
        // checking roles
        $role_read = $entry_obj->getRoleRead();
        $role_write = $entry_obj->getRoleWrite();
        $all_roles = $role_read + $role_write;
        
        if (!empty($all_roles)) {
            $role_ids = implode(',', array_keys($all_roles));
            
            $manager->role_manager->setSqlParams(sprintf('AND r.id IN (%s)', $role_ids));
            $roles_actual = $manager->role_manager->getRecords();
            
            foreach (array_keys($role_read) as $k) {
                $role_id = $role_read[$k];
                if (empty($roles_actual[$role_id])) {
                     unset($role_read[$k]);
                }
            }
            
            $entry_obj->setRoleRead($role_read);
            
            foreach (array_keys($role_write) as $k) {
                $role_id = $role_write[$k];
                if (empty($roles_actual[$role_id])) {
                     unset($role_write[$k]);
                }
            }
            
            $entry_obj->setRoleWrite($role_write);
        }
    }
    
    
    static function setEntryTags(&$entry_obj, $manager) {
        
        $tags = $entry_obj->getTag();        
        
        if($tags) {
            $available_tags = $manager->tag_manager->getTagByIds(implode(',', $tags)); 
            $tags = array_intersect($tags, array_keys($available_tags));
            $entry_obj->setTag($tags);
            
            $keywords = implode($manager->tag_manager->getKeywordDelimeter(), $available_tags);
            $keywords = RequestDataUtil::addslashes($keywords);
            $entry_obj->set('meta_keywords', $keywords);
        }
    }
}
?>