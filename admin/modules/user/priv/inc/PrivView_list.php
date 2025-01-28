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

class PrivView_list extends AppView
{
        
    var $template = 'list.html';
    var $template_popup = 'list_popup.html';
    
    var $columns = array('title','description','priv_level', 'user_num','active');
    var $columns_popup = array('title','description','priv_level');
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
    
        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        // popup 
        $popup = $this->controller->getMoreParam('popup');
        if($popup) {
            $field_name = $this->controller->getMoreParam('field_name');
            $tpl->tplAssign('field_name', $field_name);
        }
        
        
        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // get records
        $rows = $this->stripVars($manager->getRecords());
        
        // header
        $button = array();
        $button[] = 'insert';

        if($this->priv->isPriv('update')) {
            $pmenu = array();
            $pmenu[] = array(
                'msg' => $this->msg['manage_priv_levels_msg'], 
                'link' => 'javascript:xajax_getSortableList();void(0);'
                );
            $button['...'] = $pmenu;
        }
        
        $options = array('bulk_form' => false);
        $tpl->tplAssign('header', $this->commonHeaderList('', '', $button, $options));
        
        foreach($rows as $k => $row) {
            
            $obj->set($row);
            
            $more = array('filter[priv]' => $obj->get('id'));
            $row['user_link'] = $this->getLink('users', 'user', false, false, $more);
            $row['user_num'] = ($row['user_num']) ? $row['user_num'] : false;
            $row['escaped_name'] = addslashes($row['name']);
  
            $row += $this->getViewListVarsCustomJs($obj->get('id'), $obj->get('active'), $obj->get('editable'));

            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('getSortableList', $this, 'ajaxSetSortableList'));
		
        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrintIn($this->template_dir . 'list_in.html');
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setCustomDefaultOrder('user_num', 2);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);        
        
        $sort->setSortItem('id_msg', 'id', 'id', $this->msg['id_msg']);        
        $sort->setSortItem('priv_level_msg', 'privl', 'sort_order', $this->msg['priv_level_msg'], 1);        
        $sort->setSortItem('title_msg',  'title', 'n.name', $this->msg['title_msg']);
        $sort->setSortItem('users_msg','user_num', 'user_num', $this->msg['users_msg']);
        
        return $sort;
    }
    
    
    function getViewListVarsCustomJs($record_id = false, $active = false, $editable = false) {
        
        $actions = array('clone', 'status', 'update', 'delete');

        if(!$editable) {
            $active = ($active == 0) ? 'not' : 'not_checked';
            unset($actions[0], $actions[3]);
        }
        
        $row = $this->getViewListVarsJs($record_id, $active, 1, $actions);

        return $row;
    }
    
    
    function ajaxSetSortableList() {
        
        $objResponse = new xajaxResponse();
        
        $tpl = new tplTemplatez($this->template_dir . 'list_sortable.html');
        
        $tpl->tplAssign('hint', AppMsg::hintBoxCommon('privilege_level'));
        
        $rows = $this->manager->getRecords();
        
        foreach($rows as $row) {
            if ($row['sort_order'] == 1) {
                $tpl->tplAssign('admin_name', $row['name']);
                
            } else {
                $tpl->tplParse($row, 'row');
            }
        }
        
        $cancel_link = $this->controller->getCommonLink();
        $tpl->tplAssign('cancel_link', $cancel_link);
        
        $tpl->tplParse($this->msg);        
        $objResponse->addAssign('priv_list', 'innerHTML', $tpl->tplPrint(1));
        
        $objResponse->call('initSort');
    
        return $objResponse;    
    }
    
    
    function getListColumns() {
        
        $options = array(
            
            'id',
            
            'title' => array(
                'type' => 'text',
                'params' => array(
                    'text' => 'name')
            ),
            
            'description' => array(
                'type' => 'text',
            ),        
                                        
            'priv_level' => array(
                'type' => 'text',
                'width' => 1,
                'options' => 'text-align: center;',
                'params' => array(
                    'text' => 'sort_order')
            ),  
                                                  
            'user_num',
            'active'
        );
            
        return $options;
    } 
}
?>