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


class SettingViewWizard_setting extends AppView
{
    
    var $tmpl = 'form_wizard_setting.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('start_wizard_msg.ini');

        $form_data = SettingView_form::parseMultiIni($this->template_dir . 'form.ini');
        $popup_link = $this->getLink('all');
        
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors, $manager->module_name));
        $tpl->tplAssign('js_error', $this->getErrorJs($obj->errors));
        
        
        $r = new Replacer();

        $select = new FormSelect();
        $select->select_tag = false;
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm2'));
        
        $rows = $manager->getCommonGroupRecords($this->manager->wizard_group_id);
        
        $sort = $this->getSortOrder($rows, $manager);
        array_multisort($sort, SORT_NATURAL, $rows);
        
        // echo '<pre>', print_r($rows, 1), '</pre>';die();
        
        foreach($rows as $v) {
            $module_name = $manager->getModuleName($v['module_id']);
            
            $manager->loadParser(false, $module_name);
            $parser = $manager->parser;
            
            $setting_msg = $parser->getSettingMsg($module_name);

            $setting_key = trim($v['setting_key']);
            $v['group_title_msg'] = @$setting_msg['group_title'][$v['group_id']];
            $v['required_class'] = ($v['required']) ? 'required' : '';
            $v['id'] = $setting_key;
            if($ioptions = $parser->parseInputOptions($setting_key, @$v['value'])) {
                $v['options'] .= ' ' . $ioptions;
            }
            
            if($v['input'] == 'checkbox') {
                $v['checked'] = @($obj->get($setting_key)) ? 'checked' : '';
                $v['value'] = @($obj->get($setting_key)) ? 1 : 0;
            
            } elseif ($v['input'] == 'select') {
                
                $lang_range = $setting_msg[$v['setting_key']];
                unset($lang_range['title'], $lang_range['descr']);
                
                // we have options in lang file
                if(isset($lang_range['option_1'])) {
                    
                    if($v['range'] == 'dinamic') {
                        $v['range'] = $parser->parseSelectOptions($v['setting_key'], $lang_range);
                    } else {
                       
                        $options = $parser->parseSelectOptions($v['setting_key'], $lang_range);
                        foreach($v['range'] as $k1 => $v1) {
                            $value = (current($options)) ? trim(current($options)) : $v1;
                            $v['range'][trim($k1)] = $value;
                            next($options);
                        }    
                    }
        
                // full dinamic generate
                } else {
                    $v['range'] = $parser->parseSelectOptions($v['setting_key'], $v['range']);
                }

                
                //if multiple
                if(strpos($v['options'], 'multiple') !== false) {
                    $v['array_sign'] = '[]';
                }
                
                $select->setRange($v['range']);
                $v['value'] = $select->select($obj->get($setting_key));
                unset($v['range']);
            
            } else {
                $v['value'] = @$obj->get($setting_key);
            }             
            
            
            // here we can change some values
            $v['value'] = $parser->parseOut($setting_key, $v['value']);
            if($v['input'] == 'checkbox') {
                $v['checked'] = ($v['value']) ? 'checked' : '';
            }
            
            if($v['input'] == 'checkbox_btn') {
                $v['checked'] = ($v['value']) ? 'checked' : '';
                $v['url'] = $this->controller->getCurrentLink();
            }
            
            if($parser->parseForm($setting_key, 'check')) {
                $field = $parser->parseForm($setting_key, 
                                           $v, 
                                           $r->parse($form_data[$v['input']], $v), 
                                           $setting_msg);
            } else {                
                $field = $r->parse($form_data[$v['input']], $v);
            }
            
            $msg_key = $parser->parseMsgKey($v['setting_key']);
            
            $title = $parser->parseTitle($msg_key, @$setting_msg[$msg_key]['title']);
            $tpl->tplAssign('title_msg', $title);

            $desc = $parser->parseDescription($msg_key, $setting_msg[$msg_key]['descr']);
            if ($desc) {
                $tpl->tplSetNeeded('row/description');
                $tpl->tplAssign('description_msg', $desc);
            }

            $tpl->tplAssign('form_input', $field);
            $tpl->tplAssign('id', $setting_key);

            $tpl->tplParse(array_merge($v, $this->msg), 'row');
        }
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }


    function getSortOrder($rows, $manager) {
        $sort = array(
            34 => '6.1',
            154 => '6.2'
        );
        
        foreach (array_keys($rows) as $k) {
            $row = $rows[$k];
            if (!empty($sort[$row['name']])) {
                $rows[$k]['sort_order'] = $sort[$row['name']];
            }
        }
        
        $data = $manager->getValuesArray($rows, 'sort_order');
        return $data;
    }
    
	
	function ajaxValidateForm2($values, $options) {
        
	    $values2 = array();
        $keys = $this->manager->getWizardGroupSettingKeys($this->manager->wizard_group_id);
        
        foreach($keys as $module_id => $v) {
            foreach($v as $setting_id => $setting_name) {
                $values2[$module_id][$setting_name] = @$values['values'][$setting_id]; 
            }
        }
        
		return parent::ajaxValidateForm($values2, $options);
	}
	
}
?>