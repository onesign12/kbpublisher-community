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


class ListGroupView_list extends AppView
{
    
    // var $template = 'list_group.html';
    // var $columns = array('title', 'description');
    
    var $tmpl = 'list_group.html';
    
    
    function execute(&$obj, &$manager) {
    
        // $list = new ListBuilder($this);
        // $tmpl = $list->getTemplate();
        
        // $tpl = new tplTemplatez($tmpl);
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        // sort generate
        $manager->setSqlParamsOrder('ORDER BY sort_order');
        
        //plugins 
        $pluginable = AppPlugin::getPluginsFiltered('lists', true);
        
        // get records
        $rows = $this->stripVars($manager->getRecords());
        $rows_msg = ParseListMsg::getGroupMsg();
        
        foreach($rows as $k => $row) {
        
            if(isset($pluginable[$row['list_key']])) {
                if(!AppPlugin::isPlugin($pluginable[$row['list_key']])) {
                    continue;
                }
            }
            
            $row = $rows[$k];
            $obj->set($row);
            
            $title = (!empty($row['title'])) ? $row['title'] : $rows_msg[$row['list_key']];
            // $title = $rows_msg[$row['list_key']];
            $row['title'] = $title;
                    
            $row += $this->getViewListVarsJsCustom($obj->get('id'), $obj->get('active'), $obj->get('list_key'));
            
            // $tpl->tplAssign('list_row', $list->getRow($row));
            // $tpl->tplParse($row, 'row');
            $tpl->tplParse($row, 'row');
        }
        
        // $tpl->tplAssign($list->getListVars(array(), $this->msg));
        $tpl->tplAssign($this->msg);
    
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getViewListVarsJsCustom($record_id = false, $active = false, $key = false) {
        
        $actions = array();
        
        $actions['update'] = array(
            'link' => $this->getActionLink('update_group', $record_id),
            );
        
        $actions['load'] = array(
            'link' => $this->controller->getLink('this', 'this', false, false, array('list'=>$key)),
            'msg'  => $this->msg['view_items_msg']
            );
        
        $row = $this->getViewListVarsJs($record_id, $active, 1, $actions);
        $row['load_link'] = $actions['load']['link'];
        
        return $row;
    }
    
    
    // LIST // --------     
     
    // function getListColumns() {
    //
    //     $options = array(
    //
    //         'title' => array(
    //             'type' => 'link',
    //             'title' => 'title_msg',
    //             'width' => '30%',
    //             'params' => array(
    //                 'text' => 'title',
    //                 'link' => 'load_link')
    //         ),
    //
    //         'description'
    //     );
    //
    //     return $options;
    // }
}
?>