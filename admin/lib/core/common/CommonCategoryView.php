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


class CommonCategoryView
{    
    
    static function ajaxGetCategoryAdmin($category_id, $manager) {
        
        $admin = $manager->getAdminUserById($category_id);
        $objResponse = new xajaxResponse();
        
        //$objResponse->addAlert($category_id);
        $objResponse->addScriptCall("ajaxEmptyAdminUser");
        
        if($admin) {
            $i = 0;
            foreach($admin as $id => $name) {
                $objResponse->addScriptCall("ajaxFillAdminUser", $id, $name, $i++);
            }        
        } else {
            $objResponse->addScriptCall("ajaxFillAdminUser", 'empty');
        }
    
        return $objResponse;    
    }
    
    
    // such as published, allow comment, rate etc.
    static function ajaxSetCategoryValues($category_id, $manager) {
        
        $ch_properties = array('commentable', 'ratingable', 'attachable', 'active');
        $se_properties = array('category_type');
        
        $data = $manager->getById($category_id);
        
        $objResponse = new xajaxResponse();
        
        foreach($ch_properties as $v) {
            if(isset($data[$v])) {
                $value = ($data[$v]) ? 'true' : 'false';
                $str = sprintf('$("input[name=%s]").prop("checked", %s);', $v, $value);
                $objResponse->addScript($str);
            }
        }

        foreach($se_properties as $v) {
            if(isset($data[$v])) {
                $value = ($data[$v]);
                $str = sprintf('$("select[name=%s]").val(%d);', $v, $value);
                $objResponse->addScript($str);
            }
        }
    
        return $objResponse;
    } 
    
    
    static function ajaxGetSortableList($category_id, $alphabetical, $manager, $view) {
        
        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/category/template/list_sortable.html');
        
        $manager->setSqlParams(sprintf('AND c.parent_id = "%s"', $category_id));
        
        $field = ($alphabetical) ? 'name' : 'sort_order';
        $manager->setSqlParamsOrder('ORDER BY ' . $field);
        $rows = $manager->getRecords();
        
        $tpl->tplAssign('category_id', $category_id);
        
        if ($category_id) {
            $tpl->tplSetNeeded('/parent_category');
            $tpl->tplAssign('parent_category', $view->full_categories[$category_id]);
        }
        
        foreach($rows as $row) {
            if ($category_id != 0) {
                $tpl->tplSetNeeded('row/icon');
            }
        
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
    
    
    static function getListRolesMsg($roles, $category_id, $roles_range, $msg) {
        
        $ret = '';
        $str = '<div>%s</div><div>%s</div>';
        
        foreach($roles AS $rule => $v) {
            if(isset($v[$category_id])) {
                $_roles = array();
                foreach($v[$category_id] as $id) { 
                    $_roles[] = $roles_range[$id]; 
                }
                
                $mkey = "private2_{$rule}_msg";
                $_roles = ' - ' . implode('<br> - ', RequestDataUtil::stripVars($_roles, array(), true));
                $ret .= sprintf($str, $msg[$mkey], $_roles);
            }
        }
        
        // return RequestDataUtil::jsEscapeString($ret);
        return $ret;
    }
    
    
    static function getListPrivatesMsg($private, $msg) {
        $private_msg = BaseView::getPrivateTypeMsg($private, $msg);
        return sprintf('%s (%s)', $msg['private_msg'], $private_msg);
    }
    
    
    static function getPrivateToList($row, $roles, $category_id, $roles_range, $msg) {
        
        if(!AppPlugin::isPlugin('private')) {
            return [];
        }   
        
        $text = sprintf('%s', self::getListPrivatesMsg($row['private'], $msg));
        $text .= self::getListRolesMsg($roles, $category_id, $roles_range, $msg);
        
        $v = array();
        $v['private_title'] = $text;
        
        if(PrivatePlugin::isPrivateRead($row['private'])) {
            $v['private_img'] = 'lock2_red.svg';
            
        } elseif(PrivatePlugin::isPrivateWrite($row['private'])) {
            $v['private_img'] = 'lock2_blue.svg';
        
        } elseif(PrivatePlugin::isPrivateUnlisted($row['private'])) {
            $v['private_img'] = 'lock2_grey.svg';
        
        } else {
            $v['private_img'] = '';
        }
        
        return $v;
    }
    
    
    static function getSortOrder($entry_id, $sort_order, $entries, $ajax = false) {
        
        // when wrong form submission
      /*  if($_POST && $ajax == false) {
            die(var_dump($_POST));
            @$sort_order = $_POST['sort_values'][$category_id];
        
        } else {*/
            
            $found = false;
            if($sort_order != 'sort_begin' && $sort_order != 'sort_end') {
                foreach(array_keys($entries) as $id) {
                    if($id == $entry_id) {
                        $found = true;
                        $sort_order = (isset($prev_id)) ? $prev_id : 'sort_begin'; //sort begin if it on first place
                        break;
                    }
                    
                    $prev_id = $entries[$id]['sort_order'];
                }
            }
       // }
                
        return $sort_order;    
    }
    

    static function sortCategoriesByOrder($a, $b) {
        return $a['sort_order'] - $b['sort_order'];
    }
    
    
    // generate arrays for js (DynamicOptionList) $arr from getSelectRecords
    static function &getSortJsArray($arr, $skip_id = false) {
        
        $values = array();
        foreach($arr as $v) {
            if ($skip_id == $v['id']) {
                continue;
            }
            
            if (!isset($values[$v['id']])) {
                $values[$v['id']] = array();
            }
            
            $str =  "'%s: %s', '%s'";
            $values[$v['parent_id']][] = sprintf($str, 'AFTER', addslashes($v['name']), $v['sort_order']);
        }

        $str = "'AT THE BEGINNING', 'sort_begin'";
        
        $data = array();
        foreach($values as $k => $v) {
            if (!empty($values[$k])) {
                $a = $str . ", 'AT THE END', 'sort_end', ";
                $data[$k]['str'] = "'" . $k . "'," . $a . implode(',', $v);
                $data[$k]['default'] = 'sort_end';
                
            } else {
                $data[$k]['str'] = "'" . $k . "'," . $str;
                $data[$k]['default'] = 'sort_begin';
            }
        }
        
        return $data;
    }
    
    
    // should be in common category view
    static function getCategoryListActions($cat_id, $parent_id, $direct_children, $view) {
        
        $actions = array('status', 'clone');
        
        if($view->priv->isPriv('insert')) {
            $actions['clone_tree'] = array(
                'msg'  => $view->msg['duplicate_tree_msg'],
                'link' => $view->getActionLink('clone_tree', $cat_id),
                'confirm_msg' => $view->msg['sure_duplicate_tree_msg']
            );
            
            if (empty($direct_children)) {
                $actions['clone_tree']['disabled'] = true;
            }
        }
      
        if($view->priv->isPriv('insert')) {
            $link = $view->getActionLink('insert', false, array('parent_id' => $cat_id));
            $actions['insert'] = array(
                'msg'  => $view->msg['new_child_category_msg'],
                'link' => $link,
                'img'  => '');
        }
        
        
        $more = array('parent_id' => $parent_id);
        
        $link = $view->getActionLink('update', $cat_id, $more);
        $actions['update'] = array(
            'link' => $link
            );
        
        $link = $view->getActionLink('delete', $cat_id, $more);        
        $actions['delete'] = array(
            'link' => $link,
            'confirm_msg' => $view->msg['sure_delete_category_msg']
            );
        
        if($view->priv->isPriv('update')) {
            $actions['sort'] = array(
                'msg'  => $view->msg['reorder_child_categories_msg'],
                'link' => sprintf('javascript:xajax_getSortableList(%s);void(0);', $cat_id),
                'disabled' => (count($direct_children) <= 1)
            );  
        }
        
        return $actions;
    }
    
    
    static function getCategoryPopupBlock($controller, $msg, $module = 'knowledgebase', $page = 'kb_category') {
        
        $tmpl = APP_MODULE_DIR . 'knowledgebase/category/template/block_category.html';
        $tpl = new tplTemplatez($tmpl);

        $link = $controller->getFullLink($module, $page, false, 'category');
        $tpl->tplAssign('popup_link', $link);
        
        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }
    
    
    static function getButtons($view) {
        
        $button = array();
        $button[] = 'insert';
        
        if($view->priv->isPriv('update')) {
            $category_id = (isset($_GET['filter']['c'])) ? $_GET['filter']['c'] : 0;
            $category_id = ($category_id == 'top') ? 0 : $category_id;
            
            $disabled = ($category_id === 'all') ? true : false;
            $menu_msg_key = ($category_id && !$disabled) ? 'reorder_child_categories_msg' : 'reorder_msg';
            $pmenu[] = array(
                'msg' => $view->msg[$menu_msg_key],
                'link' => sprintf('javascript:xajax_getSortableList(%s);void(0);', $category_id),
                'disabled' => $disabled
            );
            
            $button['...'] = $pmenu;
        }
        
        return $button;
    }
    
    
    static function getFilter($manager, $view) {

        $tpl = new tplTemplatez($view->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;
        
        // category
        $manager->setSqlParams(false, 'filter');
        $range = $manager->getSelectRangeByParentId(0);
        $select->setRange($range, array('all'=>$view->msg['all_categories_msg'], 
                                        'top'=>$view->msg['top_categories_msg']));
                                        
        $category_id = (isset($_GET['filter']['c'])) ? $_GET['filter']['c'] : 'top';
        $category_id = (in_array($category_id, array('top', 'all'))) ? $category_id : (int) $category_id; 
        $tpl->tplAssign('category_select', $select->select($category_id));
        
        $tpl->tplAssign($view->setCommonFormVarsFilter());
        $tpl->tplAssign($view->msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    static function getFilterSql($manager) {
    
        // filter
        @$v = $_GET['filter']['c'];
        $arr = array();
        $top_category_id = 0;
        $categories = $manager->getSelectRecords();
        
        if($v == 'all') {
            $arr = array();
            
        } elseif($v == 'top' || empty($v)) {
            $arr[] = "AND c.parent_id = '$top_category_id'";
        
        } elseif(!empty($v)) {
            $category_id = (int) $v;
            $child = array_merge($manager->getChilds($categories, $category_id), array($category_id));
            $child = implode(',', $child);
            
            $arr[] = "AND c.id IN($child)";
        }
        
        return implode(" \n", $arr);
    }
}
?>