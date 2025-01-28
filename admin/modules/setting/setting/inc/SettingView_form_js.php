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

class SettingView_form_js extends AppView
{
    
    var $tmpl = 'form.html';
    
    
    function execute(&$obj, &$manager) {
        
        $form_data = $this->parseMultiIni($this->template_dir . 'form.ini');    
        
        $parser = &$manager->getParser();
        $setting_msg = $parser->getSettingMsg($manager->module_name);
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors, $manager->module_name));
        $tpl->tplAssign('js_error', $this->getErrorJs($obj->errors));
        
        $r = new Replacer();
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        $rows = &$manager->getRecords();
        //echo '<pre>', print_r($rows, 1), '</pre>';
        
        $i = 1;
        foreach($rows as $group_id => $group) {
            if(!empty($setting_msg['group_title'])) {
                $tpl->tplSetNeeded('block/group');
                if($i != 1) { $tpl->tplSetNeeded('block/group_delim'); }
                $i++;
            }
    
            foreach($group as $setting_key => $v) {
                
                $setting_key = trim($setting_key);
                $group_title_msg = $setting_msg['group_title'][$v['group_id']];  
                $v['required_class'] = ($v['required']) ? 'required' : '';
                $v['id'] = $setting_key;
                
                if($v['input'] == 'checkbox' && $obj->get($setting_key) == 1) {
                    $v['checked'] = 'checked';
                
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
                    
                    $v['value'] = $obj->get($setting_key);
                }             
                
                
                // here we can change some values
                $v['value'] = $parser->parseOut($setting_key, $v['value']);                
                
                
                if($parser->parseForm($setting_key, 'check')) {
                    $field = $parser->parseForm($setting_key, 
                                               $v, 
                                               $r->parse($form_data[$v['input']], $v), 
                                               $setting_msg);
                } else {                
                    $field = $r->parse($form_data[$v['input']], $v);
                }
                
                
                $tpl->tplAssign('title_msg', $setting_msg[$v['setting_key']]['title']);
                $tpl->tplAssign('description_msg', $setting_msg[$v['setting_key']]['descr']);            
                $tpl->tplAssign('form_input', $field);
 
                $tpl->tplParse($v, 'block/row');
                 
            }
            
            $tpl->tplSetNested('block/row');
            $tpl->tplAssign('group_title_msg', $group_title_msg);
            
            $tpl->tplAssign('display_style', 'none');
            $tpl->tplAssign('image_path', 'images/icons/plus.gif');
            $tpl->tplAssign('status_msg', 'Expand');
            
            $tpl->tplAssign('block_id', 'block_' . $i);
            $tpl->tplParse($v, 'block');      
        }
            
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($obj->get());
        //$tpl->tplAssign($this->msg);
        $tpl->tplAssign('custom_text', $parser->getCustomFormHeader());
        $tpl->tplAssign('submit_buittons', $parser->parseSubmit($this->template_dir, $this->msg));
        
        $tpl->tplAssign('collapse_msg', 'Collapse');
        $tpl->tplAssign('expand_msg', 'Expand');
        $tpl->tplParse();
        
        
        return $tpl->tplPrint(1);
    }
    
    
    // parse multilines ini file
    // it will skip all before defining first [block] 
    function parseMultiIni($file, $key = false) {
        return WebUtil::parseMultiIni($file, $key);
    }
}
?>