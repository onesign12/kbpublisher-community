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

class StuffEntry extends AppObj
{
    
    var $error;
    
    var $properties = array('id'            => NULL,
                            'category_id'    => 0,
                            'filename'        => '',
                            'filesize'        => '',
                            'filetype'         => '',
                            'filedata'      => '',
                            'title'         => '',
                            'description'    => '',
                            'date_posted'    => '',
                            'author_id'        => '',
                            'updater_id'    => '',
                            'active'        => 1
                            );
    
    var $hidden = array('id', 'author_id', 'updater_id', 'filename', 'filesize', 'filetype', 'date_posted');
    var $reset_on_update = array('updater_id');
    
    var $author = array();
    var $updater = array();    
    
    
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
    
        
    function _callBack($property, $val) {
    
        if($property == 'date_posted' && !$val) {
            $val = date('Y-m-d H:i:s');
        
        } elseif($property == 'author_id' && !$val) {
            $val = AuthPriv::getUserId();
        
        } elseif($property == 'updater_id' && !$val) {    
            $val = AuthPriv::getUserId();
        }
        
        return $val;
    }
    
    
    function validate($values, $action) {
        
        $required = array('title');
        
        if($action == 'insert') {
            $required[] = 'file';
            
            if (!empty($_FILES['file_1']['name'])) {
                $values['file'] = $_FILES['file_1']['name'];
            }
        }
        
        $v = new Validator($values, true);
        $v->csrf();

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }
    
    
    function getValidate($values) {
        $ret = array();
        $ret['func'] = array($this, 'validate');
        $ret['options'] = array($values, 'action');
        return $ret;
    }
}
?>