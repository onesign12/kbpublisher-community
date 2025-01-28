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


class KBEntry extends AppObj
{
    
    var $properties = array('id'                 => NULL,
                            'category_id'        => 0,
                            'author_id'          => 0,
                            'updater_id'         => 0,
                            'title'              => '',
                            'body'               => '',
                            //'body_index'         => '',
                            'date_posted'        => '',
                            // 'date_updated'       => '',
                            //'date_commented'     => '',
                            'meta_keywords'      => '',
                            'meta_description'   => '',
                            'entry_type'         => 0,
                            'external_link'      => '',
                            'url_title'          => '',
                            // 'history_comment'    => '',
                            'hits'               => 0,
                            'sort_order'         => 'sort_end',
                            'private'            => 0,
                            'active'             => 1
                            );
    
    
    var $hidden = array('id', 'author_id', 'updater_id', 'date_posted', 'hits', 'sort_order');
    var $reset_on_update = array('updater_id');
    var $reset_on_clone = array('id', 'title', 'date_posted', 'author_id', 'hits', 'sort_order');
    
    var $category = array();
    var $sort_values = array();
    var $attachment = array();
    var $related = array();
    var $role_read = array();
    var $role_write = array();
    var $author = array();
    var $updater = array();
    var $schedule = array();
    var $mustread = array();
    var $tag = array();
    var $custom = array();
     
    
    function setSortValues($val) {
        $this->sort_values = &$val;
    }
    
    function getSortValues($category_id = false) {
        if($category_id) {
            return isset($this->sort_values[$category_id]) ? $this->sort_values[$category_id] : 'sort_end';
        } else {
            return $this->sort_values;
        }
    }
            
    function setCategory($val) {
        $val = ($val) ? $val : array();
        $this->category = &$val;
        $this->set('category_id', (is_array($val)) ? current($val) : $val);
    }
    
    function &getCategory() {
        return $this->category;
    }
    
    function setRoleRead($role) {
        $this->role_read = $role;
    }
    
    function getRoleRead() {
        return $this->role_read;
    }
    
    function getRoleWrite() {
        return $this->role_write;
    }
    
    function setRoleWrite($role) {
        $this->role_write = $role;
    }    
    
    function setAttachment($val) {
        $this->attachment = &$val;
    }
    
    function &getAttachment() {
        return $this->attachment;
    }

    function setRelated($val, $ref = false) {
        $this->related = &$val;
    }
    
    function &getRelated() {
        return $this->related;
    }    
    
    function setAuthor($val) {
        $this->author = &$val;
    }
    
    function &getAuthor() {
        return $this->author;
    }    
    
    function setUpdater($val) {
        $this->updater = &$val;
    }
    
    function &getUpdater() {
        return $this->updater;
    }
    
    function setSchedule($key, $val) {
        $this->schedule[$key] = $val;
    }
    
    function getSchedule() {
        return $this->schedule;
    }
    
    function setTag($val) {
        $this->tag = &$val;
    }
    
    function getTag() {
        return $this->tag;
    }
    
    function setMustread($val) {
        $filter = array('user', 'priv');
        foreach($filter as $v) {
            if(isset($val[$v])) {
                $val[$v] = array_filter($val[$v]); // remove empty 
            }
        }
        
        $this->mustread = &$val;
    }

    function getMustread() {
        return $this->mustread;
    }
    
    function setCustom($val) {
        $this->custom = &$val;
    }  
      
    function getCustom() {
        return $this->custom;
    }
    
    
    function _callBack($property, $val) {
        
        if($property == 'id' && !$val) {
            $val = NULL; // if not set after unserialize it is set as '' string, cause db error 
        
        } elseif($property == 'date_posted' && !$val) {
            $val = date('Y-m-d H:i:s');
                
        } elseif($property == 'author_id' && !$val) {
            $val = AuthPriv::getUserId();
        
        } elseif($property == 'updater_id' && !$val) {
            $val = AuthPriv::getUserId();
        
        } elseif($property == 'private') {
            $val = $this->getPrivateValue($val);
        }
        
        return $val;
    }
        
    
    function validate($values, $manager) {
        
        $required = array('category', 'title', 'body');
        
        $v = new Validator($values, false);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
        
        // mustread
        if($error = CommonEntryView::validateMustread($values)) {
            $v->setError($error[0], $error[1], $error[2]);
            $this->errors =& $v->getErrors();                    
            return true;
        }
         
        // custom
        if(AppPlugin::isPlugin('fields')) {
            $entry_cats = RequestDataUtil::addslashes($values['category']);
            $fields = $manager->cf_manager->getCustomField($manager->getCategoryRecords(), $entry_cats);
            $error = $manager->cf_manager->validate($fields, $values);
            
            if($error) {
                $v->setError($error[0], $error[1], $error[2], $error[3]);
                $this->errors =& $v->getErrors();                    
                return true;
            }
        }
    }
    
    
    function collect($id, $data, $manager, $action = 'save') {
        
        $this->set($data, false, $action);
        
        if($action != 'clone') {
            $this->setSortValues($manager->getSortOrderByEntryId($id));
        }
        
        $this->setCategory($manager->getCategoryById($id));
        $this->setRelated($manager->getRelatedById($id));
        $this->setAttachment($manager->getAttachmentById($id));
        
        $this->setCustom($manager->cf_manager->getCustomDataById($id));
        $this->setTag($manager->tag_manager->getTagByEntryId($id));
        
        if(AppPlugin::isPlugin('mustread')) {
            $mustread = $manager->mr_manager->getMustreadByEntryId($id);
            if($mustread) {
                $mustread['id'] = ($action == 'clone') ? NULL : $mustread['id']; // reset mustread id if clone
                $this->setMustread($mustread);
            }
        }
        
        $this->setRoleRead($manager->getRoleReadById($id));
        $this->setRoleWrite($manager->getRoleWriteById($id));
                
        foreach($manager->getScheduleByEntryId($id) as $num => $v) {
            $this->setSchedule($num, $v);
        }
 
        // add author/updater to forms
        if($action != 'save' && $data) {
            $this->setAuthor($manager->getUser($data['author_id']));
            
            $ddiff = $data['tsu'] - $data['ts'];
            if($ddiff > $manager->update_diff) {
                $this->setUpdater($manager->getUser($data['updater_id']));
                $this->set('date_updated', $data['date_updated']);
            }
        }
        
        
        // when we saved serialized $obj, make $obj the same as we save in db
        if($action == 'save' && $data) {
            $this->set('date_updated', $data['date_updated']);
            $this->setAttachment(array_keys($this->getAttachment()));
            // $this->setRelated(array_keys($this->getRelated()));
            $this->setTag(array_keys($this->getTag()));
        }

        return $this;
    }
    
    
    // from saved obj
    function restore($manager) {

        $related = $this->getRelated();
        if(!empty($related)) {
            $related_ = array();
            $ids = implode(',', array_keys($related));
            foreach($manager->getRelatedByIds($ids) as $id => $title) {
                $related[$id]['title'] = $title;
            }
            
            $this->setRelated($related);
        }

        $attachment = $this->getAttachment();
        if(!empty($attachment)) {
            $ids = implode(',', $attachment);
            $this->setAttachment($manager->getAttachmentByIds($ids));
        }

        foreach($this->getSchedule() as $num => $v) {
            $v['date'] = strtotime($v['date']);
            $this->setSchedule($num, $v);
        }

        $tag = $this->getTag();
        if(!empty($tag)) {
            $ids = implode(',', $tag);
            $this->setTag($manager->tag_manager->getTagByIds($ids));
        }
        
        $mustread = $this->getMustread();
        if(!empty($mustread)) {
            $this->setMustread($mustread);
        }
    }


    // from data
    function populate($data, $manager, $to_form = false) {
        
        $this->set($data);
        
        // if(!empty($data['body']) && !$to_form) {
            // $this->set('body_index', $manager->getIndexText($data['body']));
        // }
        
        if(!empty($data['sort_values'])) {
            $this->setSortValues($data['sort_values']);
        }
        
        if(!empty($data['category'])) {
            $this->setCategory($data['category']);
        }

        // do not update date_updated
        if(isset($data['submit_skip'])) {
            $this->set('date_updated', $manager->getById($data['id'])['date_updated']);
        }

        // author to form
        if($to_form && !empty($data['id'])) { // being used in kb_entry
            $entry = $manager->getById($data['id']);
            $this->setAuthor($manager->getUser($entry['author_id']));

            $ddiff = $entry['tsu'] - $entry['ts'];
            if($ddiff > $manager->update_diff) {
                $this->setUpdater($manager->getUser($entry['updater_id']));
                $this->set('date_updated', $entry['date_updated']);
            }
        }
        
        // roles
        if(!empty($data['role_read'])) {
            $this->setRoleRead($data['role_read']);
        }

        if(!empty($data['role_write'])) {
            $this->setRoleWrite($data['role_write']);
        }

        // related
        if(!empty($data['related'])) {
            $r = array();
            $related_ref = (!empty($data['related_ref'])) ? $data['related_ref'] : array();
            
            if($to_form) {
                $ids = implode(',', $data['related']);
                foreach($manager->getRelatedByIds($ids) as $id => $title) {
                    $r[$id]['title'] = $title;
                    $r[$id]['ref'] = (in_array($id, $related_ref)) ? 1 : 0;
                }
            
            } else {
                foreach($data['related'] as $id) {
                    $r[$id]['title'] = '';
                    $r[$id]['ref'] = (in_array($id, $related_ref)) ? 1 : 0;
                }
            }
            
            $this->setRelated($r);
        }
        
        // attachments
        if(!empty($data['attachment'])) {
            if($to_form) {
                $ids = implode(',', $data['attachment']);
                $data['attachment'] = $manager->getAttachmentByIds($ids);
            }
            
            $this->setAttachment($data['attachment']);
        }

        // schedule
        if(!empty($data['schedule_on'])) {
            foreach($data['schedule_on'] as $num => $v) {
                if($to_form) {
                    $data['schedule'][$num]['date'] = strtotime($data['schedule'][$num]['date']);
                } else {
                    $data['schedule'][$num]['date'] = date('YmdHi00', strtotime($data['schedule'][$num]['date']));
                }
                
                $this->setSchedule($num, $data['schedule'][$num]);
            }
        }
        
        // tags
        if(!empty($data['tag'])) {
            $ids = implode(',', $data['tag']);
            
            if($to_form) {
                $this->setTag($manager->tag_manager->getTagByIds($ids));
            } else {
                $keywords = $manager->tag_manager->getKeywordsStringByIds($ids);
                $keywords = RequestDataUtil::addslashes($keywords);
                $this->set('meta_keywords', $keywords);                
                $this->setTag($data['tag']);
            }
            
        } else {
            $this->setTag(array());
        }

        // mustread
        if(!empty($data['mustread']['on']) || !empty($data['mustread']['id'])) {     
            $this->setMustread($data['mustread']);
        }

        // custom
        if(!empty($data['custom'])) {
            $this->setCustom($data['custom']);
        }
        
    }

}
?>