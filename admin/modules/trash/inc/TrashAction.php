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


class TrashAction
{
    
    static function factory($type) {
        $class = self::factoryStatic($type);
        return new $class;
    }

    
    static function factoryStatic($type) {
        $class = 'TrashAction_' . $type;
        $file = 'TrashAction_' . $type . '.php';
        return $class;
    }


    function validate($entry_obj, $values) {

        $v = new Validator($values);
        $v->display_all = false;
        $v->required_set = true;
        
        $v->csrf();
     
        if($v->getErrors()) {
            $entry_obj->errors =& $v->getErrors();
            return true;
        }
    }

    
    static function getTitle($type, $obj_str) {
        $class = self::factoryStatic($type);
        return $class::getTitleStr($obj_str);
    }

    
    static function getTitleStr($obj_str) {
        $search = '#s:5:"title";s:\d+:"(.*?)";s:4:"body";#';
        preg_match($search, $obj_str, $matches);
        return (!empty($matches[1])) ? $matches[1] : '';
    }
    
    
    function setNewValues(&$entry_obj, $values) {
        $entry_obj->set($values);
    }
    
    
    function deleteOnTrashEmpty($record_data = array()) {
        $this->emanager->deleteOnTrashEmpty($record_data);
    }
    
    
    function deleteOnTrashEntry($record_id, $record_data = array()) {
        $this->emanager->deleteOnTrashEntry($record_id, $record_data);
    }
    
    
    function validateObj($entry_obj) {}
        
}
?>