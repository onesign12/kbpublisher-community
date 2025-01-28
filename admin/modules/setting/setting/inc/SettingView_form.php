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


class SettingView_form extends AppView
{

    var $tmpl = 'form.html';


    function execute(&$obj, &$manager, $extra_options = array()) {

        $form_data = $this->parseMultiIni($this->template_dir . 'form.ini');

        $parser = &$manager->getParser();
        // $setting_msg = $parser->getSettingMsg($manager->module_name);
        $popup_link = $this->getLink('all');

        $tmpl = ($manager->separate_form) ? $this->tmpl_2 : $this->tmpl;

        $tpl = new tplTemplatez($this->template_dir . $tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors, $manager->module_name));
        $tpl->tplAssign('js_error', $this->getErrorJs($obj->errors));

        if(!empty($_GET['saved']) && !empty($_GET['tkey'])) {
            $tpl->tplAssign('test_key', $_GET['tkey']);
            $tpl->tplSetNeeded('/close_window');
        }

        if (empty($this->controller->getMoreParam('popup'))) {
            $tpl->tplSetNeeded('/filter');
        }

        $group_name = ($this->controller->sub_page) ? $this->controller->sub_page : $this->controller->page;
        $tpl->tplAssign('group_name', $group_name);

        // $r = new Replacer();

        // $select = new FormSelect();
        // $select->select_tag = false;

        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm2'));
        $xajax->registerFunction(array('checkPort', $this, 'ajaxCheckPort'));
        $xajax->registerFunction(array('populateDateFormatSelect', $this, 'ajaxPopulateDateFormatSelect'));

        if (!empty($_GET['sid'])) {
            $_sid = array_map('addslashes', $_GET['sid']);
            $manager->setSqlParams(sprintf('AND id IN (%s)', implode(',', $_sid)));

            $more = array('sid' => $_sid);
            $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
        }

        // $rows = &$manager->getRecords();
        //echo '<pre>', print_r($rows, 1), '</pre>';

        $tpl->tplAssign('rows', $this->parseRows($obj, $manager, $parser, $form_data));

//         $t_id = 0;
//         $i = 1;
//         foreach($rows as $group_id => $group) {
//             if(!empty($setting_msg['group_title'])) {
//                 $tpl->tplSetNeeded('row/group');
// 
//                 $original_group_id = current($group)['group_id'];
//                 $tpl->tplAssign('group_id', $original_group_id);
//                 $tpl->tplAssign('group_num', $i);
//                 $tpl->tplAssign('delim_id', $i);
// 
//                 if($i != 1) { $tpl->tplSetNeeded('block/group_delim'); }
//                 $i++;
//             }
// 
//             $key_last = end($group);
//             $key_last = $key_last['setting_key'];
// 
//             foreach($group as $setting_key => $v) {
// 
//                 if ($setting_key == $key_last) {
//                     $tpl->tplAssign('end', '</div>');
//                     $tpl->tplSetNeeded('row/submit');
//                 } else {
//                     $tpl->tplAssign('end', '');
//                 }
// 
//                 $setting_key = trim($setting_key);
// 
//                 // if (!empty($setting_msg['group_title'][$v['module_id']]) && is_array($setting_msg['group_title'][$v['module_id']])) {
//                 //     $v['group_title_msg'] = $setting_msg['group_title'][$v['module_id']][$v['group_id']];
// 
//                 if (isset($v['user_module_id'])) {
//                     $v['group_title_msg'] = $setting_msg['group_title'][$v['user_module_id']][$v['group_id']];
// 
//                 } else {
//                     $v['group_title_msg'] = $setting_msg['group_title'][$v['group_id']];
//                 }
// 
//                 $v['required_class'] = ($v['required']) ? 'required' : '';
//                 $v['id'] = $setting_key;
//                 if($ioptions = $parser->parseInputOptions($setting_key, @$v['value'])) {
//                     $v['options'] .= ' ' . $ioptions;
//                 }
// 
//                 if($v['input'] == 'checkbox') {
//                     $v['checked'] = @($obj->get($setting_key)) ? 'checked' : '';
//                     $v['value'] = @($obj->get($setting_key)) ? 1 : 0;
// 
//                 } elseif ($v['input'] == 'select') {
// 
//                     $lang_range = $setting_msg[$v['setting_key']];
// 
//                     // we have options in lang file
//                     if(isset($lang_range['option_1'])) {
// 
//                         // keep only options_[num]
// //                         $lang_range = array_filter($lang_range,
// //                             function($k) { return (strpos($k, 'option_') !== false); },
// //                             ARRAY_FILTER_USE_KEY);
//                         foreach(array_keys($lang_range) as $k2) {
//                             if(strpos($k2, 'option_') === false) {
//                                 unset($lang_range[$k2]);
//                             }
//                         }
// 
//                         if($v['range'] == 'dinamic') {
//                             $v['range'] = $parser->parseSelectOptions($v['setting_key'], $lang_range);
// 
//                         } else {
//                             $options = $parser->parseSelectOptions($v['setting_key'], $lang_range);
//                             foreach($v['range'] as $k1 => $v1) {
//                                 $value = (current($options)) ? trim(current($options)) : $v1;
//                                 $v['range'][trim($k1)] = $value;
//                                 next($options);
//                             }
//                         }
// 
//                     // full dinamic generate
//                     } else {
//                         $v['range'] = $parser->parseSelectOptions($v['setting_key'], $v['range']);
//                     }
// 
// 
//                     //if multiple
//                     if(strpos($v['options'], 'multiple') !== false) {
//                         $v['array_sign'] = '[]';
//                     }
// 
//                     $select->setRange($v['range']);
//                     $v['value'] = $select->select($obj->get($setting_key));
//                     unset($v['range']);
// 
//                 } else {
// 
//                     $v['value'] = @$obj->get($setting_key);
//                 }
// 
// 
//                 // here we can change some values
//                 $v['value'] = $parser->parseOut($setting_key, $v['value']);
//                 if($v['input'] == 'checkbox') {
//                     $v['checked'] = ($v['value']) ? 'checked' : '';
//                 }
// 
//                 $v['popup_link'] = $this->getLink('all');
// 
// 
//                 if($v['input'] == 'hidden_btn') {
//                     $v['click_handler'] = sprintf("submitToPopup('%s');", $v['id']);
//                     $v += $parser->parseExtraOut($setting_key, $v['value'], $manager, $this, $v['id']);
//                 }
// 
//                 if($v['input'] == 'checkbox_btn') {
//                     $v['checked'] = ($v['value']) ? 'checked' : '';
// 
//                     if (!empty($_GET['saved'])) {
//                         unset($_GET['saved']);
//                     }
//                     $v['url'] = $this->controller->getCurrentLink();
//                 }
// 
//                 if($v['input'] == 'info') {
//                     $v['url'] = $this->controller->getCurrentLink();
//                 }
// 
//                 if($v['input'] == 'double_checkbox') {
//                     $v['checked_1'] = (in_array($v['value'], array(1,3))) ? 'checked' : '';
//                     $v['checked_2'] = (in_array($v['value'], array(2,3))) ? 'checked' : '';
// 
//                     $v['caption_1'] = $this->msg['email_msg'];
//                     $v['caption_2'] = $this->msg['notification_msg'];
//                 }
// 
// 
//                 $msg_key = $parser->parseMsgKey($v['setting_key']);
//                 $title = $parser->parseTitle($msg_key, $setting_msg[$msg_key]['title']);
// 
//                 if($v['input'] == 'button') {
//                     $v['btn_title'] = $title;
//                     $v['tr_extra_style'] = 'background: white; color: white;';
//                     $title = '';
//                 }
// 
//                 $tpl->tplAssign('title_msg', $title);
// 
// 
//                 if($parser->parseForm($setting_key, 'check')) {
//                     $field = $parser->parseForm($setting_key, $v,
//                                                $r->parse($form_data[$v['input']], $v),
//                                                $setting_msg);
//                 } else {
//                     $field = $r->parse($form_data[$v['input']], $v);
//                 }
// 
//                 $desc = $parser->parseDescription($msg_key, $setting_msg[$msg_key]['descr']);
//                 $tpl->tplAssign('description_msg', $desc);
// 
//                 $tpl->tplAssign('form_input', $field);
//                 $tpl->tplAssign('id', $setting_key);
//                 $t_id ++;
// 
//                 $tpl->tplParse(array_merge($v, $this->msg), 'row');
//             }
//         }

        if (isset($_REQUEST['q'])) {
            //$tpl->tplAssign('filter', $_REQUEST['q']);
        }

        if (!empty($obj->errors)) {
            foreach ($obj->errors as $error) {
                $err_key = (is_array($error[0]['field'])) ? implode("','", $error[0]['field']) : $error[0]['field'];
                $tpl->tplAssign('show_errors', "showErrorBlock('$err_key')");
            }
        }

        // debug page
        $debug_link = $this->controller->getCurrentLink();
        $debug_link = $this->controller->_replaceArgSeparator($debug_link);
        $tpl->tplAssign('debug_link', $debug_link);

        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign('custom_text', $parser->getCustomFormHeader($obj));

        // fselect
        $tpl->tplAssign('overflow_text', $this->msg['selected_msg'] . ': {{n}}');

        if ($this->priv->isPriv('update') || $this->controller->module == 'account') {
            $tpl->tplAssign('submit_buittons', $parser->parseSubmit($this->template_dir, $this->msg, $extra_options));
        }

        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }



    function parseRows($obj, $manager, $parser, $form_data) {
        
        $setting_msg = $parser->getSettingMsg($manager->module_name);
        
        $r = new Replacer();
        $select = new FormSelect();
        $select->select_tag = false;
        
        $tpl = new tplTemplatez($this->template_dir . 'form_row_standart.html');
        
        
        $rows = &$manager->getRecords();
        
        $t_id = 0;
        $i = 1;
        foreach($rows as $group_id => $group) {
            if(!empty($setting_msg['group_title'])) {
                $tpl->tplSetNeeded('row/group');

                $original_group_id = current($group)['group_id'];
                $tpl->tplAssign('group_id', $original_group_id);
                $tpl->tplAssign('group_num', $i);
                $tpl->tplAssign('delim_id', $i);

                if($i != 1) { $tpl->tplSetNeeded('block/group_delim'); }
                $i++;
            }

            $key_last = end($group);
            $key_last = $key_last['setting_key'];

            foreach($group as $setting_key => $v) {

                if ($setting_key == $key_last) {
                    $tpl->tplAssign('end', '</div>');
                    $tpl->tplSetNeeded('row/submit');
                } else {
                    $tpl->tplAssign('end', '');
                }

                $setting_key = trim($setting_key);

                // if (!empty($setting_msg['group_title'][$v['module_id']]) && is_array($setting_msg['group_title'][$v['module_id']])) {
                //     $v['group_title_msg'] = $setting_msg['group_title'][$v['module_id']][$v['group_id']];

                if (isset($v['user_module_id'])) {
                    $v['group_title_msg'] = $setting_msg['group_title'][$v['user_module_id']][$v['group_id']];

                } else {
                    $v['group_title_msg'] = $setting_msg['group_title'][$v['group_id']];
                }

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

                    // we have options in lang file
                    if(isset($lang_range['option_1'])) {

                        // keep only options_[num]
//                         $lang_range = array_filter($lang_range,
//                             function($k) { return (strpos($k, 'option_') !== false); },
//                             ARRAY_FILTER_USE_KEY);
                        foreach(array_keys($lang_range) as $k2) {
                            if(strpos($k2, 'option_') === false) {
                                unset($lang_range[$k2]);
                            }
                        }

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

                $v['popup_link'] = $this->getLink('all');


                if($v['input'] == 'hidden_btn') {
                    $v['click_handler'] = sprintf("submitToPopup('%s');", $v['id']);
                    $v += $parser->parseExtraOut($setting_key, $v['value'], $manager, $this, $v['id']);
                }

                if($v['input'] == 'checkbox_btn') {
                    $v['checked'] = ($v['value']) ? 'checked' : '';

                    if (!empty($_GET['saved'])) {
                        unset($_GET['saved']);
                    }
                    $v['url'] = $this->controller->getCurrentLink();
                }

                if($v['input'] == 'info') {
                    $v['url'] = $this->controller->getCurrentLink();
                }

                if($v['input'] == 'double_checkbox') {
                    $v['checked_1'] = (in_array($v['value'], array(1,3))) ? 'checked' : '';
                    $v['checked_2'] = (in_array($v['value'], array(2,3))) ? 'checked' : '';

                    $v['caption_1'] = $this->msg['email_msg'];
                    $v['caption_2'] = $this->msg['notification_msg'];
                }


                $msg_key = $parser->parseMsgKey($v['setting_key']);
                $title = $parser->parseTitle($msg_key, $setting_msg[$msg_key]['title']);

                if($v['input'] == 'button') {
                    $v['btn_title'] = $title;
                    $v['tr_extra_style'] = 'background: white; color: white;';
                    $title = '';
                }

                $tpl->tplAssign('title_msg', $title);


                if($parser->parseForm($setting_key, 'check')) {
                    $field = $parser->parseForm($setting_key, $v,
                                               $r->parse($form_data[$v['input']], $v),
                                               $setting_msg);
                } else {
                    $field = $r->parse($form_data[$v['input']], $v);
                }

                $desc = $parser->parseDescription($msg_key, $setting_msg[$msg_key]['descr']);
                $tpl->tplAssign('description_msg', $desc);

                $tpl->tplAssign('form_input', $field);
                $tpl->tplAssign('id', $setting_key);
                $t_id ++;

                $tpl->tplParse(array_merge($v, $this->msg), 'row');
            }
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    // parse multilines ini file
    // it will skip all before defining first [block]
    static function parseMultiIni($file, $key = false) {
        return WebUtil::parseMultiIni($file, $key);
    }


	function ajaxValidateForm2($values, $options) {
		$values = $this->obj->prepareValues($values['values'], $this->manager);
		return parent::ajaxValidateForm($values, $options);
	}


    function ajaxCheckPort($host, $port, $button_name) {

        $objResponse = new xajaxResponse();

        $old_port = SettingModel::getQuick(141, 'sphinx_port');

        if ($old_port != $port) { // changed
            $ret = true;
			$port  = (int) $port;

            $fp = @fsockopen($host, $port, $errno, $errstr, 0.1);

            if ($fp) {
                fclose($fp);

                $msg = AppMsg::getMsgs('setting_msg.ini', 'sphinx_setting');
                $objResponse->script(sprintf("confirmForm('%s', 'submit');", $msg['sphinx_other']['port_in_use']));

                return $objResponse;
            }
        }

        $script = sprintf('$("input[name=%s]").attr("onClick", "").click();', $button_name);
        $objResponse->script($script);

        return $objResponse;
    }

    
    function ajaxPopulateDateFormatSelect($lang) {

        $objResponse = new xajaxResponse();

        $parser = $this->manager->getParser();
        $values = $parser->getDateFormatRange($lang);
        $values = json_encode($values);
        $objResponse->call('populateDateFormatSelect', $values);

        return $objResponse;
    }

}
?>