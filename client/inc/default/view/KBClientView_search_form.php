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

//$if_modified_since = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
//$mtime = filemtime($_SERVER['SCRIPT_FILENAME']);
//$gmdate_mod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';

//if ($if_modified_since == $gmdate_mod) {
    //header("HTTP/1.0 304 Not Modified");
    //exit;
//}

/*
Header("Cache-Control: must-revalidate");
$offset = 60 * 60 * 24 * 3;
$ExpStr = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
Header($ExpStr);
*/

header("Cache-control: private");

class KBClientView_search_form extends KBClientView_search
{

    function &execute(&$manager) {

        $title = $this->msg['advanced_search_msg'];

        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $title;
        $this->nav_title = $title;
        $this->category_nav_generate = false; // not to generate categories in navigation line

        $data = &$this->getForm($manager);

        return $data;
    }


    function getSearchParams() {
        return $this->stripVars($_GET, array(), 'qweqweqe'); // 3 param for stripslashes);
    }


    function &getForm($manager, $show_options = true) {
        $params = KBClientSearchHelper::getSearchParams(true);

        $in_vals = KBClientSearchHelper::getInValue($params, $manager);
        $in = $in_vals['in'];
        $by = $in_vals['by'];
        $qs = $in_vals['qs'];
        $special_search = $in_vals['sk'];

        $c_display = 0;
        $et_display = 0;
        $custom_display = 0;

        $view_key = false;
        
        // categories
        $more = array('type' => 'article');
        $article_cat_link = $this->controller->getLink('search_category', false, false, false, $more);
        $article_cat_link = $this->controller->_replaceArgSeparator($article_cat_link);
        
        $more = array('type' => 'file');
        $file_cat_link = $this->controller->getLink('search_category', false, false, false, $more);
        $file_cat_link = $this->controller->_replaceArgSeparator($file_cat_link);

        // var_dump($in);
        $_in = (count($in) == 1) ? $in[0] : 'all';

        if(strpos($_in, 'article') !== false) {
            $c_display = 1;
            $et_display = 1;
            $custom_display = 1;
            $cat_link = $article_cat_link;

        } elseif(strpos($_in, 'file') !== false) {
            $manager = &KBClientLoader::getManager($manager->setting, $this->controller, 'file');
            $c_display = 1;
            $custom_display = 1;
            $cat_link = $file_cat_link;

        } elseif(strpos($_in, 'news') !== false) {
            $manager = &KBClientLoader::getManager($manager->setting, $this->controller, 'news');
            $custom_display = 1;
        }
        

        $manager->cf_manager = new CommonCustomFieldModel();
        // $manager->cf_manager->etype = $entry_type;

        $tpl = new tplTemplatez($this->getTemplate('search_form.html'));
        
        $options = [
            'advanced_search' => true,
            'special_search' => $special_search,
        ];
        $tpl->tplAssign('search_form_input', $this->getSearchFormInput($manager, $options));
        
        if (!$show_options) {
            $tpl->tplSetNeededGlobal('search_options');
        }

        //xajax
        $ajax = &$this->getAjax('search');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('getCustomFields', $ajax, 'ajaxGetCustomFields'));
        $xajax->registerFunction(array('getExtraFields', $ajax, 'ajaxGetExtraFields'));


        // types
        $et_range = ListValueModel::getListSelectRange('article_type');
        if($et_range) {
            $et = (isset($params['et'])) ? $params['et'] : array();
            foreach ($et_range as $k => $v) {
                $v1['name'] = $v;
                $v1['value'] = $k;
                
                $v1['checked'] = '';
                $selected = @in_array($k, $et);
                if ($selected) {
                    $v1['checked'] = 'checked';
                    $tpl->tplParse($v1, 'et_selected_row');
                }
                
                $tpl->tplParse($v1, 'et_row');
            }
                
            $tpl->tplAssign('et_display', ($et_display) ? 'block' : 'none');
            $tpl->tplSetNeededGlobal('article_type');
        }
        
        // categories
        $tpl->tplAssign('article_cat_link', $article_cat_link);
        $tpl->tplAssign('file_cat_link', $file_cat_link);
        
        if($c_display) {
            $tpl->tplAssign('cat_link', $cat_link);
            
            $categories = $manager->getCategorySelectRangeFolow($manager->categories);
            
            $c = (isset($params['c'])) ? $params['c'] : array();
            $c = (!is_array($c)) ? array() : $c;
            foreach ($c as $cat_id) {
                $cat_id = (int) $cat_id;
                if(!isset($categories[$cat_id])) {
                    continue;
                }
                
                $v1['value'] = $cat_id;
                $v1['name'] = $categories[$cat_id];
                
                $tpl->tplParse($v1, 'cat_selected_row');
            }
            
            $s = (isset($params['q'])) ? @$params['cp'] : 1;
            $tpl->tplAssign('cp_checked', BaseView::getChecked($s));
        }
        $tpl->tplAssign('c_display', ($c_display) ? 'block' : 'none');
        $tpl->tplAssign('cat_cbx_display', (isset($params['c'])) ? 'block' : 'none');

        // custom field
        if ($custom_display) {
            $custom_values = @$params['custom'];
            $custom_blocks = $this->getCustomFieldBlocks($manager->entry_type, $custom_values, $manager);
            
            if (!empty($custom_blocks)) {
                $block_num = 1;
                if ($et_display) {
                    $block_num ++;
                }
                
                if ($c_display) {
                    $block_num ++;
                }
                
                foreach ($custom_blocks as $block) {
                    $tpl->tplAssign('custom_field_' . $block_num, $block);
                    
                    if ($block_num == 3) {
                        $block_num = 1;
                        
                    } else {
                        $block_num ++;
                    }
                }
                
            } else {
                $custom_display = 0;
            }
        }

        // period
        $range = AppMsg::getMsgs('ranges_msg.ini', 'public', 'search_period_range');
        $period = (isset($params['period'])) ? $params['period'] : 'all';
        foreach ($range as $k => $v) {
            $v1['name'] = $v;
            $v1['value'] = $k;
            $v1['checked'] = ($period == $k) ? 'checked' : '';
            
            $tpl->tplParse($v1, 'period_row');
        }

        // search in
        $range = $this->getSearchInRange($manager, true);
        $range = $this->stripVars($range);

        $_in = (isset($params['in'])) ? $params['in'] : $in;
        // $_in = (is_array($_in)) ? $_in : array($_in);
        $_in = (count($_in) > 1) ? 'all' : $_in[0];
        $tpl->tplAssign('search_in_select', $this->getSearchInSelect($range, $_in));
        
        foreach ($range as $k => $v) {
            $v1['name'] = $v;
            $v1['value'] = $k;
            $v1['checked'] = ($_in == $k) ? 'checked' : '';
            
            $tpl->tplParse($v1, 'in_row');
        }
        
        // search by
		$_by = (isset($params['by'])) ? $params['by'] : 'all';
		$_by = (isset($params['by_set'])) ? $params['by_set'] : $_by; // was redirected to new by but need to set old by in form
        $this->parseSearchByBlock($tpl, $_by, $_in);
        
        
		// dates
        $date_from = (!empty($params['date_from'])) ? strtotime($params['date_from']) : time();
        $from_checked = ((@$params['period'] != 'custom') ? 'checked' : (!empty($params['date_from']))) ? 'checked' : '';
       
        $date_to = (!empty($params['date_to'])) ? strtotime($params['date_to']) : time();
        $to_checked = ((@$params['period'] != 'custom') ? 'checked' : (!empty($params['date_to']))) ? 'checked' : '';

        $tpl->tplAssign($this->setDatepickerVars(array($date_from, $date_to)));
        $tpl->tplAssign('from_checked', $from_checked);
        $tpl->tplAssign('to_checked', $to_checked);


        // checkboxes
        $f = (@$params['pv'] == 'p') ? 'pvp_checked' : 'pvu_checked';
        $tpl->tplAssign($f, 'checked');

        $tpl->tplAssign('qt_checked', BaseView::getChecked(@$params['qt']));
        
        $extra_options = ($c_display || $et_display || $custom_display);
        
        if ($extra_options) {
            $show_extra_block = (!empty($params['c']) || !empty($params['et']));
            if (!$show_extra_block && !empty($params['custom'])) {
                if (array_filter($params['custom'])) {
                    $show_extra_block = true;
                }
            }
            
            $extra_block_display = ($show_extra_block) ? 'block' : 'none';
            $extra_link_display = ($show_extra_block) ? 'none' : 'block';
            
        } else {
            $extra_block_display = 'none';
            $extra_link_display = 'none';
        }
        
        $tpl->tplAssign('extra_block_display', $extra_block_display);
        $tpl->tplAssign('extra_link_display', $extra_link_display);
        

        if(!$this->controller->mod_rewrite) {
            $view_key = $this->controller->getRequestKey('view');
            $ar = array($view_key => $this->view_id);
            $tpl->tplAssign('hidden', http_build_hidden($ar, true));
        }

        $tpl->tplAssign('action_link', $this->getLink('all'));
        $tpl->tplAssign('user_msg', $this->getErrors());
        $tpl->tplAssign('q', @$params['q']);

        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getCustomFieldBlocks($entry_type, $values, $manager) {

        if(!AppPlugin::isPlugin('fields')) {
            return;
        }

        // all for entry type
        $rows = $manager->cf_manager->getCustomFieldByEntryType($entry_type, true);
        $rows = $this->stripVars($rows);
        
        if(empty($rows)) {
            return;
        }

        $options = array(
            'use_default' => 0,
            'force_extra_range' => 1,
            'search_form' => 1,
            // 'style_select' => 'width: 250px;',
            'style_text' => 'width: 95%;',
            'style_textarea' => 'width: 95%;',
            'substitute_to_checkbox' => true,
            // 'radio_wrap' => '<div class="search_item">%s</div>',
            // 'ch_wrap' => '<div class="search_item">%s</div>',
            'ch_group_wrap' => '<div class="search_item">%s</div>',
            'radio_delim' => ''
        );

        $inputs = CommonCustomFieldView::getCustomFields($rows, $values, $manager->cf_manager, $options);
        
        $custom_fields = array();
        foreach($rows as $id => $field) {
            $tpl = new tplTemplatez($this->template_dir . 'search_custom_field.html');
            
            $tpl->tplAssign('id', $id);
            $tpl->tplAssign('input', $inputs[$id]);

            $tpl->tplParse($field);
            $custom_fields[] = $tpl->tplPrint(1);
        }
        
        $column_num = 3;
        $custom_fields = array_chunk($custom_fields, $column_num, true);
        
        $custom_blocks = array();
        foreach($custom_fields as $blocks) {
            $i = 1;
            foreach($blocks as $block) {
                @$custom_blocks[$i] .= $block;
                $i ++;
            }
        }
        
        return $custom_blocks;
    }
    
}
?>