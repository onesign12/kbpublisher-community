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


class CommonEntryView
{

    // ROLES MSG IN ENTRY LIST VIEW // --------------------------

    static function parseEntryRolesMsg($roles, $roles_range, $msg, $mkey = 'entry_msg') {
        $ret = array();
        foreach($roles AS $rule => $v) {
            foreach($v as $category_id => $data) {
                foreach($data as $role_id) {
                    $ret[$category_id][$rule][$role_id] = sprintf('%s (%s)', $roles_range[$role_id], $msg[$mkey]);
                }
            }
        }

        return $ret;
    }


    static function parseEntryCategoryRolesMsg($roles, $roles_range, $msg) {
        return CommonEntryView::parseEntryRolesMsg($roles, $roles_range, $msg, 'category_msg');
    }


    static function getEntryPrivateMsg($entry_roles, $category_roles, $msg) {
		
		if(empty($entry_roles)) {
			$entry_roles = array();
		}

		if(empty($category_roles)) {
			$category_roles = array();
		}

        $roles_msg = '';
        $entry_roles = RequestDataUtil::stripVars($entry_roles, array(), true);
        $category_roles = RequestDataUtil::stripVars($category_roles, array(), true);

        $str = '<div>%s</div><div>%s</div>';

        $map = array('read', 'write');
        foreach($map as $v) {

            if(!isset($entry_roles[$v])) {
                $entry_roles[$v] = array();
            }

            if(!isset($category_roles[$v])) {
                $category_roles[$v] = array();
            }
            
            $roles = array_merge($entry_roles[$v], $category_roles[$v]);

            if($roles) {
                $mkey = "private2_{$v}_msg";
                $r = ' - ' . implode('<br> - ', $roles);
                $roles_msg .= sprintf($str, $msg[$mkey], $r);
            }
        }

        // return RequestDataUtil::jsEscapeString($roles_msg);
        return $roles_msg;
    }
    

    static function getEntryColorsAndRolesMsg($row, $msg, $entry_type = 1) {
        $data = array();
        
        $private = $row['private'] | $row['category_private'];
        $private_entry = BaseView::getPrivateTypeMsg($row['private'], $msg);
        $private_cat = BaseView::getPrivateTypeMsg($row['category_private'], $msg);
        // $private_entry_msg = ($entry_type == 1) ? $msg['private_article_msg'] : $msg['private_file_msg'];

        if($row['private'] && $row['category_private']) {
            $str = '%s - %s (%s) <br/> %s - %s (%s)';
            $data['private1_msg'] = sprintf($str, 
                $msg['private_msg'], $msg['entry_msg'], $private_entry, 
                $msg['private_msg'], $msg['category_msg'], $private_cat);

        } elseif($row['private']) { // private entry
                $data['private1_msg'] =  sprintf('%s - %s (%s)', $msg['private_msg'], $msg['entry_msg'], $private_entry);
        
        } else { // private category
                $data['private1_msg'] =  sprintf('%s - %s (%s)', $msg['private_msg'], $msg['category_msg'], $private_cat);
        }
        
        if(PrivatePlugin::isPrivateRead($private)) {
            $data['image_color'] = 'red';
            
        } elseif(PrivatePlugin::isPrivateWrite($private)) {
            $data['image_color'] = 'blue';
        
        } else { // unlisted
            $data['image_color'] = 'grey';
        }

        return $data;
    }


    // STATUS // -----------------------------

    static function isCategoryPublished($categories) {
        $cat_published = 0;
        foreach($categories as $v) {
            if($v['active']) { $cat_published = 1; break; }
        }

        return $cat_published;
    }


    static function isEntryPublished($entry, $categories, $publish_status_ids) {
        $cat_published = CommonEntryView::isCategoryPublished($categories);
        $entry_published = (in_array($entry['active'], $publish_status_ids));

        $ret = ($entry_published && $cat_published) ? true : false;
        return $ret;
    }


    static function getViewListEntryStatusVars($entry, $categories, $publish_status_ids, $status) {

        $cat_published = CommonEntryView::isCategoryPublished($categories);
        $row['published'] = CommonEntryView::isEntryPublished($entry, $categories, $publish_status_ids);

        // rewrite status if category not published
        $st_color = $status[$entry['active']]['color'];
        if(!$cat_published) {
            $st_color = $st_color  . '; opacity: 0.4; filter: alpha(opacity=40)';
        }

        $row['status'] = htmlentities($status[$entry['active']]['title']);
        $row['color'] = $st_color;

        return $row;
    }


    // SORT // ---------------------------

    static function &populateSortSelect($manager, $obj, $category_id, $category_title, $xajax = false) {

        $entry_id = $obj->get('id');
        $limit = 10;

        $show_more_top = false;
        $show_more_bottom = false;

        // get all entries
        $entries = $manager->getSortRecords($category_id);
        $sort = $obj->getSortValues($category_id);
        $sort_val = self::getSortOrder($category_id, $entry_id, $sort, $entries, $xajax);
        $sort_val_num = '';

        // id sort_val entry
        $s_ids = explode('_', $sort_val);
        $s_id = $s_ids[0];

        $ids = array_keys($entries);

        // max num entries
        $_limit = ($limit * 2) + 2;

        // filter entries
        if (count($entries) > $limit) {

            $cur_pos = array_search($s_id, $ids);
            $offset = $cur_pos - $limit;

            if ($offset < 0) {
                $offset = 0;
            }

            if ($offset == 0) {
                $el = array_search($entry_id, $ids);
                $_limit = $el + $limit + 1;
            }

            $entries = array_slice($entries, $offset, $_limit, true);

            // show more top option
            if ($offset > 0) {
                $show_more_top = true;
            }

            // show more button option
            $keys = array_keys($entries);
            if (end($ids) != end($keys)) {
                $show_more_bottom = true;
            }
        }

        $html = array();
        $html[] = '<div class="sortOrderDiv">';
        //$html[] = '<script language="javascript">alert("' . $sort_val . '")</script>';
        $html[] = '<div style="padding-bottom: 3px;">'. $category_title .':</div>';
        $html[] = '<div style="padding-bottom: 6px;">';

        $start_num = array_search(key($entries), $ids);
        $range = self::getSortSelectRange($entries, $start_num, $entry_id, $show_more_top);

        // num options
        foreach ($range as $key => $val) {
            $not_num = array('sort_begin', 'sort_end', 'show_top');

            if (in_array($key, $not_num)) {
                $_range[$key] = $val;
            } else {

                $k = explode('_', $key);
                $num = array_search($k[0], $ids);

                $_range[$key . '_' . $num] = $val;

                if ($key == $sort_val) {
                    $sort_val_num = '_' . $num;
                }
            }
        }

        if ($show_more_bottom) {
            $_range['show_bottom'] = 'Show more ...';
        }

        $reg = &Registry::instance();
        $conf = $reg->getEntry('conf');
        $encoding = $conf['lang']['meta_charset'];

        $_range = Utf8::stripBadUtf($_range, $encoding);
        $_range = RequestDataUtil::stripVars($_range, array(), true);

        $select = new FormSelect();
        $select->select_tag = false;
        $select->setRange($_range);

        $str = '<select name="sort_values[%d]" id="sort_values_%d" class="sort_values" style="width: 100%%;">';
        $html[] = sprintf($str, $category_id, $category_id);
        $html[] =  $select->select($sort_val . $sort_val_num);
        $html[] = '</select>';

        $html[] = '</div>';
        $html[] = '</div>';

        $html = implode("\n\t", $html);

        return $html;
    }


    static function ajaxGetNextCategories($mode, $val, $category_id, $manager) {

        $limit = 20;

        $id = substr($val, 0, strpos($val, '_'));
        $pos = substr($val, strrpos($val, '_') + 1);
        if ($pos < 0) $pos = 0;

        $objResponse = new xajaxResponse();


        $del_show_top = false;
        $del_show_bottom = false;

        switch ($mode) {

            case 'bottom':
                $entries = $manager->getSortRecords($category_id, $limit + 1, $pos + 1);

                if(count($entries) < ($limit + 1)) {
                    $del_show_bottom = true;
                } else {
                    array_pop($entries);
                }

                // first selected
                $is_selected = true;

                foreach($entries as $key => $value) {

                    $pos ++;
                    $opt_value = sprintf('%d_%d_%d', $key, $value['s'], $pos);

                    $objResponse->call('SortShowMore.addNextBottomArticle', $category_id, ($pos + 2) . '.  AFTER: ' . $value['t'], $opt_value, $is_selected);
                    $is_selected = false;
                }

                if($del_show_bottom) {
                    $objResponse->call('SortShowMore.deleteShow', $category_id, 'show_bottom');
                }

                break;

            case 'top':
                $pos_lim = $pos - $limit - 1;

                if ($pos_lim <= $limit) {
                    $limit = $pos - 1;
                    $pos_lim = 0;
                }

                $entries = $manager->getSortRecords($category_id, $limit + 1, $pos_lim);

                // is end, delete first if not
                if ($pos_lim == 0) {
                    $del_show_top = true;
                } else {
                    unset($entries[key($entries)]);
                }


                // first selected
                $is_selected = true;

                foreach(array_reverse($entries, true) as $key => $value) {

                    $pos --;
                    $opt_value = sprintf('%d_%d_%d', $key, $value['s'], $pos);

                    $objResponse->call('SortShowMore.addNextTopArticle', $category_id, ($pos + 2) . '.  AFTER: ' . $value['t'], $opt_value, $is_selected);
                    $is_selected = false;
                }

                if($del_show_top) {
                    $objResponse->call('SortShowMore.deleteShow', $category_id, 'show_top');
                }

                break;
        }

        return $objResponse;
    }


    static function ajaxPopulateSortSelect($category_id, $category_title, $manager, $obj) {

        $category_title = RequestDataUtil::stripVars($category_title, array(), true);
        $html = &CommonEntryView::populateSortSelect($manager, $obj, $category_id,
                                                      $category_title, true);

        $objResponse = new xajaxResponse();
        // $objResponse->addAlert($category_title);

        $objResponse->addAppend('writeroot_sort', 'innerHTML', $html);
        $objResponse->call('SortShowMore.init');

        return $objResponse;
    }


    static function getSortSelectRange($rows, $start_num, $entry_id = false, $show_more_top = false) {

        $search = array("#(\r\n|\n)#"); // "#\n+#",
        $data = array();

        $data['sort_begin'] = 'AT THE BEGINNING';
        $data['sort_end'] = 'AT THE END (default)';

        if ($show_more_top) {
            $data['show_top'] = 'Show more ...';
        }

        foreach(array_keys($rows) as $val => $id) {
            $v = $rows[$id];
            if($id == $entry_id) {
                $start_num ++;
                continue;
            }

            $title = preg_replace($search, '', $v['t']);
            $title = substr($title, 0, 100);
            $data[$id . '_' . $v['s']] = sprintf("%s. AFTER: %s", $start_num + 2, $title);
            $start_num ++;
        }

        //echo "<pre>"; print_r($rows); echo "</pre>";
        //echo "<pre>"; print_r($data); echo "</pre>";

        return $data;
    }


    static function getSortOrder($category_id, $entry_id, $sort_order, $entries, $ajax = false) {

        // when wrong form submission
        if(!(empty($_POST['sort_values'])) && $ajax == false) {
            @$sort_order = $_POST['sort_values'][$category_id];

        } else {

            $found = false;
            if($sort_order != 'sort_begin' && $sort_order != 'sort_end') {
                foreach(array_keys($entries) as $id) {
                    $v = $entries[$id];
                    if($id == $entry_id) {
                        $found = true;
                        $sort_order = (isset($prev_id)) ? $prev_id : 'sort_begin'; //sort begin if it on first place
                        break;
                    }

                    $prev_id = $id . '_' . $v['s'];
                }
            }
        }

        return $sort_order;
    }


    static function getSortOrderSetting($setting_sort) {

        $sort = array(
            'name'         => array('title' => 1),
            'filename'     => array('fname' => 1),
            'added_desc'   => array('datep' => 2),
            'added_asc'    => array('datep' => 1),
            'updated_desc' => array('dateu' => 2),
            'updated_asc'  => array('dateu' => 1)
        );

        return (isset($sort[$setting_sort])) ? $sort[$setting_sort] : array();
    }


    static function ajaxGetSortableList($title_field, $alphabetical, $manager, $view) {
        
        $category_id = (int) $_GET['filter']['c'];
        $rows = $manager->getRecords($view->bp->limit, $view->bp->offset);
        
        $sort_values = array();
        foreach ($rows as $k => $v) {
            $sort_values[$k]  = $v['real_sort_order'];
        }
        
        array_multisort($sort_values, SORT_ASC, $rows);
        
        if($alphabetical) {
            if($title_field == 'title') {
                uasort($rows, function($a, $b) {
                    return (_strtolower($a['title']) > _strtolower($b['title'])) ? 1 : 0;
                });    
            } else {
                uasort($rows, function($a, $b) {
                    return (_strtolower($a['filename']) > _strtolower($b['filename'])) ? 1 : 0;
                });
            }
        }
        
        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/list_sortable.html');

        $tpl->tplAssign('category_id', $category_id);
        $tpl->tplAssign('sort_values', implode(',', $sort_values));
        $tpl->tplAssign('title_category', $view->full_categories[$category_id]);

        foreach($rows as $row) {
            $row['title'] = $row[$title_field];
            $tpl->tplSetNeeded('row/icon');
            $tpl->tplParse($row, 'row');
        }

        $cancel_link = $view->controller->getCommonLink();
        $tpl->tplAssign('cancel_link', $cancel_link);

        $tpl->tplParse($view->msg);
        
        
        $objResponse = new xajaxResponse();        
        $objResponse->addAssign('trigger_list', 'innerHTML', $tpl->tplPrint(1));
        $objResponse->call('initSort');

        return $objResponse;
    }


    // CATEGORY // ---------------------

    static function getCategoryBlock($obj, $manager, $categories, 
                                            $module = 'knowledgebase', 
                                            $page = 'kb_entry', $options = array()) {

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/block_category_entry.html');

        $range = array();
        if ($obj) {
            foreach($obj->getCategory() as $cat_id) {
                $range[$cat_id] = $categories[$cat_id];
            }
        }

        $select = new FormSelect();
        $select->select_tag = false;
        $select->setRange($range);
        $tpl->tplAssign('category_select', $select->select());


        $no_button = (!empty($options['no_button'])) ? 'true' : 'false';
        $all_option = (!empty($options['all_option'])) ? 'true' : 'false';
        $default_button = (isset($options['default_button'])) ? $options['default_button'] : true;

        $popup_params = '';
        if(isset($options['popup_params'])) {
            if(is_array($options['popup_params'])) {
                $popup_params = http_build_query($options['popup_params']);
            } else {
                $popup_params = $options['popup_params'];
            }
        }

        if (!empty($options['limited'])) {
            $tpl->tplSetNeeded('/limited');
        }

        // default categories
        $default_categories = array();
        $setting_name = false;
        if($module == 'knowledgebase') {
            $setting_name = 'article_default_category';

        } elseif($module == 'file') {
            $setting_name = 'file_default_category';
        }

        if ($setting_name && $default_button) {
            if (isset($options['entry_categories'])) {
                $default_categories = array();
                foreach ($options['entry_categories'] as $v) {
                    $default_categories[$v] = $categories[$v];
                }
                $default_button_title = 'add_own_categories_msg';

            } else {
                $default_cat = SettingModel::getQuickUser(AuthPriv::getUserId(), 1, $setting_name);
                if ($default_cat != 'none') {
                    if (isset($categories[$default_cat])) {
                        $default_categories = array($default_cat => $categories[$default_cat]);

                        // in add article, add file we have role_skip_categories, apply it.
                        if(!empty($manager->role_skip_categories) &&
                           in_array($default_cat, $manager->role_skip_categories)) {
                               unset($default_categories[$default_cat]);
                        }
                    }
                }

                $default_button_title = 'add_default_category_msg';
            }

            $tpl->tplAssign('default_button_title', sprintf('{%s}', $default_button_title));

            $tpl->tplSetNeeded('/default_category_btn');
        }


        // default categories
        $default_categories = RequestDataUtil::stripVars($default_categories); // for compability with other $js_hash

        $js_hash = array();
        $str = '{value: %s, text: "%s"}';
        foreach($default_categories as $k => $v) {
            $js_hash[] = sprintf($str, $k, $v);
        }

        $js_hash = implode(",\n", $js_hash);
        $tpl->tplAssign('default_categories', $js_hash);

        
        if(!AppPlugin::isPlugin('private')) {
            $options['hide_private'] = true;
        }

        // 22.12.2015 move this block from ...View_form.php
        if(empty($options['hide_private'])) {

            $category_private_display = 'none';

            foreach($obj->getCategory() as $category_id) {
                $cat_title = $categories[$category_id];
                $a['category_private_info'] =
                    PrivatePlugin::getCategoryPrivateInfo($category_id, $cat_title, $manager->cat_manager);

                if (strlen($a['category_private_info']) > 0) {
                    $category_private_display = 'block';
                }

                $tpl->tplParse($a, 'category_private_row');
            }

            $tpl->tplAssign('category_private_display', $category_private_display);
            $tpl->tplSetNeeded('/private_info');
        }
        //->

        $tpl->tplAssign('confirm', ($obj && $obj->get('id')) ? 'true' : 'false');
        $tpl->tplAssign('module', $module);
        $tpl->tplAssign('page', $page);
        $tpl->tplAssign('no_button', $no_button);
        $tpl->tplAssign('all_option', $all_option);
        $tpl->tplAssign('popup_params', $popup_params);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    static function getCategoryBlockSearch($manager, $categories, $add_option, 
                                             $referer, $module, $page, $controller = false) {

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/block_category_search.html');

        $categories = $manager->getCategorySelectRangeFolow($categories);  // private removed

        $js_hash = array();
        $str = '{label: "%s", value: "%s"}';
        foreach(array_keys($categories) as $k) {
            $js_hash[] = sprintf($str, addslashes($categories[$k]), $k);
        }

        $js_hash = implode(",\n", $js_hash);
        $tpl->tplAssign('categories', $js_hash);

        $tpl->tplAssign('creation_allowed', ($add_option) ? 'true' : 'false');
        $tpl->tplAssign('referer', $referer);

        $tpl->tplAssign('module', $module);
        $tpl->tplAssign('page', $page);

        if ($controller) {
            $link = $controller->getFullLink($module, $page, false, 'insert');
            $tpl->tplAssign('popup_link', $controller->_replaceArgSeparator($link));
        }

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    

    // SCHEDULE // ----------------------

    static function getScheduleBlock($obj, $status_range, $bulk = false) {

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/block_schedule_entry.html');

        $select = new FormSelect();
        $select->select_tag = false;

        $schedule = $obj->getSchedule();

        $datepicker_str = '<input type="text" id="%s" name="%s" class="schedule_date" />';
        $date_format = TimeUtil::getDateFormat();

        for ($i=1; $i<=2; $i++) {

            $select->setRange($status_range);
            $status = (isset($schedule[$i]['st'])) ? $schedule[$i]['st'] : 0;
            $note = (isset($schedule[$i]['note'])) ? $schedule[$i]['note'] : '';

            if($i == 2) {
                $status = (isset($schedule[$i]['st'])) ? $schedule[$i]['st'] : 1;
            }

            $tpl->tplAssign('status_select_' . $i, $select->select($status));

            $display = 'none';
            $checked = '';
            $timestamp = time();
            if(isset($schedule[$i]['date'])) {
                $timestamp = $schedule[$i]['date'];
                $display = 'block';
                $checked = 'checked';
            }

            $tpl->tplAssign('note_' . $i, $note);

            $tpl->tplAssign('div_schedule_' . $i . '_display', $display);
            $tpl->tplAssign('ch_schedule_on_' . $i, $checked);

            $tpl->tplAssign('date_format', $date_format);
            $tpl->tplAssign('date_format_formatted', str_replace('yy', 'yyyy', $date_format) . '  hh:mm');

            $reg = &Registry::instance();
            $week_start = &$reg->getEntry('week_start');
            $tpl->tplAssign('week_start', $week_start);

            $tpl->tplAssign('datepicker_id_' . $i, 'schedule_' . $i . '_date');

            $date_parts = array('Y', 'm', 'd', 'H');
            $date_obj_params = array();
            foreach ($date_parts as $part) {
                $date_obj_param = date($part, $timestamp);
                if ($part == 'm') {
                    $date_obj_param --;
                }
                $date_obj_params[] = $date_obj_param;
            }
            $tpl->tplAssign('date_formatted_' . $i, implode(',', $date_obj_params));
            //$tpl->tplAssign('time_formatted_' . $i, date('H:i', $timestamp));

            $date = sprintf($datepicker_str, 'schedule_' . $i . '_date', 'schedule['.$i.'][date]');
            $tpl->tplAssign('date_picker_' . $i, $date);
        }

        $minDate = 0; // current
        if (isset($schedule[1]['date'])) {
            if (time() > $schedule[1]['date']) {

                $date_obj_params = array();
                foreach ($date_parts as $part) {
                    $date_obj_param = date($part, $schedule[1]['date']);
                    $date_obj_params[] = $date_obj_param;
                }
                $minDate = 'new Date("' . implode(',', $date_obj_params) . '")';
            }
        }

        $tpl->tplAssign('min_date', $minDate);

        // diff in bulk view
        if($bulk) {
            $tpl->tplAssign('div_schedule_1_display', 'block');
        } else {
            $tpl->tplSetNeededGlobal('tpl_show_schedule1');
        }

        $msg = AppMsg::getMsgs('datetime_msg.ini', false, 'timepicker');
        $tpl->tplAssign($msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    // MUSTREAD // ----------------------
    
    static function getMustreadBlock($obj, $manager, $view, $module = 'knowledgebase', $page = 'kb_entry', $bulk = false) {
        
        $mustread = $obj->getMustread();
        
        $items = MustreadModel::$items;
        $msg_items = AppMsg::getMsg('ranges_msg.ini', false, 'mustread_type');


        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/block_mustread.html');
        $tpl->strip_vars = true;
        
        if(!$bulk) {
            $tpl->tplSetNeededGlobal('tpl_list');
        } else {
            $tpl->tplSetNeededGlobal('tpl_bulk');
            $mustread['on'] = 1;
        }
                        
        $ch_mustread_on = (!empty($mustread['on']) || !empty($mustread['active']));
        $tpl->tplAssign('ch_mustread_on',  $view->getChecked($ch_mustread_on));
        $tpl->tplAssign('div_mustread_display', ($ch_mustread_on) ? 'block' : 'none');
        
        $mustread_disabled = 0;
        if(!empty($mustread['id'])) {
            
            // reset some old items if mustread expiered
            if(!$_POST && empty($mustread['active'])) {
                $mustread['date_valid'] = NULL;
            }
            
            if(!empty($mustread['active'])) {
                $tpl->tplSetNeeded('/rewrite');
            }
            
            $select = new FormSelect();
            $select->setSelectWidth(150);
            $select->select_tag = false;
            $select->setRange(array(0 => '__', 1 => $view->msg['update_msg'],  2 => $view->msg['reset_msg']));
            $tpl->tplAssign('mustread_update_select', $select->select(@$mustread['update']));
            $tpl->tplAssign('date_created_formatted', $view->getFormatedDate($mustread['date_created']));
        }

        $tpl->tplAssign('mustread_disabled', $mustread_disabled);        
        
        // echo '<pre>', print_r($mustread,1), '<pre>'; exit;
        
        // datepicker
        $timestamp = time() + (30 * 24 * 60 * 60);
        if(!empty($mustread['date_valid']) && strtotime($mustread['date_valid'])) {
            $timestamp = DatePicker::toUnixDate($mustread['date_valid']);
        }
        
        $tpl->tplAssign($view->setDatepickerVars($timestamp));
        
        // $date_valid_on = ($_POST) ? (!empty($mustread['date_valid_on'])) : (!empty($mustread['date_valid']));
        
        // 12-04-2023 eleontev changed block above, worked incorrect in drafts
        if(isset($mustread['date_valid_on'])) {
            $date_valid_on = ($mustread['date_valid_on']);
        } else {
            $date_valid_on = (!empty($mustread['date_valid']));
        }
                
        $tpl->tplAssign('ch_date_valid_on',  $view->getChecked($date_valid_on));
        
        $tpl->tplAssign('ch_notify', $view->getChecked(@$mustread['notify']));
        $tpl->tplAssign('ch_force_read', $view->getChecked(@$mustread['force_read']));
        $tpl->tplAssign($mustread);
        
        foreach($items as $type_num => $v) {
            $type = $v['type'];
            
            $a = array();
            $a['type'] = $type;
            $a['type_num'] = $type_num;
            $a['title'] = $msg_items[$type];
            $a['checked'] = BaseView::getChecked(isset($mustread['rules'][$type]));
            
            $a['display'] = 'none';
            $a['is_toogle'] = 0;  
            if(!empty($v['items'])) {
                $a['display'] = ($a['checked']) ? 'block' : 'none';
                $a['is_toogle'] = 1;
                
                $tpl->tplSetNeeded('rule_row/rule_item_' . $type);
                
                $popup_link = '';
                $ids = (!empty($mustread[$type])) ? $mustread[$type] : array();
                
                // role
                if($type == 'role') {
                    
                    $select = new FormSelect();
                    $select->setSelectWidth(250);
                    $select->select_tag = false;
        
                    if($ids) {
                        $range = self::getMustreadRuleValues($type, $ids, $manager);
                        $select->setRange($range);
                        $tpl->tplAssign('role_select', $select->select());
                    }                    
                    
                    $popup_link = $view->controller->getFullLink($module, $page, false, 'role');
                
                // user
                } elseif($type == 'user') {
                    
                    if($ids) {
                        $b = array();
                        $users = self::getMustreadRuleValues($type, $ids, $manager);
                        foreach($users as $k => $v) {
                            $b['title'] = PersonHelper::getFullName($v); 
                            $b['value'] = $k; 
                            $tpl->tplParse($b, 'rule_row/rule_item_user_row');
                        }
                    }
                    
                    $popup_link = $view->controller->getLink('users', 'user');
                    
                // priv
                } elseif($type == 'priv') {
                    
                    if($ids) {
                        $priv = self::getMustreadRuleValues($type, $ids, $manager);
                        foreach($priv as $k => $v) {
                            $b['title'] = $v;
                            $b['value'] = $k;
                            $tpl->tplParse($b, 'rule_row/rule_item_priv_row');
                        }
                    }
                    
                    $popup_link = $view->controller->getLink('users', 'priv');
                }
            
                $a['popup_link'] = $popup_link;
            }
            
            $tpl->tplParse($a, 'rule_row');
        }

        $tpl->tplAssign('mustread_tip_msg', AppView::getHelpTooltip('mustread_tip_msg')['mustread_tip_msg']);

        $tpl->tplParse($view->msg);
        return $tpl->tplPrint(1);
    }


    static function getMustreadRuleValues($type, $values, $manager) {
        
        static $priv_manager;
        $range = array();
        
        // role
        if($type == 'role') {
            $roles = $manager->role_manager->getSelectRangeFolow();
            foreach($values as $role_id) {
                if(isset($roles[$role_id])) {
                    $range[$role_id] = $roles[$role_id];
                }
            }
        
        // user
        } elseif($type == 'user') {
            $users = $manager->getUserByIds(implode(',', $values));
            foreach($users as $v) {
                $range[$v['id']] = PersonHelper::getFullName($v);
            }
            
        // priv
        } elseif($type == 'priv') {         
            if(!$priv_manager) {
                
                $priv_manager = new PrivModel;
            }
            
            $priv = $priv_manager->getByIds(implode(',', $values));
            foreach($priv as $v) {
                $range[$v['id']] = $v['name'];
            }
        }
        
        return $range;
    }


    static function validateMustread($values) {
        $error = array();
        
        if(isset($values['mustread']['on'])) {
            $mr = $values['mustread'];
            if(empty($mr['rules'])) {
                $error = array('mustread_msg', 'mustread_rule_div_all', 'mustread');
            
            } else {
                foreach(MustreadModel::$items as $k2 => $item) {
                    if(!empty($item['items']) ) {
                        $items = (isset($mr[$item['type']])) ? array_filter($mr[$item['type']]) : array();
                    
                        if(isset($mr['rules'][$item['type']]) && empty($items)) {
                            $error = array('mustread_msg', 'mustread_rule_div_' . $item['type'], 'mustread');
                            break;
                        }
                    }
                }
            }            
        }
        
        return $error;
    }


    // AUTOSAVE // -----------------

    static function ajaxAutoSave($data, $id_key, $obj, $view, $manager, $entry_type = false) {

        $objResponse = new xajaxResponse();

        // disabled for body there is no error even with bad utf
        // it could be FCK fix it somehow
        // if $view->encoding = utf8 stripBadUtf will be called
        $key_to_replace = array('title'); // $key_to_replace = array('title', 'body'); 
        $data_to_replace = array_filter($data, function($k) use ($key_to_replace) { return in_array($k, $key_to_replace); }, ARRAY_FILTER_USE_KEY);
        $data_to_replace = Utf8::stripBadUtf($data_to_replace, $view->encoding);
        $data = array_merge($data, $data_to_replace);

        $obj->populate($data, $manager);

        if(!empty($data['history_comment'])) { // article
	        $obj->set('history_comment', $data['history_comment']);
        }

        if($entry_type == 7) { // draft
            $entry_id = (!empty($data['draft']['id'])) ? $data['draft']['id'] : 0;
        } else {
            $entry_id = (!empty($data['id'])) ? $data['id'] : 0;
        }

        // $objResponse->addAlert(print_r($entry_id, 1));

        $entry_obj = addslashes(serialize($obj));
        $manager->autosave($id_key, $entry_id, $entry_obj, $entry_type);

        $date_format = $view->date_format;
        $time_format = $view->conf['lang']['time_format'];
        
        $time_msg = AppMsg::getMsg('datetime_msg.ini', false, 'time_interval');
		$common_msg = AppMsg::getMsg('common_msg.ini', 'knowledgebase');
		
        $info_msg = sprintf(
            '%s: <span title="%s %s">%s</span>',
            $view->msg['autosave_draft_saved_msg'],
            _strftime($date_format),
            _strftime($time_format),
            $time_msg['just_now_msg']
        );
		
        $msg = array(
            'ago' => $time_msg['ago_msg'],
            'minute' => $time_msg['minute_3'],
            'hour' => $time_msg['hour_3'],
            'prefix' => $common_msg['autosave_draft_saved_msg'] 
        );
        $objResponse->call('showAutosaveBlock',$info_msg, $id_key, $msg);

        $growl_cmd = '$.growl({title: "%s", message: "%s"});';
        $growl_cmd = sprintf($growl_cmd, '', $view->msg['autosave_draft_saved_msg']);
        $objResponse->script($growl_cmd);

        return $objResponse;
    }


    static function ajaxDeleteAutoSave($id_key, $manager) {
        $objResponse = new xajaxResponse();
        $manager->deleteAutosaveByKey($id_key);

        return $objResponse;
    }


    static function getAutosaveValues($obj, $manager, $view) {

        if(!empty($_POST['id_key']))  {
            $id_key = htmlentities($_POST['id_key'], ENT_QUOTES);

        } elseif($id_key = $view->controller->getMoreParam('dkey')) {
            $id_key = addslashes($id_key);

        } else {
            $id_key = array($manager->user_id, 1, $obj->get('id'), $view->controller->action);
            if ($view->controller->action != 'update') {
                $id_key[] = time();
            }

            $id_key = md5(serialize($id_key));
        }

        $data = array();
        $data['autosave_key'] = $id_key;

        $autosave_period = 60000 * $view->setting['entry_autosave'];
        $data['autosave_period'] = $autosave_period;

        return $data;
    }


    // TAG // ----------------------
    
    static function getTagBlock($tag, $popup_link = '', $options = array()) {
        return TagPlugin::getTagBlock($tag, $popup_link, $options);
    }


    static function ajaxAddTag($string, $manager) {

        $objResponse = new xajaxResponse();

        if (_strlen($string) == 0) {
            return $objResponse;
        }

        $creation_allowed = SettingModel::getQuick(1, 'allow_create_tags');
        if (!$creation_allowed) {
            return $objResponse;
        }

        $titles = $manager->tag_manager->parseTagString($string);
        $manager->tag_manager->saveTag($titles);

        $tags = $manager->tag_manager->getTagArray($titles);
        $tags = RequestDataUtil::stripVars($tags, array(), true);
        
        $objResponse->call('createList', $tags);
        $objResponse->addAssign('tag_input', 'value', '');

        return $objResponse;
    }


    static function ajaxGetTags($limit = false, $offset = 0, $manager = '') {

        $objResponse = new xajaxResponse();

        if ($limit) {
            $limit ++;
        }

        $tags = $manager->tag_manager->getSuggestList($limit, $offset);
        $tags = RequestDataUtil::stripVars($tags, array(), true);

        $end_reached = !$limit || (count($tags) < $limit);
        if (!$end_reached) {
            array_pop($tags);
        }

        $data = array();
        foreach($tags as $v) {
            $data[] = array($v['id'], $v['title']);
        }

        $objResponse->addScriptCall('TagManager.updateSuggestList', $data);

        if ($end_reached) {
            $objResponse->addScriptCall('TagManager.hideAllButtons');

        } else {
            $objResponse->addScriptCall('TagManager.showAllButtons');
        }

        return $objResponse;
    }
    
    
    static function parseTagBlock(&$tpl, &$xajax, $obj, $view) {
        $xajax->registerFunction(array('addTag', $view, 'ajaxAddTag'));
        $xajax->registerFunction(array('getTags', $view, 'ajaxGetTags'));
    
        $link = $view->getLink('this', 'this', false, 'tags');
        $tpl->tplAssign('block_tag_tmpl', CommonEntryView::getTagBlock($obj->getTag(), $link));
    }


    // FEATURED // ----------------------

    static function ajaxFeatureArticle($id, $category_id, $value, $view) {

        $objResponse = new xajaxResponse();

        if ($value) {
            $view->manager->increaseFeaturedEntrySortOrder($category_id);
            
            $obj = new KBFeaturedEntry;
            $obj->set('entry_type', $view->manager->entry_type);
            $obj->set('entry_id', $id);
            $obj->set('category_id', $category_id);
            
            $f_manager = new KBFeaturedEntryModel;
            $f_manager->save($obj);
            
            $new_value = 0;
            $show_class = 'featured_img_remove';
            $hide_class = 'featured_img_assign';

        } else {
            $new_value = 1;
            $show_class = 'featured_img_assign';
            $hide_class = 'featured_img_remove';

            $view->manager->deleteFeaturedEntry($id, $category_id);
        }

        $ajax = sprintf("xajax_featureArticle(%s, '%s', '%s'); return false;", $id, $category_id, $new_value);

        $objResponse->script(sprintf('$("#featured_link_%s_%s").attr("onclick", "%s");', $category_id, $id, $ajax));
        $objResponse->script(sprintf('$("#featured_link_%s_%s").find("img.%s").show();', $category_id, $id, $show_class));
        $objResponse->script(sprintf('$("#featured_link_%s_%s").find("img.%s").hide();', $category_id, $id, $hide_class));

        return $objResponse;
    }


    // SPECIAL SEARCH // -------------------

    // if some special search used
    static function getSpecialSearch() {
        $search = array(
            'id', 'tag', 'author', 'updater', 
            'scheduled', 'mustread', 'private', 'custom_id'
        );
        
        return $search;
    }


    static function getSpecialSearchSql($manager, $ret, $string, $entry_only = false) {

        $arr = array();

        if($ret['rule'] == 'author') {
            $arr['where'] = sprintf("AND e.author_id IN(%s)", $ret['val']);

        } elseif($ret['rule'] == 'updater') {
            $arr['where'] = sprintf("AND e.updater_id IN(%s)", $ret['val']);

        } elseif($ret['rule'] == 'scheduled') {
            $arr['from'] = ", {$manager->tbl->entry_schedule} sch";
            $arr['where'] = "AND sch.entry_id = e.id
                                  AND sch.entry_type = '{$manager->entry_type}'
                                  AND sch.num = 1";

        } elseif($ret['rule'] == 'mustread') {
            
            $forced_sql = '';
            if(!empty($ret['val'])) {                            
                preg_match("#(\w+)(\s+[\dyn]+)?#", $ret['val'], $match);
                $forced = isset($match[2]) ? trim($match[2]) : false;
                $forced = ($forced == 'n') ? 0 : 1;
                $forced_sql = sprintf('AND mr.force_read = %d', $forced);
            }

            $arr['from'] = ", {$manager->tbl->entry_mustread} mr";
            $arr['where'] = "AND mr.entry_id = e.id
                             AND mr.entry_type = '{$manager->entry_type}'
                             AND mr.active = 1
                             {$forced_sql}";

        } elseif ($ret['rule'] == 'tag') {

            $tags = explode(',', addslashes(stripslashes($ret['val'])));
            foreach($tags as $k => $v) {
                $tags[$k] = trim($v);
            }

            $ids = $manager->tag_manager->getTagIds($tags);
            $ids = ($ids) ? implode(',', $ids) : 0;

            $arr['from'] = ", {$manager->tag_manager->tbl->tag_to_entry} tag_to_e";
            $arr['where'] = "AND tag_to_e.entry_id = e.id
                             AND tag_to_e.entry_type = '{$manager->entry_type}'
                             AND tag_to_e.tag_id IN ({$ids})";

        } elseif($ret['rule'] == 'private') {

            // all
            $pr = array_sum($manager->private_rule);

            if(empty($ret['val']) ||  $ret['val'] == 'yes' || $ret['val'] == 'y' ) {

            } elseif($ret['val'] == 'write') {
                $pr = $manager->private_rule['write'];
            } elseif($ret['val'] == 'read') {
                $pr = $manager->private_rule['read'];
            } elseif($ret['val'] == 'unlisted') {
                $pr = $manager->private_rule['list'];
            } elseif($ret['val'] == 'none' || $ret['val'] == 'no' || $ret['val'] == 'n') {
                $pr = 0;
            }

            if(strpos($string, 'private-entry') !== false || $entry_only) {
                $arr['where'] = "AND e.private & {$pr}";

            } elseif(strpos($string, 'private-cat') !== false) {
                $arr['where'] = "AND cat.private & {$pr}";

            } else {
                $arr['where'] = "AND (e.private & {$pr} OR cat.private & {$pr})";
            }
            
        }  elseif ($ret['rule'] == 'custom_id') {
            $where = array();
            $where[] = sprintf('AND ecd.field_id IN (%s)', $ret['val']);
            $where[] = 'AND ecd.entry_id = e.id';

            $arr['where'] = implode("\n ", $where);
            $arr['from'] = ", {$manager->tbl->custom_data} ecd";
        }

        // echo '<pre>', print_r($arr,1), '</pre>';

        return $arr;
    }


    static function getChildCategoriesFilterSelectRange($categories, $parent_id, $manager) {

        $range = array();
        $range_ = $manager->getCategorySelectRange($categories, $parent_id);

        if(isset($categories[$parent_id])) {
            $range[$parent_id] = $categories[$parent_id]['name'];
        }

        if (!empty($range_)) {
            foreach (array_keys($range_) as $cat_id) {
                $range[$cat_id] = '-- ' . $range_[$cat_id];
            }
        }

        return $range;
    }


    static function getButtons($view, $xajax, $draft_page) {

        $button = array();

        // change link and msg for drafts
        // if only drafts allowed
        if($view->priv->isPrivOptional('insert', 'draft')) {
            $button['insert'] = $view->getLink('this', $draft_page, '', 'insert');

        } elseif($view->priv->isPriv('insert')) {
            $button['insert'] = $view->getActionLink('insert');
        }

        $category_id = (empty($_GET['filter']['c'])) ? 0 : $_GET['filter']['c'];
        $children_on_display = (!empty($_GET['filter']['ch']));
        $disabled = (!$category_id || $children_on_display);

        if($view->priv->isPriv('update') && !$view->priv->isSelfPriv('update')) {
            $button['...'] = array(array(
                'msg' => $view->msg['reorder_msg'],
                'link' => 'javascript:xajax_getSortableList();void(0);',
                'disabled' => $disabled
            ));
        }
        
        
        $xajax->setRequestURI($view->controller->getAjaxLink('full'));
        $xajax->registerFunction(array('getSortableList', $view, 'ajaxGetSortableList'));

        return $button;
    }
    
    
    // LISTS // --------------------
    
    static function getPrivateToList($row, $entry_roles, $category_roles, $msg, $entry_type = 1) {
        
        $v = array();
        $v['private_img'] = false;
        $v['private_title'] = false; 
        
        if(!AppPlugin::isPlugin('private')) {
            return $v;
        }       
    
        if($row['private'] || $row['category_private']) {
            $colors = self::getEntryColorsAndRolesMsg($row, $msg, $entry_type);
            $roles_msg = self::getEntryPrivateMsg($entry_roles, $category_roles, $msg);
        
            $v['private_img'] = sprintf('lock2_%s.svg', $colors['image_color']);
            $v['private_title'] = sprintf('%s<br/>%s', $colors['private1_msg'], $roles_msg);
        }
        
        return $v;
    }
    
    
    static function getScheduleToList($data, $statuses, $view) {

        $v = array();
        $msgs = array();
        $str = '%s - %s<i>%s</i>';

        for ($i=1; $i<=2; $i++) {
            if(isset($data[$i])) {
                $v = $data[$i];

                $d = $view->getFormatedDate($v['date'], 'datetime');
                $s = RequestDataUtil::stripVars($statuses[$v['st']]['title'], array(), true);
                $n = (!empty($v['note'])) ? '<br />' . RequestDataUtil::stripVars($v['note'], array(), true) : '';

                $msgs[] = sprintf($str, $d, $s, $n);
            }
        }

        $v['schedule_title'] = '';
        if($msgs) {
            $stitle = sprintf('<div><b>%s</b></div>', $view->msg['schedule_msg']);
            $v['schedule_title'] = $stitle . implode('<br />', $msgs);
        }

        return $v;
    }
    
    
    static function getMustreadToList($data, $view, $manager, $to_list = true) {
        
        if(!$data) {
            return array();
        }

        static $msg_items;
        if(!$msg_items) {
            $msg_items = AppMsg::getMsg('ranges_msg.ini', false, 'mustread_type');
        }

        $msgs = array();
        
        if($to_list) {
            $msgs[] = '<div>';
            $msgs[] = sprintf('<div><b>%s</b></div>', $view->msg['mustread_msg']);
        } else {
            $msgs[] = '<div style="line-height: 1.4;">';
        }
        
        $dvalid = ($data['date_valid']) ? $view->getFormatedDate($data['date_valid'], 'datetime') : $view->msg['expired_never_msg'];
        $msgs[] = sprintf('<div>%s: %s</div>', $view->msg['expires_msg'], $dvalid);
    
        $forced = (!empty($data['force_read'])) ? $view->msg['yes_msg'] : $view->msg['no_msg'];
        $msgs[] = sprintf('<div>%s: %s</div>', $view->msg['mustread_force_msg'], $forced);
        
        // $nmsg = ($data['notify']) ? $view->msg['yes_msg'] : $view->msg['no_msg'];
        // $msgs[] = sprintf('<div>%s: %s</div>', $view->msg['notify_msg'], $nmsg);
        
        if($data['note']) {
            $note = BaseView::getSubstring($data['note'], 150);
            $note = RequestDataUtil::stripVars($note, array(), true);
            $msgs[] = sprintf('<div>%s: %s</div>', $view->msg['note_msg'], $note);
        }
        
        $msgs[] = sprintf('<div>------------------------------------------</div>');
        
        $rules = array();
        foreach($data['rules'] as $type => $type_num) {
            $rtitle = $msg_items[$type];
            
            $more = false;
            $values = '';
            
            if(!empty($data[$type])) {
                $ids = $data[$type];
                if($to_list && count($ids) > 3) {
                    $ids = array_slice($ids, 0, 3, true);
                    $more = true;
                }
                
                $values = self::getMustreadRuleValues($type, $ids, $manager);
                if(!$values) { // if no values, could be deleted user etc. 
                    // continue;
                }
                
                $values = RequestDataUtil::stripVars($values, array(), true);
                if($more) {
                    $values[] = '...';
                    // $values[] = sprintf('<a href="%s">...</a>', $view->controller->getActionLink('detail', $data['entry_id']));
                }
                $values = ' -- ' . implode('<br /> -- ', $values) . '<br/>';
            }
            
            $msgs[] = sprintf('%s<br/>%s', $rtitle, $values);
        }

        $msgs[] = '</div>';

        return array('mustread_title' => implode('', $msgs));
    }
    
    
    // OTHER // ------------------
    
    // user dates
    static function parseDateFull($user, $date, $view) {
        if($user) {
            $str = '%s %s %s';
            $str = sprintf($str, $view->getFormatedDate($date, 'datetime'),
                                 $view->msg['by_user_msg'],
                                 PersonHelper::getFullName($user));
        } else {
            $str = '%s';
            $str = sprintf($str, $view->getFormatedDate($date, 'datetime'));
        }

        return $str;
    }


    static function parseInfoBlock(&$tpl, $obj, $view) {

        $tpl->tplSetNeededGlobal('entry_id');

        $str_user = '{by_msg} {first_name} {last_name} (<a href="mailto:{email}">{email}</a>)';

        $a = array();
        $a['date_msg'] = $view->msg['date_posted_msg'];
        $a['by_msg'] = $view->msg['by_user_msg'];
        $a['date_formatted'] = $view->getFormatedDate($obj->get('date_posted'), 'datetime');
        if($user = $obj->getAuthor()) {
            $a['formatted_user'] = $tpl->tplParseString($str_user, array_merge($a, $user));
        }

        $tpl->tplParse($a, 'posted');

        if($obj->getUpdater()) {
            $a = array();
            $a['date_msg'] = $view->msg['date_updated_msg'];
            $a['by_msg'] = $view->msg['by_user_msg'];
            $a['date_formatted'] = $view->getFormatedDate($obj->get('date_updated'), 'datetime');
            if($user = $obj->getUpdater()) {
                $a['formatted_user'] = $tpl->tplParseString($str_user, array_merge($a, $user));
            }

            $tpl->tplParse($a, 'posted');
        }
    }
    
    
    static function getHistoryBlock($obj, $view, $vars) {
        
        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/block_history_comment.html');

        $history_display = 'none';
        $history_flag = 2;
        if ($vars['history_needed']) {
            $display_history_comment = (@$obj->get('history_comment') || !empty($_POST['history_flag']));
            $history_comment = (@$obj->get('history_comment')) ? $obj->get('history_comment') : @$_POST['history_comment'];
             
            $history_display = ($display_history_comment) ? 'block' : 'none';
            $history_flag = ($display_history_comment) ? 1 : 0;
            
            $tpl->tplAssign('history_comment', $history_comment);
        }
        
        $tpl->tplAssign('h_display', $history_display);
        $tpl->tplAssign('h_skip_display', ($view->draft_view) ? 'none' : 'block');
        $tpl->tplAssign('history_flag', $history_flag);

        $tpl->tplParse($view->msg);
        return $tpl->tplPrint(1);
    }
    
}
?>