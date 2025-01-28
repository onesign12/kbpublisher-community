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

class RoleView_list extends AppView
{
        
    var $tmpl = 'list.html';
    var $columns = array('id', 'title', 'description', 'user_num');
    
    var $padding = 15;
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
    
        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        // header generate
        $tpl->tplAssign('header', $this->commonHeaderList(false, $this->getFilter($manager)));
        
        // filter
        $manager->setSqlParams($this->getFilterSql($manager));        
        
        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // get records
        $rows = $this->stripVars($manager->getRecords());
        $tree_helper = &$manager->getTreeHelperArray($rows);
        
        foreach($tree_helper as $cat_id => $level) {
            
            $row = $rows[$cat_id];
            $level = $tree_helper[$cat_id];
            
            // title
            $row += $this->getTitleToList($row['title'], 100);
            
            if($level == 0) {
                $num_subcat = (isset($child[$cat_id])) ? sprintf(' [%s]', count($child[$cat_id])) : '';
                $more = array('filter[c]'=>$cat_id);
                $link = $this->getLink('this', 'this', false, false, $more);
                $str = '<b><a href="%s">%s</a></b>%s';
                $row['title_entry'] = sprintf($str, $link, $row['title_entry'], $num_subcat);
                
            } else {
                $padding = $this->padding*$level-$this->padding;
                $str = '<img src="images/icons/join.gif" width="14" height="9" style="margin-left: %spx;"> %s';
                $row['title_entry'] = sprintf($str, $padding, $row['title_entry']);
            }
            
            $more = array('filter[role]' => $row['id']);
            $row['user_link'] = $this->getLink('users', 'user', false, false, $more);
            $row['user_num'] = ($row['user_num']) ? $row['user_num'] : '';
            
            $actions = $this->getListActions($cat_id, $rows[$cat_id]['parent_id']);
            $row += $this->getViewListVarsJs($cat_id, $rows[$cat_id]['active'], $rows[$cat_id]['parent_id'], $actions);
            
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getListActions($id, $parent_id) {
        
        $actions = array();
      
        if($this->priv->isPriv('insert')) {
            $link = $this->getActionLink('insert', false, array('parent_id' => $id));
            $actions['insert'] = array(
                'msg'  => $this->msg['new_child_role_msg'],
                'link' => $link
                );
        }
        
        
        $more = array('parent_id' => $parent_id);
        
        $link = $this->getActionLink('update', $id, $more);
        $actions['update'] = array('link' => $link);
        
        $link = $this->getActionLink('delete', $id, $more);        
        $actions['delete'] = array(
            'link' => $link,
            'confirm_msg' => $this->msg['sure_delete_category_msg']
        );
        
        return $actions;
    }    
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setCustomDefaultOrder('user_num', 2);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('title_msg', 'title', 'title', $this->msg['title_msg'], 1);
        $sort->setSortItem('users_msg', 'user_num', 'user_num', $this->msg['users_msg']);
        $sort->setSortItem('sort_order_msg', 'sort_order', 'sort_order', $this->msg['sort_order_msg']);    
        
        return $sort;
    }
    
    
    function getFilter($manager) {

        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;
        
        // category
        $range = $manager->getSelectRangeByParentId(0);
        $select->setRange($range, array('all'=>$this->msg['all_roles_msg'], 
                                        'top'=>$this->msg['top_roles_msg']));

        @$category_id = $_GET['filter']['c'];
        $tpl->tplAssign('category_select', $select->select($category_id));
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql($manager) {
    
        // filter
        @$v = $_GET['filter']['c'];
        $arr = array();
        $top_category_id = 0;
        $categories = $manager->getSelectRecords();
        
        if($v == 'all') {
            $arr = array();
            
        } elseif($v == 'top') {
            $arr[] = "AND r.parent_id = '$top_category_id'";
        
        } elseif(!empty($v)) {
            $category_id = (int) $v;
            $child = array_merge($manager->getChilds($categories, $category_id), array($category_id));
            $child = implode(',', $child);
            
            $arr[] = "AND r.id IN($child)";
        }
        
        return implode(" \n", $arr);
    }
    
    
    // LIST // --------

    function getListColumns() {
        
        $options = array(
            
            'id',
            
            'title' => array(
                'type' => 'text',
                'params' => array(
                    'title' => 'title_title',
                    'text' => 'title_entry')
            ),
            
            'description',                                         
            'user_num'
        );
            
        return $options;
    }
}
?>