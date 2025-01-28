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

class PrivView_form_rule extends AppView
{
        
    var $tmpl = 'form_rule.html';
    var $padding = 20;
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
        $this->addMsg('privileges_msg.ini');
        
        // fix to add missed words from en
        $priv_other_msg = AppMsg::getMsg('privileges_msg.ini', false, 'priv_other');
        $this->msg['priv_other'] = $priv_other_msg;
        
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        $tpl->tplAssign($this->msg['priv_other']);
        
        // priv rules
        $priv = $obj->getPriv();
        // echo "<pre>"; print_r($priv); echo "</pre>";
        
        // get records
        $rows = $this->stripVars($manager->getPrivModules());
        $tree_helper = $manager->getTreeHelperArray($rows);
        
        // parsed in other way, used for draft
        // in kbp_priv_module stored in extra_priv, kbp_priv_rule in optional_priv
        $special_exra_priv = array('draft');
        
        //cloud
        $hidden_in_cloud = array();
        if(BaseModel::isCloud()) {
            $hidden_in_cloud = BaseModel::getCloudHideTabs();
        }
        
        $emodules = AppPlugin::getPluginsOn();
        
        foreach($tree_helper as $module_id => $level) {
            
            $parent_id = $rows[$module_id]['parent_id'];
            $module_name = $rows[$module_id]['module_name'];
            $v['module_id'] = $module_id;
            
            if(in_array($module_name, $hidden_in_cloud)) {
                continue;
            }
            
            // module disabled        
            if(in_array($module_name, $emodules) && !AppPlugin::isPlugin($module_name)) {
                continue;
            }
            
            // only admin can manage privs, others may read only  
            if($module_name == 'priv') {
                $rows[$module_id]['what_priv'] = array('select');
            }
            
            if($rows[$module_id]['check_priv']) {
                
                $tpl->tplSetNeeded('row/if_row_priv');
                
                $a = array();
                
                // priv values for rule
                $what_priv = array();
                if(isset($priv[$module_id])) {
                    $what_priv = (isset($priv[$module_id]['what_priv'])) ? $priv[$module_id]['what_priv'] : array() ;
                    foreach($what_priv as $priv_value) {
                        if(strpos($priv_value, 'self_') !== false) {
                            $what_priv[] = str_replace('self_', '', $priv_value);
                        }
                    }
                }
                
                // priv module status values
                if($rows[$module_id]['status_priv']) {
                
                    $module_name = $rows[$module_id]['module_name'];
                    $status_model = &PrivStatusModel::factory($module_name, $this->controller->working_dir);
                    $range = $status_model->getStatusSelectRange();
                    
                    $select = new FormSelect();
                    $select->select_tag = false;
                    
                    $statuses = array('status'=>array(), 'update'=>array(), 'delete'=>array());
                    if(!empty($priv[$module_id]['status_priv'])) {
                        $statuses = array_merge($statuses, $priv[$module_id]['status_priv']);
                    }    
                }    
                
                
                $a['num'] = 0;
                foreach($manager->priv_values as $priv_value) {
                    
                    $a['num']++;
                    if(in_array($priv_value, $rows[$module_id]['what_priv'])) {
                    
                        $a['checked'] = (in_array($priv_value, $what_priv)) ? 'checked' : '';
                        $a['self_checked'] = (in_array('self_' . $priv_value, $what_priv)) ? 'checked' : '';
                        
                        $a['priv_value'] = $priv_value;
                        $a['priv_title_msg'] = $this->msg['priv_values'][$priv_value];
                        $a['module_id'] = $module_id;
                        $a['parent_id']    = $parent_id;
                        
                        $pref = '';
                        $a['ch_id'] = $pref . $parent_id . '_' . $module_id;
                        
                        if($rows[$module_id]['own_priv'] && $priv_value != 'insert') {
                            $own_msg = ($rows[$module_id]['own_priv'] == 1) ? 'own_records_msg' : 'own_records2_msg';
                            $a['own_records_caption'] = $this->msg['priv_other'][$own_msg];
                            $tpl->tplSetNeeded('row_priv/self_priv');
                        }
                        
                        $priv_with_status = array('status', 'update', 'delete');
                        if($rows[$module_id]['status_priv'] && in_array($priv_value, $priv_with_status)) {
                                                        
                            $select->setRange($range);
                            $a['status_select'] = $select->select($statuses[$priv_value]);
                            $a['priv_status_msg'] = $this->msg['priv_other']['status_' . $priv_value .'_msg'];

                            $tpl->tplSetNeeded('row_priv/status_priv');
                        }                    
                        
                        
                        // optional priv
                        if($rows[$module_id]['extra_priv']) {
                            
                            $op_values = array();
                            if(!empty($priv[$module_id]['optional_priv'][$priv_value])) {
                                $op_values = $priv[$module_id]['optional_priv'][$priv_value];
                            }
                            
                            foreach($rows[$module_id]['extra_priv'] as $extra_priv) {
                                
                                if(strpos($extra_priv, ':') === false) {
                                    continue;
                                }
                                
                                $extra_priv_key = strtok($extra_priv, ':'); // draft etc.
                                $extra_priv_values = explode(';', substr($extra_priv, strpos($extra_priv, ":") + 1)); //insert, update etc.
                            
                                if(in_array($priv_value, $extra_priv_values)) {
                                                                        
                                    $msg_key = sprintf('%s_records_msg', $extra_priv_key);
                                    $a['optional_caption'] = $this->msg['priv_other'][$msg_key];
                                    $a['optional_checked'] = in_array($extra_priv_key, $op_values) ? 'checked' : '';
                                    $a['optional_priv_value'] = $extra_priv_key;
                               
                                    $tpl->tplSetNeeded('row_priv/optional_priv');
                                }
                            }                            
                        }                    
                        
                        $tpl->tplSetNeeded('row_priv/priv');
                    }
                    
                    $tpl->tplParse($a,'row/row_priv');
                }
                
                $tpl->tplSetNested('row/row_priv');
            }
            
            
            // extra priv
            if($rows[$module_id]['extra_priv']) {
                
                foreach($rows[$module_id]['extra_priv'] as $priv_value) {
                    
                    if(strpos($priv_value, ':') !== false) {
                        continue;
                    }
            
                    $a['num']++;
                    
                    // run once
                    if($a['num'] = 1) {
                        $tpl->tplSetNeeded('row/if_row_priv_extra');
                    }
            
                    // for extar priv we use self_priv_name it means with self, priv_name - no self
                    $priv_self = (strpos($priv_value, 'self') !== false);
                    $priv_value = str_replace('self_', '', $priv_value);
            
                    $a['checked'] = (in_array($priv_value, $what_priv)) ? 'checked' : '';
                    $a['self_checked'] = (in_array('self_' . $priv_value, $what_priv)) ? 'checked' : '';
        
                    $a['priv_value'] = $priv_value;
                    $a['priv_title_msg'] = $this->msg['priv_values'][$priv_value];
                    $a['module_id'] = $module_id;
                    $a['parent_id']    = $parent_id;
                    
                    $pref = '';
                    $a['ch_id'] = $pref . $parent_id . '_' . $module_id;
        
                    if($rows[$module_id]['own_priv'] && $priv_self) {
                        $own_msg = ($rows[$module_id]['own_priv'] == 1) ? 'own_records_msg' : 'own_records2_msg';
                        $a['own_records_caption'] = $this->msg['priv_other'][$own_msg];                        
                        $tpl->tplSetNeeded('row_priv_extra/self_priv_extra');
                    }
        
                    $tpl->tplParse($a,'row/row_priv_extra');
                }
        
                $tpl->tplSetNested('row/row_priv_extra');
            }
            
            
            // apply child
            if($rows[$module_id]['check_priv'] && $level == 0) {
                $tpl->tplSetNeeded('row/apply_child');
                
                $v['ac_checked'] = '';
                if(isset($priv[$module_id]['apply_to_child'])) {
                    $v['ac_checked'] = ($priv[$module_id]['apply_to_child']) ? 'checked' : '';
                }                    
            }
            
            $v['class'] = ($level == 0) ? 'trDarker' : 'trLighter';
            $v['padding'] = $this->padding*$level;
            $block = ($level == 0) ? 'level_0' : 'other_level';
            
            $tpl->tplSetNeeded('row/' . $block);
            $tpl->tplParse(array_merge($v, $rows[$module_id], $this->msg), 'row');
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        if($this->controller->getAction() == 'insert') {
            $obj->set('sort_order', $manager->getMaxPrivLevel() + 1);
        }
        
        if($this->controller->getAction() == 'update' && $obj->get('id') <= 5) {
            $link = $this->getActionLink('default', $obj->get('id'));
            $tpl->tplAssign('default_link', $link);
            $tpl->tplSetNeeded('/default_btn');
        }
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>