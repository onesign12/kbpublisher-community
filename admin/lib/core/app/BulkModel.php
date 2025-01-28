<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007-2021 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+


class BulkModel
{

    var $model;
    var $actions = array();
    var $actions_extra = array();
    var $actions_allowed = array();
    var $actions_immidiate = array('rate_reset', 'hits_reset', 'public', 'delete', 'trash');
    var $msg_key = 'bulk_kbentry'; // key in bulk_msg.ini
    
    // 1 = allowed for full priv only, 2 = also allowed for self priv
    var $bulk_access = 1; 
    // var $entry_type = 0;
    // var $tbl = 0;
    
    
    function __construct() {
        
    }
    
    
    function setManager(&$model) {
        $this->model = &$model;
        $this->model->delete_mode = 2; // allowed by ids
        // $this->entry_type = (isset($model->entry_type)) ? $model->entry_type : 0;
        // $this->tbl = $model->tbl;
    }
    

    function setActionsAllowed($manager, $priv, $allowed = array()) {
    
        $actions = $this->getActionAllowedCommon($manager, $priv, $allowed);
        
        $this->actions_allowed = array_keys($actions);
        return $this->actions_allowed;        
    }
    
    
    function getActionsAllowed() {
        return $this->actions_allowed;
    }
    
    
    function removeActionAllowed($action) {
        $key = array_search($action, $this->actions_allowed);
        if($key !== false) {
            unset($this->actions_allowed[$key]);
        }
    }
    
    
    function isActionAllowed($action) {
        return (in_array($action, $this->actions_allowed)); 
    }


    function getActionsMsg() {
        
        $range = array();
        $msg = AppMsg::getMsg('bulk_msg.ini', false, $this->msg_key);
        $actions = $this->getActionsAllowed();        
        
        foreach($actions as $k => $v) {
            $range[$v] = $msg[$v];
        }
        
        return $range;
    }
    
    
    function getSubActionSelectRange($keys, $msg_key) {
        
        $range = array();
        $msg = AppMsg::getMsg('bulk_msg.ini', false, $msg_key);
        
        foreach($keys as $key) {
            $msg_ = (isset($msg[$key])) ? $msg[$key] : $key;
            $range[$key] = $msg_;
        }
        
        return $range;        
    }
        
    
    function getActionAllowedCommon($manager, $priv, $allowed = array()) {
        
        $actions = array_flip($this->actions);
        
        // here we know we show only self records
        // and $bulk_access = 2 - allowed to use bulk for self records
        if($priv->isPrivConcrete('self_select') && $this->bulk_access == 2) {
            $pref = 'self_';
        } else {
            $pref = '';
        }
        
        
        if(!$priv->isPrivConcrete($pref . 'delete') || $priv->isPrivStatusActionAny('delete')) {
        
            if(isset($actions['delete'])) {
                unset($actions['delete']);
            }
            
            if(!empty($this->actions_extra['delete'])) {
                foreach($this->actions_extra['delete'] as $k => $v) {
                    unset($actions[$v]);
                }
            }            

            if(isset($actions['trash'])) {
                unset($actions['trash']);
            }
            
            if(!empty($this->actions_extra['trash'])) {
                foreach($this->actions_extra['trash'] as $k => $v) {
                    unset($actions[$v]);
                }
            }            
        }
        
            
        // no need to check isPrivStatusActionAny because statuses select generated for allowed statuses only
        // if(!$priv->isPrivConcrete($pref . 'status') || $priv->isPrivStatusActionAny('status')) {
        if(!$priv->isPrivConcrete($pref . 'status')) {
            
            if(isset($actions['status'])) {
                unset($actions['status']);
            }
            
            if(!empty($this->actions_extra['status'])) {
                foreach($this->actions_extra['status'] as $k => $v) {
                    unset($actions[$v]);
                }
            }        
        }
        
            
        // no update priv or restriction to some statuses - not allowed bulk actions
        if(!$priv->isPrivConcrete($pref . 'update') || $priv->isPrivStatusActionAny('update')) {
            foreach($actions as $k => $v) {
                
                // not to remove some actions if they allowed 
                // if(in_array($k, array('status', 'delete'))) { 
                    // continue; 
                // }
                
                unset($actions[$k]);
            }
        }        
        // echo '<pre>' . print_r($actions, 1) . '</pre>';
        // modules
        $modules = [
            'tags' => ['tag'], 
            'private' => ['private', 'public'], 
            'schedule' => ['schedule'], 
            'mustread' => ['mustread'],
            'fields' => ['custom']
        ];
        foreach($modules as $module => $mactions) {
            if(!AppPlugin::isPlugin($module)) {
                foreach($mactions as $v) {
                    unset($actions[$v]);
                }
            }
        }
        // echo '<pre>' . print_r($actions, 1) . '</pre>';
        // exit;
        
        if($allowed) {
            foreach($actions as $k => $v) {
                if(!in_array($k, $allowed)) {
                    unset($actions[$k]);
                }
            }
        }
        
        return $actions;
    }
    

    function updateSphinxAttributes($attribute, $value, $ids) {
        return AppSphinxModel::updateAttributes($attribute, $value, $ids, $this->model->entry_type);
    }
    
    
    function validate($values) {
        $v = new Validator($values);
        $v->csrf();
        return ($v->getErrors());
    }
    
}
?>