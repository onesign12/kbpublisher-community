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

class FileEntry extends AppObj
{
    
    var $error;
    var $success_files = array();    
    
    var $properties = array('id'             => NULL,
                            'category_id'    => 0,
                            'filename'       => '',
                            'filename_index' => '',
                            'filename_disk'  => '',
                            'meta_keywords'  => '',
                            'filesize'       => '',
                            'filetype'       => '',
                            'title'          => '',
                            'description'    => '',
                            'description_full' => '',
                            'filetext'       => '',
                            'md5hash'        => '',
                            'comment'        => '',
                            'downloads'      => 0,
                            // 'history_comment'    => '',
                            'date_posted'    => '',
                            // 'date_updated'   => '',
                            'directory'      => '',
                            'sub_directory'  => '',
                            'author_id'      => '',
                            'updater_id'     => '',
                            'sort_order'     => 'sort_end',
                            'private'        => 0,
                            'addtype'        => 1, // 1 - uploaded, 2 = local, 3 - s3
                            'active'         => 1
                            );
    
    //, 'author_id', 'updater_id', 'date_posted'
    var $hidden = array(
        'id', 'author_id', 'updater_id', 
        'filename', 'filesize', 'filetype', 'md5hash', 'downloads',
        'date_posted', 'directory', 'sub_directory', 'addtype', 'filename_index', 'filename_disk');
        
    var $reset_on_update = array('updater_id');    
    
    var $reset_on_clone = array(
        'id', 'title', 'filename', 'filesize', 'filetype', 'md5hash', 'downloads',
        'date_posted', 'author_id', 'sort_order',
        'directory', 'sub_directory', 'addtype', 'filename_index', 'filename_disk');
    
    var $category = array();
    var $text;
    var $role_read = array();
    var $role_write = array();
    var $sort_values = array();
    var $author = array();
    var $updater = array();    
    var $schedule = array();
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
    
    function getRoleRead() {
        return $this->role_read;
    }
    
    function setRoleRead($role) {
        $this->role_read = $role;
    }
        
    function getRoleWrite() {
        return $this->role_write;
    }
    
    function setRoleWrite($role) {
        $this->role_write = $role;
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
    
    function setCustom($val) {
        $this->custom = &$val;
    }  
      
    function getCustom() {
        return $this->custom;
    }
      
    // function isUploadedFile() {
    //     return ($this->get('addtype') = 1);
    // }
        
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
    
    
    function validate($values, $action, $manager) {
        
        $required = array();
        
        if(in_array($action, array('insert', 'clone')) && empty($values['id'])) {
            $required[] = 'file';
            
            foreach ($_FILES as $file) {
                if ($file['name']) {
                    $values['file'] = $file['name']; 
                }
            }
        }
        
        $required[] = 'category';
                                            
        $v = new Validator($values, true);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
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
    
    
    function getValidate($values) {
        $ret = array();
        $ret['func'] = array($this, 'validate');
        
        $action = (!empty($values['id'])) ? 'update' : 'action';
        $ret['options'] = array($values, $action, 'manager');
        return $ret;
    }
    
    
    function validateFile($values, $manager) {
		
        $upload = new Uploader;    
        $upload->setAllowedExtension($manager->setting['file_allowed_extensions']);
        $upload->setDeniedExtension($manager->setting['file_denied_extensions']);
        $upload->setMaxSize($manager->setting['file_max_filesize']);
        
        $errors = $upload->validate($values);
        if(!empty($errors)) {
			$v = new Validator($values, true);
            $error_msg = Uploader::getErrorText($errors);
            foreach($error_msg as $msg) {
				$v->setError($msg, 'file', 'file', 'custom');
            }

			$this->errors = $v->getErrors();
            return true;
        }
    }
    
    
    function getValidateFile($values) {
        $ret = array();
        $ret['func'] = array($this, 'validateFile');
        $ret['options'] = array($values, 'manager');
        return $ret;
    }
    
    
    function restore($manager) {
        
        foreach($this->getSchedule() as $num => $v) {
            $v['date'] = strtotime($v['date']);
            $this->setSchedule($num, $v);
        }

        $tag = $this->getTag();
        if(!empty($tag)) {
            $ids = implode(',', $tag);
            $this->setTag($manager->tag_manager->getTagByIds($ids));
        }
    }
    
    
    function collect($id, $data, $manager, $action = 'save') {
        
        $this->set($data, false, $action);
        
        if($action != 'clone') {
            $this->setSortValues($manager->getSortOrderByEntryId($id));
        }
        
        $this->setCategory($manager->getCategoryById($id));
        
        $this->setCustom($manager->cf_manager->getCustomDataById($id));
        $this->setTag($manager->tag_manager->getTagByEntryId($id));
        
        $this->setRoleRead($manager->getRoleReadById($id));
        $this->setRoleWrite($manager->getRoleWriteById($id));
                
        foreach($manager->getScheduleByEntryId($id) as $num => $v) {
            $this->setSchedule($num, $v);
        }
 
        // add author/updater to forms
        if($action != 'save') {
            $this->setAuthor($manager->getUser($data['author_id']));
            
            $ddiff = $data['tsu'] - $data['ts'];
            if($ddiff > $manager->update_diff) {
                $this->setUpdater($manager->getUser($data['updater_id']));
                $this->set('date_updated', $data['date_updated']);
            }
        }
        
        // when we saved serialized $obj, make $obj the same as we save in db
        if($action == 'save') {
            $this->set('date_updated', $data['date_updated']);
            $this->setTag(array_keys($this->getTag()));
        }

        return $this;
    }
    
    
    // from data
    function populate($data, $manager, $to_form = false) {
        
        $this->set($data);
        
        if(!empty($data['sort_values'])) {
            $this->setSortValues($data['sort_values']);
        }

        if(!empty($data['category'])) {
            $this->setCategory($data['category']);
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

        // custom
        if(!empty($data['custom'])) {
            $this->setCustom($data['custom']);
        }
        
        if(!empty($data['addtype'])) {
            $this->set('addtype', (int) $data['addtype']);
        }
    }
    
    
    function populateFile($data, $manager, $extract = true) {
        
        $this->set('filename', addslashes($data['name']));
        $this->set('directory', $data['directory']);
        $this->set('filesize', $data['size']);
        $this->set('filetype', addslashes($data['type']));
        $this->set('md5hash', (isset($data['md5hash'])) ? $data['md5hash'] : md5_file($data['to_read']));
        $this->set('filename_index', addslashes($manager->getFilenameIndex($data['name'])));
        $this->set('filename_disk', addslashes($data['name_disk']));

        if(!empty($data['sub_directory'])) {
            $this->set('sub_directory', $data['sub_directory']);
        }

        if(!empty($data['addtype'])) {
            $this->set('addtype', (int) $data['addtype']);
        }

        if(!empty($manager->setting['file_extract']) && $extract) {
            $text = $manager->extractFileText($data['to_read'], $manager->setting);
            $this->set('filetext', addslashes($text));
        }
    }
    
}
?>