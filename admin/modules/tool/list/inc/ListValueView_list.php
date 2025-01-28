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


class ListValueView_list extends AppView
{

    var $template = 'list_no_customize.html';
    var $columns = array(
            'title', 'description', 'sort_order', 'color', 
            'default', 'record_active', 'item_active'
        );


    function execute(&$obj, &$manager) {

        $this->addMsg('setting_msg.ini');

        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);

        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());

        // get records
        $rows = $this->stripVars($manager->getRecords());
        $rows_msg = ParseListMsg::getValueMsg($obj->group_key);
        
        $button = array();
        
        if ($this->priv->isPriv('insert')) {
            $button[] = 'insert';
        }
        
        if ($this->priv->isPriv('update')) {
            $disabled = (count($rows) <= 1);
            $button['...'][] = array(
                'msg' => $this->msg['reorder_msg'],
                'link' => 'javascript:xajax_getSortableList();void(0);',
                'disabled' => $disabled
            );
        }
        
        // header generate
        $tpl->tplAssign('header', $this->titleHeaderList(false, $obj->group_title, $button));

        // list records
        foreach($rows as $row) {

            $obj->set($row);
            
            $row['title'] = $this->getTitle($obj, $rows_msg);

            $row += $this->getViewListVarsJsCustom($obj->get('id'), $obj->get('active'), $obj);
            
            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
        }

        // user
        if($obj->get('list_id') == 4) {
            $this->msg['list_entry_status_msg'] = $this->msg['list_user_status_msg'];
        }
        
        // xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        $xajax->setRequestURI($this->controller->getAjaxLink('full'));
        $xajax->registerFunction(array('getSortableList', $this, 'ajaxGetSortableList'));

        $tpl->tplAssign($list->getListVars($sort->toHtml(), $this->msg));
        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrintIn($list->getListInTemplate());
    }


    function &getSort() {

        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);

        $sort->setSortItem('', 'sort', 'sort_order', '', 1);

        return $sort;
    }


    function getTitle($obj, $msg) {
        return ($obj->get('title')) ? $obj->get('title') : $msg[$obj->get('list_key')];
    }


    function getViewListVarsJsCustom($record_id = false, $active = false, $obj = true) {

        $entry_active = $obj->get('custom_3');
        $predifined = $obj->get('predifined');
        
        $actions = array(
            'update' => true, 
            'delete' => true
            );
        
        // locked
        if($predifined == 2) {
            $actions['update'] = false;
        }

        if($predifined) {
            $actions['delete'] = false;
        }
        
        $row = $this->getViewListVarsJs($record_id, $active, 1, $actions);


        // entry active link
        $act = $entry_active;
        $active_img = ($act == 0) ? 'active_0' : 'active_1';
        $row['active_entry_img'] = $this->getImgLink(false, $active_img, false);

        // entry default
        $default = $obj->get('custom_4');
        $default_img = ($default == 0) ? 'active_0' : 'active_1';
        $row['default_img'] = $this->getImgLink(false, $default_img, false);

        // active item
        $active_img = ($active == 0) ? 'active_0' : 'active_1';
        $row['active_img'] = $this->getImgLink(false, $active_img, $this->msg['set_status_msg']);

        return $row;
    }
    
    
    // LIST // --------     
     
    function getListColumns() {
        
        $options = array(
            
            'title' => array(
                'type' => 'text',
                'width' => '30%'
            ),
                
            'description',
            
            'feedback_admin' => array(
                'title' => 'feedback_admin_msg',
                'params' => array(
                    'text' => 'admin_user')
            ),
            
            'sort_order' => array(
                'width' => 1,
                'options' => 'text-align: center;',
            ),

            'color' => array(
                'type' => 'color_box',
                'title' => 'color_msg',
                'width' => 1,
                'options' => 'text-align: center;',
                'params' => array(
                    'color' => 'custom_1')
            ),
            
            'default' => array(
                'type' => 'text',
                'title' => 'list_default_status_msg',
                'width' => 1,
                'options' => 'text-align: center;',
                'params' => array(
                    'text' => 'default_img')
            ),          
  
            'record_active' => array(
                'type' => 'text',
                'title' => 'list_entry_status_msg',
                'width' => 1,
                'options' => 'text-align: center;',
                'params' => array(
                    'text' => 'active_entry_img')
            ),    
                    
            'item_active' => array(
                'type' => 'text',
                'title' => 'status_msg',
                'width' => 1,
                'options' => 'text-align: center;',
                'params' => array(
                    'text' => 'active_img')
            ),
            
        );
            
        return $options;
    }

    
    function ajaxGetSortableList() {
        
        $tpl = new tplTemplatez(APP_MODULE_DIR . 'stuff/list_builder/template/list_sortable.html');

        $rows = $this->manager->getRecords();
        $rows_msg = ParseListMsg::getValueMsg($this->obj->group_key);
        
        foreach($rows as $row) {

            $this->obj->set($row);
            
            $row['title'] = $this->getTitle($this->obj, $rows_msg);
            $tpl->tplParse($row, 'row');
        }

        $cancel_link = $this->controller->getCommonLink();
        $tpl->tplAssign('cancel_link', $cancel_link);

        $tpl->tplParse($this->msg);

        $objResponse = new xajaxResponse();
        $objResponse->addAssign('trigger_list', 'innerHTML', $tpl->tplPrint(1));
        $objResponse->call('initSort');

        return $objResponse;
    }
    
}
?>