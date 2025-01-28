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


class KBCategoryView_list extends AppView
{
        
    var $template = 'list.html';
    // var $template_popup = 'list_popup.html';
    var $columns = array('id', 'private', 'title', 'type', 'admin', 'entry_num',  'draft_num', 'sort_order', 'published');
    var $columns_popup = array('id', 'private', 'title');
    
    var $padding = 15;
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
    
        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        //private
        $manager->setSqlParams($manager->getPrivateParams());
        
        // bulk
        $manager->bulk_manager = $this->getBulkModel();
        
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $bulk = $this->getBulkView($obj, $manager);
            $tpl->tplAssign('footer', CommonBulkView::parseBulkBlock($manager, $bulk));
        }        
        
        // filter
        $manager->setSqlParams($this->getFilterSql($manager), 'filter');
        
        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // get records
        $rows = $this->stripVars($manager->getRecords());
        $tree_helper = &$manager->getTreeHelperArray($rows); //$top_category_id
        $ids = $manager->getValuesString($rows, 'id');
        
        $this->full_categories = $manager->getSelectRangeFolow($rows);
        
        // header generate
        $button = CommonCategoryView::getButtons($this);
        $tpl->tplAssign('header', $this->commonHeaderList(false, $this->getFilter($manager), $button));
        
        //num entries per category
        $num_entry = ($ids) ? $manager->getEntriesNum($ids) : array();
        $num_draft = ($ids) ? $manager->getDraftsNum($ids) : array();
        
        // role to category
        $roles_range = $manager->getRoleRangeFolow();
        $roles = ($ids) ? $manager->getRoleById($ids, 'id_list') : array();
        
        // types
        $cat_type_msg = $manager->getCategoryTypeSelectRange();
        $cat_psort_msg = $manager->getCategorySortPublicSelectRange();
        
        // admin users        
        $users = array();
        if($ids) {
            $supervisor_ids = $manager->dv_manager->getDataIds($manager->admin_user_id, $ids);            
            if($supervisor_ids) {
                $users = implode(',', call_user_func_array('array_merge', $supervisor_ids));
                $users = $manager->getUser($users, false);
                $users = $this->stripVars($users);
            }    
        }
        
        $highlighted_ids = array();
        $supervisor_id = false;
        if (!empty($_GET['filter']['supervisor_id'])) {
            $supervisor_id = (int) $_GET['filter']['supervisor_id'];
        }
        
        // count childs cats
        $manager->setSqlParams(false, 'filter');
        $categories = $manager->getSelectRecords();
        $ids = array_keys($manager->getSelectRangeByParentId(0));
        $child = $manager->getChildCategories($categories, $ids);
        
        foreach(array_keys($tree_helper) as $cat_id) {
            
            $row = $rows[$cat_id];
            $level = $tree_helper[$cat_id];
            
            // title
            $row += $this->getTitleToList($row['name'], 100);
            
            $row['type'] = (!empty($row['category_type'])) ? $cat_type_msg[$row['category_type']] : '';
            $row['sort'] = (!empty($row['sort_public']) && $row['sort_public'] != 'default') ? $cat_psort_msg[$row['sort_public']] : $this->msg['sort_public_default_msg'];
                        
            $row['title_title'] = sprintf('<b>%s</b>', $row['name']);
            $row['title_title'] .= sprintf('<br/>%s: %s', $this->msg['sort_public_msg'], $row['sort']);
            if($row['type']) {
                $row['title_title'] .= sprintf('<br/>%s: %s', $this->msg['type_msg'], $row['type']);
            }
            
            
            if($level == 0) {
                $num_subcat = (isset($child[$cat_id])) ? sprintf(' [%s]', count($child[$cat_id])) : '';
                $more = array('filter[c]'=>$cat_id);
                $link = $this->getLink('this', 'this', false, false, $more);
                $str = '<b><a href="%s" style="color:inherit;">%s</a></b>%s';
                $row['title_entry'] = sprintf($str, $link, $row['title_entry'], $num_subcat);
            } else {
                $padding = $this->padding*$level-$this->padding;
                $str = '<img src="images/icons/join.gif" width="14" height="9" style="margin-left: %spx;"> %s';
                $row['title_entry'] = sprintf($str, $padding, $row['title_entry']);
            }
            
            $row['description'] = nl2br($row['description']);
            $row['num_entries'] = (isset($num_entry[$cat_id])) ? $num_entry[$cat_id] : '';
            $row['num_drafts'] = (isset($num_draft[$cat_id])) ? $num_draft[$cat_id] : '';
            // $row['type'] = (!empty($row['category_type'])) ? $cat_type_msg[$row['category_type']] : '';
            $row['entry_link'] = $this->getEntryLink($cat_id);
            $row['draft_link'] = $this->getDraftLink($cat_id);
            
            
            // private&roles
            $row += CommonCategoryView::getPrivateToList($row, $roles, $cat_id, $roles_range, $this->msg);
            
            // supervisor
            if(isset($supervisor_ids[$cat_id])) {
                
                $user_title = array();
                $user_text = array();
                foreach($supervisor_ids[$cat_id] as $user_id) {
                    if(isset($users[$user_id])) {
                        $_user_title = $this->getUserToList($users[$user_id], 'user');
                        $user_text[] = $_user_title['user'];
                        $user_title[] = $_user_title['user_title'];
                    }
                }
                
                $row['admin_num'] = count($user_title);
                $str = '<span style="white-space: nowrap;">%s</span>';
                $row['admin_text'] = sprintf($str, implode('<br />', $user_text));
                $row['admin_title'] = implode('<br />', $user_title);
                
                if (in_array($supervisor_id, array_keys($supervisor_ids[$cat_id]))) {
                    $highlighted_ids[] = $cat_id;
                }
            }
            
            
            $all_children = $manager->getChildCategories($categories, $cat_id);
            $direct_children = array();
            foreach($all_children as $child_id) {
                if($categories[$child_id]['parent_id'] == $cat_id) {
                    $direct_children[$child_id] = $child_id;
                }
            }
                        
            $actions = $this->getListActions($cat_id, $rows[$cat_id]['parent_id'], $direct_children);
            $row += $this->getViewListVarsJs($cat_id, $rows[$cat_id]['active'], true, $actions);        
            
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        $xajax->setRequestURI($this->controller->getAjaxLink('full'));
        $xajax->registerFunction(array('getSortableList', $this, 'ajaxGetSortableList'));
        
        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        $func = array();
        if($supervisor_id !== false) {
            $func = array(
                array('tplSetNeeded', array('/supervisor_highlight')),
                array('tplAssign', array('highlighted_ids', implode(',', $highlighted_ids)))
            );    
        }
        
        $tmpl = APP_MODULE_DIR . 'knowledgebase/category/template/list_in.html';
        
        $tpl->tplParse();
        return $tpl->tplPrintIn($tmpl, $func);
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        
        //$order = $this->getSortOrderSetting();
        $sort->setDefaultSortItem('sort_order', 1);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        $sort->setSortItem('title_msg',  'name', 'name', $this->msg['title_msg']);
        $sort->setSortItem('sort_order_msg','sort_order', 'sort_order', array($this->msg['sort_order_msg'], 7));
        
        return $sort;
    }
    
    
    function getBulkModel() {
        return new KBCategoryModelBulk();
    }
    
    
    function getBulkView($obj, $manager) {
        return $this->controller->getView($obj, $manager, 'KBCategoryView_bulk');
    }
    
    
    function getEntryLink($cat_id) {
        $more = array('filter[c]'=>$cat_id);
        return $this->getLink('knowledgebase', 'kb_entry', false, false, $more);
    }

    
    function getDraftLink($cat_id) {
        $more = array('filter[q]' => "cat_id:$cat_id");
        return $this->getLink('knowledgebase', 'kb_draft', false, false, $more);
    }
    
    
    function getFilter($manager) {
        return CommonCategoryView::getFilter($manager, $this);
    }
    
    
    function getFilterSql($manager) {
        return CommonCategoryView::getFilterSql($manager);
    }
    
    
    function getListActions($cat_id, $parent_id, $direct_children) {
        return CommonCategoryView::getCategoryListActions($cat_id, $parent_id, $direct_children, $this);
    }
    
    
    function ajaxGetSortableList($category_id, $alphabetical = false) {
        return CommonCategoryView::ajaxGetSortableList($category_id, $alphabetical, $this->manager, $this);
    }
    

    // LIST // --------

    function getListColumns() {
        
        $options = array(
            
            'id',
            'private',
            
            'title' => array(
                'type' => 'text_tooltip',
                'params' => array(
                    'title' => 'title_title', 
                    'text' => 'title_entry')
            ),
            
            'type',
              
            'admin2' => array(
                'type' => 'text_tooltip',
                'title' => 'category_admin_msg',
                // 'shorten_title' => 3,
                'params' => array(
                    'text' => 'admin_text',
                    'title' => 'admin_title')
            ),
                                
            'admin' => array(
                'type' => 'text_tooltip_width',
                'title' => 'category_admins_msg',
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#fff" d="M17.997 18h-11.995l-.002-.623c0-1.259.1-1.986 1.588-2.33 1.684-.389 3.344-.736 2.545-2.209-2.366-4.363-.674-6.838 1.866-6.838 2.491 0 4.226 2.383 1.866 6.839-.775 1.464.826 1.812 2.545 2.209 1.49.344 1.589 1.072 1.589 2.333l-.002.619zm4.811-2.214c-1.29-.298-2.49-.559-1.909-1.657 1.769-3.342.469-5.129-1.4-5.129-1.265 0-2.248.817-2.248 2.324 0 3.903 2.268 1.77 2.246 6.676h4.501l.002-.463c0-.946-.074-1.493-1.192-1.751zm-22.806 2.214h4.501c-.021-4.906 2.246-2.772 2.246-6.676 0-1.507-.983-2.324-2.248-2.324-1.869 0-3.169 1.787-1.399 5.129.581 1.099-.619 1.359-1.909 1.657-1.119.258-1.193.805-1.193 1.751l.002.463z"/></svg>',
                'width' => 'min',
                'align' => 'center',
                'params' => array(
                    'text' => 'admin_num',
                    'title' => 'admin_title')
            ),
                        
            'commentable' => array(
                'type' => 'bullet',
                'width' => 1,
                'options' => 'text-align: center;'
            ),
                        
            'ratingable' => array(
                'type' => 'bullet',
                'width' => 1,
                'options' => 'text-align: center;'
            ),
                                                    
            'entry_num' => array(
                'type' => 'link',
                'title' => 'entries_msg',
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="#fff" d="M4 22v-20h16v11.543c0 4.107-6 2.457-6 2.457s1.518 6-2.638 6h-7.362zm18-7.614v-14.386h-20v24h10.189c3.163 0 9.811-7.223 9.811-9.614zm-5-1.386h-10v-1h10v1zm0-4h-10v1h10v-1zm0-3h-10v1h10v-1z"/></svg>',
                'width' => 'min',
                'align' => 'center',
                'params' => array(
                    'link' => 'entry_link',
                    'text' => 'num_entries')
            ),

            'draft_num' => array(
                'type' => 'link',
                'title' => 'drafts_msg',
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="#fff" d="M10 13h-4v-1h4v1zm2.318-4.288l3.301 3.299-4.369.989 1.068-4.288zm11.682-5.062l-7.268 7.353-3.401-3.402 7.267-7.352 3.402 3.401zm-6 8.916v.977c0 4.107-6 2.457-6 2.457s1.518 6-2.638 6h-7.362v-20h14.056l1.977-2h-18.033v24h10.189c3.163 0 9.811-7.223 9.811-9.614v-3.843l-2 2.023z"/></svg>',
                'width' => 'min',
                'align' => 'center',
                'params' => array(
                    'link' => 'draft_link',
                    'text' => 'num_drafts')
            ),
            
            'sort_order',
            
            'published'
        );
            
        return $options;
    }

}
?>