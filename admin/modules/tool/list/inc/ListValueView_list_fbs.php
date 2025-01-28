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


class ListValueView_list_fbs extends ListValueView_list
{
    
    var $columns = array(
            'title', 'description', 'feedback_admin', 'sort_order', 'color', 
            'default', 'item_active'
        );

    
    function execute(&$obj, &$manager) {

        $this->addMsg('setting_msg.ini');
        $this->addMsg('user_msg.ini');
    
        $list = new ListBuilder($this);
        $tmpl = $list->getTemplate();
        
        $tpl = new tplTemplatez($tmpl);
        
        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        $supervisor_id = false;
        if (!empty($_GET['filter']['supervisor_id'])) {
            $supervisor_id = (int) $_GET['filter']['supervisor_id'];
        }
        
        // get records
        $rows = ($supervisor_id) ? $manager->getRecordsBySupervisor($supervisor_id) : $manager->getRecords();
        $rows = $this->stripVars($rows);
        $rows_msg = ParseListMsg::getValueMsg($obj->group_key);
        //echo "<pre>"; print_r($rows); echo "</pre>";
        
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
        
        // supervisor
        $ids = $manager->getValuesString($rows, 'list_value'); 
        $supervisor = ($ids) ? $manager->getAdminUserById($ids, 'id_list') : array();
        //echo "<pre>"; print_r($supervisor); echo "</pre>";
        
        // list records
        foreach($rows as $row) {
            
            $obj->set($row);
            
            $row['title'] = $this->getTitle($obj, $rows_msg);
            
            // supervisor
            $admin_user = '';
            if(isset($supervisor[$row['list_value']])) {
                $admin_user = implode('<br />', $supervisor[$row['list_value']]);
            }            
            
            $row['admin_user'] = $admin_user;
            $row += $this->getViewListVarsJsCustom($obj->get('id'), $obj->get('active'), $obj);

            $tpl->tplAssign('list_row', $list->getRow($row));
            $tpl->tplParse($row, 'row');
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
}
?>