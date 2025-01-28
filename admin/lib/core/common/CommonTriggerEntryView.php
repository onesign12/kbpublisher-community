<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2008 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class CommonTriggerEntryView extends AppView
{
    
    var $tmpl = 'list.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('trigger_msg.ini');
        $this->addMsg('random_msg.ini');
        
        // $this->template_dir = APP_PLUGIN_DIR . 'automation/template/';
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $this->_parseExtraBlocks($tpl, $manager);
        
        // filter sql
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        //$manager->setSqlParamsSelect($params['select']);
        
        // sort generate
        $manager->setSqlParamsOrder('ORDER BY sort_order asc');
        
        // get records        
        $manager->setSqlParams('AND trigger_type = ' . $obj->get('trigger_type'));
        $manager->setSqlParams('AND entry_type = ' . $obj->get('entry_type'));
        
        $rows_by_group = $this->stripVars($manager->getRecordsByStatus(), array('cond_rule'));
        
        // header generate
        $button = $this->_getButtons($rows_by_group);
        $tpl->tplAssign('header', $this->commonHeaderList('', $this->getFilter($manager), $button, false));
        
        $group_titles = array(
            1 => array(
                1 => $this->msg['active_triggers_msg'],
                0 => $this->msg['inactive_triggers_msg']
            ),
            2 => array(
                1 => $this->msg['active_automations_msg'],
                0 => $this->msg['inactive_automations_msg']
            ),
            4 => array(
                1 => $this->msg['active_workflows_msg'],
                0 => $this->msg['inactive_workflows_msg']            )
        );
        
        // list records
        foreach ($rows_by_group as $active => $rows) {
			
			if(!$rows) {
				continue;
			}
			
            foreach($rows as $row) {
            
                $obj->set($row);
                
                // actions/links
                $links = array();
                $links['clone_link'] = $this->getActionLink('clone', $row['id']);
                $actions = $this->getListActions($links);
                
                $tpl->tplAssign($this->getViewListVarsJs($obj->get('id'), $obj->get('active'), true, $actions));
                $tpl->tplParse(array_merge($obj->get(), $this->msg), 'group/row');
            }
            
            $tpl->tplSetNested('group/row');
            
            $v['group_title'] = $group_titles[$obj->get('trigger_type')][$active];
            $tpl->tplParse(array_merge($v, $this->msg), 'group');
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('getSortableList', $this, 'ajaxSetSortableList'));
        
        if ($mailbox_id = (int) $this->controller->getMoreParam(array('filter', 'mid'))) {
            $more = array('filter[mid]' => $mailbox_id);
            $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
        }
        
        $tpl->tplAssign('default_link', $this->controller->_replaceArgSeparator($this->getActionLink('default')));
        $tpl->tplAssign($this->msg);
    
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function _getButtons($rows_by_group) {
        
        $button = array();
        $button[] = 'insert';

        // more options
        if($this->priv->isPriv('update')) {
            
            $pmenu = array();
            
            $disabled = (count($rows_by_group[1]) < 2);
            $pmenu[] = array(
                'msg' => $this->msg['reorder_msg'], 
                'link' => 'javascript:xajax_getSortableList();void(0);',
                'disabled' => $disabled
                );

            $pmenu[] = array(
                'msg' => $this->msg['defaults_msg'], 
                'link' => 'javascript:resetToDefault();'
                );

            $button['...'] = $pmenu;
        }
        
        return $button;
    }
        
    
    function getListActions($links) {
        $actions = array(
            'status',
            'update',
            'delete',
            'clone' => array(
                'msg'  => $this->msg['duplicate_msg'],
                'link' => $links['clone_link'])
             );
        return $actions;
    }
    
    
    function getFilter($manager) {
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql(&$manager) {
        
        // filter
        $arr = array();
        $arr_select = array();
        @$values = $_GET['filter'];

        
        @$v = $values['approver_id'];
        if ($v) {
            $user_id = (int) $v;
            
            $serialized_str = TriggerEntryModel::$user_search_str['cond'];
            $str = sprintf($serialized_str, strlen($user_id), $user_id);
            $sql = sprintf("AND (cond LIKE '%%%s%%'", $str);

            $serialized_str = TriggerEntryModel::$user_search_str['action'];
            $str = sprintf($serialized_str, strlen($user_id), $user_id);
            $sql .= sprintf(" OR action LIKE '%%%s%%')", $str);
            
            $arr[] = $sql;
        }
        
        
        $arr['where'] = implode(" \n", $arr);
        $arr['select'] = implode(" \n", $arr_select);
        
        return $arr;
    }
    
    
    function ajaxSetSortableList() {
        
        $objResponse = new xajaxResponse();
        
        $tpl = new tplTemplatez($this->template_dir . 'list_sortable.html');
        
        if ($mailbox_id = (int) $this->controller->getMoreParam(array('filter', 'mid'))) {
            $tpl->tplSetNeededGlobal('email_automation');
        
            $mailbox = $this->manager->getMailbox($mailbox_id);    
            $mailbox_options = unserialize($mailbox['data_string']);
            $mailbox_title = (!empty($mailbox_options['title'])) ? $mailbox_options['title'] : $mailbox_options['host'];
            
            $tpl->tplAssign('mailbox_title', $mailbox_title);
        }
        
        $this->manager->setSqlParams('AND active = 1');
        $rows = $this->manager->getRecords();
        $rows = $this->manager->setPredefinedTitles($rows);
        
        foreach($rows as $row) {
            $tpl->tplParse($row, 'row');
        }
        
        $cancel_link = $this->controller->getCommonLink();
        $tpl->tplAssign('cancel_link', $cancel_link);
        
        $tpl->tplParse($this->msg);        
        $objResponse->addAssign('trigger_list', 'innerHTML', $tpl->tplPrint(1));
        
        $objResponse->call('initSort');
    
        return $objResponse;    
    }
    
    
    function _parseExtraBlocks($tpl, $manager) {
        
    }
}
?>