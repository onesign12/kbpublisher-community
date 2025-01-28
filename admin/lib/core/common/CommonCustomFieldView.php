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


class CommonCustomFieldView
{

    // get field block in admin area forms
    static function getFieldBlock($rows, $values, $manager, $use_default, 
                                    $show_required_sign = true) {

        $options = array(
            'use_default' => $use_default,
            'style_select' => 'width: 300px;',
            'style_text' => 'width: 295px;',
            'style_textarea' => 'width: 100%;'
        );

        // fields which already in form, does not depends on category
        // using this we have cistom in forms which may lost if category switched 
        // and they were assigned to category
        // 26.07.2017 notr sure if we need this
        // $rows += $manager->getCustomFieldByIds(implode(',', array_keys($values)));

        // from wrong from submission $values ($obj->custom) will be stripped but it works
        // second stripVars call will not change it
        $values = RequestDataUtil::stripVars($values, array(), true);        
        $inputs = CommonCustomFieldView::getCustomFields($rows, $values, $manager, $options);
        

        $tmpl = APP_PLUGIN_DIR . 'fields/custom_field/template/block_custom_field_form.html';
        $tpl = new tplTemplatez($tmpl);

        foreach($rows as $id => $field) {

            $field['id'] = $id;
            $field['class'] = ($field['has_category']) ? ' custom_category' : '';
            $field['input'] = $inputs[$id];

            $field['required_class'] = '';
            if ($field['is_required'] && $show_required_sign) {
                $field['required_class'] = 'required';
            }

            $field['tooltip_td_width'] = 1;
            if ($field['tooltip']) {
                $field['tooltip_td_width'] = 30;
                $tpl->tplSetNeeded('row/tooltip');
            }

            $field['tooltip'] = htmlentities(nl2br($field['tooltip']));

            $tpl->tplParse($field, 'row');
        }

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

    
    static function getFieldBlockSearch($rows, $values, $manager, $msg) {

        if(empty($rows)) {
            return;
        }

        $options = array(
            'use_default' => 0,
            'force_extra_range' => 1,
            'substitute_to_checkbox' => 1,
            'search_form' => 1,
            'class' => 'colorInput',
            'name' => 'filter[custom]'
        );

        $inputs = CommonCustomFieldView::getCustomFields($rows, $values, $manager, $options);

        $td_num = 3;
        $td_width_num = (count($rows) >= $td_num) ? $td_num : count($rows);
        $td_width = round(100/$td_width_num);

        $rows = array_chunk($rows, $td_num, true);


        $tmpl = APP_PLUGIN_DIR . 'fields/custom_field/template/block_custom_field_search.html';
        $tpl = new tplTemplatez($tmpl);

        foreach($rows as $v) {

            foreach($v as $id => $field) {

                $field['id'] = $id;
                $field['td_width'] = $td_width;
                $field['input'] = $inputs[$id];
                $field['td_colspan'] = (count($v) < $td_num) ? $td_num - count($v) + 1 : 1;

                $tpl->tplParse($field, 'row_tr/row_td');
            }

            $tpl->tplSetNested('row_tr/row_td');
            $tpl->tplParse('', 'row_tr');
        }

        $tpl->tplAssign($msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    static function getFieldBlockBulk($rows, $values, $manager, $msg) {
        
        // if(empty($rows)) {
        //     return;
        // }

        $options = array(
            'use_default' => 1,
            'force_extra_range' => 1,
            'class' => 'colorInput',
            'name' => 'value[custom]',
            'style_select' => 'width: 250px;',
            'style_text' => 'width: 250px;',
            'style_textarea' => 'width: 250px;',
            // 'add_hidden' => 1
        );

        $inputs = CommonCustomFieldView::getCustomFields($rows, $values, $manager->cf_manager, $options);
        $multiple = CommonCustomFieldModel::getFieldTypesWithRangeMultiple();

        $bmsg = AppMsg::getMsg('bulk_msg.ini', false, 'bulk_common');

        $tmpl = APP_PLUGIN_DIR . 'fields/custom_field/template/block_custom_field_bulk.html';
        $tpl = new tplTemplatez($tmpl);

        $field_range = array();
		$mandatory_fields = array();
		
        foreach($rows as $id => $field) {

            $field_range[$id] = $field['title'];

            $field['id'] = $id;
            $field['input'] = $inputs[$id];
            $field['add_existing_msg'] = $bmsg['add_existing_msg'];

            if(in_array($field['input_id'], $multiple)) {
                $tpl->tplParse($field, 'row_append');
            }
			
			$field['required_class'] = '';
			if ($field['is_required']) {
				$mandatory_fields[] = $id;
				$field['required_class'] = 'required';
			}

            $tpl->tplParse($field, 'row');
        }

        // fields select
        $select = new FormSelect();
        $select->select_tag = false;

        $items = ($field_range) ? array('remove', 'set') : array('remove');
        $extra_range = $manager->bulk_manager->getSubActionSelectRange($items, 'bulk_custom');
        $select->setRange($field_range, $extra_range);
        $tpl->tplAssign('field_select', $select->select());

        // $tpl->tplAssign($msg);

        $tpl->tplParse();
		$html = $tpl->tplPrint(1);
		$js = sprintf('mandatory_fields = [%s];',  implode(',', $mandatory_fields));
		
		$data = array($html, $js);
        return $data;
    }


    static function getCustomFields($rows, $values, $manager, $options) {

        $data = array();

        $tmpl = APP_PLUGIN_DIR . 'fields/custom_field/template/fields.ini';
        $input = CommonCustomFieldView::parseMultiIni($tmpl);

        $replacer = new Replacer();
        $replacer->strip_var = false;

        foreach($rows as $id => $field) {

            $field['id'] = $id;

            if($options && is_array($options)) {
                $field += $options;
            }

            if(!empty($options['use_default'])) {
                $field['value'] = (isset($values[$id])) ? $values[$id] : $field['default_value'];
            } else {
                $field['value'] = (isset($values[$id])) ? $values[$id] : '';
            }

            if(is_array($field['value'])) {
                $field['value'] = implode(',', $field['value']);
            }

            // for search, substitute some fields
            $substitute = array(8 => 1);
            
            if (!empty($options['substitute_to_checkbox'])) {
                $substitute[2] = 6;
                $substitute[3] = 6;
                $substitute[7] = 6;
            }
            
            if(!empty($options['search_form'])) {
                if(isset($substitute[$field['input_id']])) {
                    $field['input_id'] = $substitute[$field['input_id']];
                }
            }
            
            $field['checked'] = ($field['value']) ? 'checked' : '';
            $input_type = CommonCustomFieldView::mapFieldType($field['input_id']);
            $f = $input[$input_type];
            
            switch ($field['input_id']) {

                // select
                case 2:
                case 3:
                    $range = $manager->getCustomFieldRange($field['range_id']);
                    // $range = RequestDataUtil::stripVars($range, array(), true); // no need for select

                    // extra range for one select
                    $extra_range = array();
                    if($field['input_id'] == 2) {
                        if(!$field['is_required'] || !empty($options['force_extra_range'])){
                            $extra_range = array(''=>'__');
                        }
                    }

                    $value = (!is_array($field['value'])) ? explode(',', $field['value']) : $field['value'];

                    $select = new FormSelect();
                    $select->select_tag = false;
                    $select->setRange($range, $extra_range);
                    $field['select'] = $select->select($value);

                    $data[$id] = $replacer->parse($f, $field);
                    break;


                // checkbox group, radio
                case 6:
                case 7:
                    $range = $manager->getCustomFieldRange($field['range_id']);
                    $range = RequestDataUtil::stripVars($range, array(), true);

                    $value = (!is_array($field['value'])) ? explode(',', $field['value']) : $field['value'];

                    // empty checkbox if required
                    $empty_hidden = '';
                    if(!empty($options['add_hidden'])) {
                        $str = '<input type="hidden" name="custom[%s]" value="" />';
                        $empty_hidden = sprintf($str, $id);
                    }

                    $row = array();
                    foreach(array('style_checkbox', 'style_radio') AS $optionv){
                        $row[$optionv] = (isset($options[$optionv])) ? $options[$optionv] : '';
                    }

                    $inputs = array();
                    foreach ($range as $range_value => $title) {
                        $row['id'] = $id;
                        $row['value'] = $range_value;
                        $row['title'] = $title;
                        $row['checked'] = (in_array($range_value, $value)) ? 'checked' : '';

                        // wrap it
                        $f2 = $f;
                        
                        // checkbox
                        if($field['input_id'] == 6 && isset($options['ch_group_wrap'])) {
                            $f2 = sprintf($options['ch_group_wrap'], $f);
                        }
                        
                        // radio
                        if($field['input_id'] == 7 && isset($options['radio_wrap'])) {
                            $f2 = sprintf($options['radio_wrap'], $f);
                        }
                        
                        $inputs[] = $replacer->parse($f2, $row);
                    }

                    if($inputs) {
                        $rdelim = (isset($options['radio_delim'])) ? $options['radio_delim'] : '<br />';
                        $inputs_str = implode($rdelim, $inputs);
                        $data[$id] = sprintf('<div id="custom[%s]">%s%s</div>', $id, $empty_hidden, $inputs_str);
                    }

                    break;


                // checkbox
                case 5:

                    // empty checkbox if required
                    $empty_hidden = '';
                    if(!empty($options['add_hidden'])) {
                        $str = '<input type="hidden" name="custom[%s]" value="" />';
                        $empty_hidden = sprintf($str, $id);
                    }

                    if(isset($options['ch_wrap'])) {
                        $f = sprintf($options['ch_wrap'], $f);
                    }

                    $data[$id] = $empty_hidden . $replacer->parse($f, $field);

                    break;

                default:

                    $data[$id] = $replacer->parse($f, $field);
            }

            if(!empty($options['name'])) {
                $data[$id] = str_replace('name="custom', 'name="' . $options['name'], $data[$id]);
            }

        }

        return $data;
    }


    // dispay data in details, .. where we do not need forms
    static function getCustomData($values, $manager, $ch_value = 'checkbox', $replace_empty = '-') {

        if($values) {
            $ids = implode(',', array_keys($values));
            $fields = $manager->getCustomFieldByIds($ids);
        }

        $data = array();
        $data_skip_strip = array();

        foreach($values as $k => $v) {

            if (empty($fields[$k])) {
                continue;
            }

            $field = $fields[$k];

            $data[$k]['title'] = $field['title'];
            $data[$k]['value'] = $v;

            // checkbox
            if ($field['input_id'] == 5) {

                if($ch_value == 'checkbox') {
                    $str = '<input type="checkbox" disabled="disabled" %s >';
                    $ch_value = array();
                    $ch_value['on'] = sprintf($str, 'checked');
                    $ch_value['off'] = sprintf($str, '');

                } elseif($ch_value == 'image'){
                    $ch_value = array();
                    $ch_value['off'] = '<img alt="" src="data:image/gif;base64,R0lGODlhDAAMAJEAAEBAQICAgNTQyAAAACH5BAAAAAAALAAAAAAMAAwAAAIZjI8Hyy0BhJwU0vsinnbL7oGbiJGXWXlTAQA7" />';
                    $ch_value['on'] = '<img alt="" src="data:image/gif;base64,R0lGODlhDAAMAJEAAEBAQICAgNTQyAAAACH5BAAAAAAALAAAAAAMAAwAAAIgjI8Hyy0BhJwU0hmkvUbHrHXPlx0YaZ4hqGrX+FYxVQAAOw==" />';

                } elseif(is_array($ch_value)){

                } else {
                    $ch_value = array();
                    $ch_value['on'] = 1;
                    $ch_value['off'] = 0;
                }

                $data_skip_strip[$k]['value'] = (!empty($v)) ? $ch_value['on'] : $ch_value['off'];

            // empty
            } elseif($v === null || $v == '') {
                $data[$k]['value'] = $replace_empty;

            // textarea
            } elseif ($field['input_id'] == 8) {
                $data[$k]['value'] = RequestDataUtil::stripVars($data[$k]['value'], array(), true);
                $data_skip_strip[$k]['value'] = nl2br($data[$k]['value']);

            // date
            // } elseif ($field['input_id'] == 9) {
                // $data[$k]['value'] = $this->getFormatedDate($v);

            // with range
            } elseif ($manager->isFieldTypeWithRange($field['input_id'])) {
                $range = $manager->getCustomFieldRange($field['range_id']);
                $values = (!is_array($v)) ? explode(',', $v) : $v;
                $value = array();
                foreach($values as $val) {
                    $value[] = $range[$val];
                }

                $data[$k]['value'] = implode(', ', $value);
            }
        }

        // strip to display
        $data = RequestDataUtil::stripVars($data, array(), true);
        foreach($data_skip_strip as $k => $value) {
            $data[$k]['value'] = $data_skip_strip[$k]['value'];
        }

        return $data;
    }


    // in admin list
    static function parseAdvancedSearch(&$tpl, $manager, $values, $msg) {

        if(!AppPlugin::isPlugin('fields')) {
            return;
        }

        $crows = $manager->cf_manager->getCustomFieldByEntryType();
        if($crows) {
            $tpl->tplSetNeeded('/advance_button');
        }
        
        $btn_sign = '+';
        $as_display = 'none';
        @$cv = $values['custom'];
        if($cv) {
            $cv = RequestDataUtil::stripVars($cv, array(), true);
            $tpl->tplAssign('custom_field_tmpl',
                    CommonCustomFieldView::getFieldBlockSearch($crows, $cv, $manager->cf_manager, $msg));
            $btn_sign = '-';
            $as_display = 'display';
        }

        $tpl->tplAssign('as_btn_value', $btn_sign);
        $tpl->tplAssign('as_display', $as_display);
    }


    static function ajaxParseAdvancedSearch($show, $manager, $msg) {

        $objResponse = new xajaxResponse();

        $block = '';
        if($show) {
            $rows = $manager->cf_manager->getCustomFieldByEntryType();
            $block = CommonCustomFieldView::getFieldBlockSearch($rows, array(), $manager->cf_manager, $msg);
        }

        $objResponse->addAssign('advanced_search', 'innerHTML', $block);

        return $objResponse;
    }


    static function ajaxParseCutomBulkAction($cat_id, $manager, $msg) {

        $objResponse = new xajaxResponse();

        $filtered_cats = array();
        $categories = array();
        if($cat_id) {
            $filtered_cats = array($cat_id);
            $categories = &$manager->getCategoryRecords();
        }

        $rows = $manager->cf_manager->getCustomField($categories, $filtered_cats);
        list($block, $js) = CommonCustomFieldView::getFieldBlockBulk($rows, array(), $manager, $msg);
		
        $objResponse->assign('bulk_custom_container', 'innerHTML', $block);
		$objResponse->script($js);

        return $objResponse;
    }


    static function mapFieldType($field_num) {

        $field_type = array(
            'text' => 1, 'select' => 2, 'select_multiply' => 3, //'password' => 4,
            'checkbox' => 5, 'checkbox_group' => 6, 'radio' => 7, 'textarea' => 8, 'date' => 9);

        return array_search($field_num, $field_type);
    }


    // parse multilines ini file
    // it will skip all before defining first [block]
    static function parseMultiIni($file, $key = false) {
        return WebUtil::parseMultiIni($file, $key);
    }


    // AJAX // ---------------------------

    static function ajaxGetCustomByCategory($entry_categories, $entry_id, $use_default, $manager) {

        $objResponse = new xajaxResponse();


        if(!$entry_categories) {
            $objResponse->call('deleteCustomByCategory', array());
            return $objResponse;
        }

        $rows = self::getCustomRowsByCategory($entry_categories, $manager);

        $values = array();
        if($entry_id) {
            $values = $manager->cf_manager->getCustomDataById($entry_id);
        }

        $block = CommonCustomFieldView::getFieldBlock($rows, $values, $manager->cf_manager, $use_default);

        $objResponse->call('insertCustom', '<table>' . $block . '</table>', 1);

        return $objResponse;
    }


    static function getCustomRowsByCategory($entry_categories, $manager) {
        $categories = $manager->getCategoryRecords();

        $cat_ids = array();
        foreach ($entry_categories as $v) {
            $arr = TreeHelperUtil::getParentsById($categories, $v);
            $cat_ids = array_merge($cat_ids, $arr);
        }

        $cat_ids = array_unique($cat_ids);
        $cat_ids = implode(',', $cat_ids);

        $rows = $manager->cf_manager->getCustomFieldByCategory($cat_ids);
        return $rows;
    }


    static function ajaxGetCustomToDelete($category_id, $entry_categories, $manager) {

        $objResponse = new xajaxResponse();

        // no categories in select
        if(!$entry_categories) {
            $objResponse->call('deleteCustomAll');
            return $objResponse;
        }

        $categories = $manager->getCategoryRecords();

        $delete_cat_ids = TreeHelperUtil::getParentsById($categories, $category_id);
        $delete_cat_ids = implode(',', $delete_cat_ids);

        $preserve_cat_ids = array();
        foreach ($entry_categories as $id) {
            $arr = TreeHelperUtil::getParentsById($categories, $id);
            $preserve_cat_ids = array_merge($preserve_cat_ids, $arr);
        }

        $preserve_cat_ids = array_unique($preserve_cat_ids);
        $preserve_cat_ids = implode(',', $preserve_cat_ids);

        $fields = $manager->cf_manager->getCustomFieldIdsByCategory($delete_cat_ids);
        $fields_to_preserve = (empty($categories)) ? array() :
            $manager->cf_manager->getCustomFieldIdsByCategory($preserve_cat_ids);

        $field_to_delete = array_diff($fields, $fields_to_preserve);

        $objResponse->call('deleteCustom', $field_to_delete);

        return $objResponse;
    }

}
?>