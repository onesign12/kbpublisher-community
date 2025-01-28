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


class KBAutosaveView_list extends AppView
{
        
    var $tmpl = 'list.html';
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
    
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        // header generate
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql());
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, false, false));
        
        // breadcrumb
        $url = $manager->entry_type_to_url[$manager->record_entry_type];
        $data['module'] = $url[0]; 
        $data['page'] = $url[1];

        $menu_msg = AppMsg::getMenuMsgs($data['module']); 
        $nav = array();

        $link = $this->getLink($data['module'], $data['page']); 
        $nav[1] = array('link' => $link, 'item' => $menu_msg[$data['page']]);
        $nav[2]['item'] =  $this->msg['autosaved_draft_msg'];             

        $tpl->tplAssign('nav', $this->getBreadCrumbNavigation($nav));
            
        // sort generate
        $sort = $this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // get records
        $rows = $manager->getRecords($bp->limit, $bp->offset);
        $rows = $this->stripVars($rows, array('entry_obj'));

        foreach($rows as $row) {
            
            $obj->set($row);

            $entry_obj = unserialize($row['entry_obj']);
            $entry_obj->properties = $this->stripVars($entry_obj->properties);
            $tpl->tplAssign('title', $entry_obj->get('title'));

            $date_formatted = $this->getFormatedDate($row['date_saved'], 'datetime');
            $tpl->tplAssign('date_formatted', $date_formatted);

            $date_interval = $this->getTimeInterval($row['date_saved']);
            $tpl->tplAssign('date_interval', $date_interval);
            
            // actions/links
            $more = array('dkey' => $row['id_key']);
            $links = array();
            $links['delete_link'] = $this->getActionLink('delete', false, $more);
            $links['update_link'] = $this->getLink($data['module'], $data['page'], false, 'insert', $more);
            $links['detail_link'] = $this->getActionLink('detail', false, $more);
            
            // preview 
            $link = $this->getActionLink('preview', false, $more);    
            $link = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link);
            $links['preview_link'] = $link;
            
            $actions = $this->getListActions($obj, $links);
            $tpl->tplAssign($this->getViewListVarsJs($row['id_key'], null, true, $actions));
            
            $tpl->tplParse($obj->get(), 'row');
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getListActions($obj, $links) {
        $actions = array(
            'preview' => array(
                'msg'  => $this->msg['preview_msg'],
                'link'  => $links['preview_link']),
            'detail' => array(
                'msg'  => $this->msg['detail_msg'],
                'link'  => $links['detail_link']),
            'update' => array(
                 'msg'  => $this->msg['draft_update_msg'],
                 'link' => $links['update_link']),
             'delete' => array(
                 'link'  => $links['delete_link'])
        );
                 
        return $actions;
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setCustomDefaultOrder('date_saved', 1);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('autosaved_date_msg', 'date_saved', 'date_saved', $this->msg['autosaved_date_msg'], 2);
        
        return $sort;
    }
}
?>