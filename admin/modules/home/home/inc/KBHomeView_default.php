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


class KBHomeView_default extends AppView
{
    
    var $num = 10;
    
    
    function execute(&$obj, &$manager) {

        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        
        $tpl = new tplTemplatez($this->template_dir . 'page.html');
        
        $start_wizard = SettingModel::getQuick(0, 'display_start_wizard');
        
        if($this->controller->getMoreParam('done')) {
            $msgs = AppMsg::getMsg('start_wizard_msg.ini'); 
            $msgs = array('body' => $msgs['wizard_completed_msg']);
            $msg_box = BoxMsg::factory('info', $msgs);
            $tpl->tplAssign('user_msg', $msg_box);
            
        } elseif ($start_wizard && AuthPriv::isAdmin()) {
            $this->addMsg('start_wizard_msg.ini');
            $tpl->tplSetNeeded('/start_wizard');
            
            $wizard_link = $this->getLink('setting', 'common_setting');
            $tpl->tplAssign('wizard_link', $wizard_link);
        }
        
        
        $active_portlets_ids = $manager->getActivePortletsIds();
        $portlets_range = $manager->getPortletSelectRange($this->msg);
        $portlets = array();
        $hidden_portlets_ids = array();
        
        $pluginable = AppPlugin::getPluginsFiltered('portlet', true);
        
        foreach ($portlets_range as $id => $title) {
            
            $portlet_key = $manager->getPortletKeyById($id);
            
            // pluginable
            if(isset($pluginable[$portlet_key]) && !AppPlugin::isPlugin($pluginable[$portlet_key])) {
                continue;
            }
            
            $portlets[$id] = $this->getPortlet($portlet_key, $manager);
            
            if (!$portlets[$id]) {
                continue;
            }
            
            $v['block_name'] = $title;
            $v['block_id'] = $id;
            
            $is_portlet_visible = (in_array($id, $active_portlets_ids[0]) || in_array($id, $active_portlets_ids[1]));
            if (!$is_portlet_visible) {
                $hidden_portlets_ids[] = $id;
            }
            
            $v['checked'] = ($is_portlet_visible) ? 'checked' : '';
            
            $tpl->tplParse($v, 'portlet_row');
        }
        
        $available_portlets = array_filter($portlets);
        if (empty($available_portlets)) { // nothing to display
            return '';
        }
        
        $hidden_portlets[1] = array_slice($hidden_portlets_ids, 0, round(count($hidden_portlets_ids) / 2));
        $hidden_portlets[2] = array_slice($hidden_portlets_ids, round(count($hidden_portlets_ids) / 2));
        
        for ($i = 1; $i <= 2; $i ++) {
            
            $column_ids = implode(',', $active_portlets_ids[$i - 1]);
            $tpl->tplAssign(sprintf('column%d_ids', $i), $column_ids);
            
            $tpl->tplAssign('percentage_column_width', ($i == 1) ? '60' : '40');
            
            $v = array();
            foreach ($active_portlets_ids[$i - 1] as $portlet_id) {
                
                if (empty($portlets[$portlet_id])) { // user doesn't have access to this portlet
                    continue;
                }
                
                $v['portlet'] = $portlets[$portlet_id];
                $v['id'] = $portlet_id;
                $v['display'] = 'block';
                
                $tpl->tplParse($v, 'column/portlet');
            }
            
            // add hidden portlets to the end of a column
            foreach ($hidden_portlets[$i] as $portlet_id) {
                    
                if (empty($portlets[$portlet_id])) { // user doesn't have access to this portlet
                    continue;
                }
            
                $v['portlet'] = $portlets[$portlet_id];
                $v['id'] = $portlet_id;
                $v['display'] = 'none';
                
                $tpl->tplParse($v, 'column/portlet');
            }
            
            $row['column_id'] = $i;
            $row['placeholder_display'] = (empty($hidden_portlets_ids)) ? 'none' : 'block';
            
            $tpl->tplSetNested('column/portlet');
            $tpl->tplParse(array_merge($row, $this->msg), 'column');
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        $xajax->registerFunction(array('setUserHome', $this, 'ajaxSetUserHome'));
        $xajax->registerFunction(array('setUserHomeDefault', $this, 'ajaxSetUserHomeDefault'));
        $xajax->registerFunction(array('hideGetStartedBlock', $this, 'ajaxHideGetStartedBlock'));
        
        $tpl->tplParse($this->msg); 
        return $tpl->tplPrint(1);
    }
    
    
    function instance($class) {
        // if(isset($this->$class)) {
        //     return $this->$class;
        // }
        
        // $this->$class = new $class;
        // return $this->$class;
        
        return new $class;
    }
    
    
    function getPortlet($key, $manager) {
        
        // probably we should show stat at all if user do not have priv ?
        $reg =& Registry::instance();
        $priv = $reg->getEntry('priv');
        
        $setting = SettingModel::getQuick(100);
        
        switch ($key) {
            case 'article':
            	if($priv->isPriv('select', 'kb_entry')) {
                    return $this->getArticleStat($manager);
                }
            	break;
                
            case 'file':
            	if($priv->isPriv('select', 'file_entry')) {
                    return $this->getFileStat($manager);
                }
            	break;
                
            case 'draft_article':
            	if($priv->isPriv('select', 'kb_draft')) {
            	    return $this->getDraftArticleStat($manager);
                }
            	break;
                
            case 'draft_file':
            	if($priv->isPriv('select', 'file_draft')) {
            	    return $this->getDraftFileStat($manager);
                }
            	break;
                           
            case 'approval':
            	if($priv->isPriv('select', 'kb_draft') || $priv->isPriv('select', 'file_draft')) {
            	    return $this->getApprovalStat($manager);
                }
            	break;
                
        }
    }    
    
    
    function getStat($rows, $statuses, $options) {
        
        $tpl = new tplTemplatez($this->template_dir . 'article_stat.html');
        
        $total = 0;
        foreach($statuses as $num => $v) {
            
            $status_compare = (isset($options['st_correction'])) ? $num + $options['st_correction'] : $num;
            if(!empty($rows[$status_compare])) {
                $v['num'] = $rows[$status_compare];
            } else {
                continue;
            }
            
            $more = array('filter[s]' => $num, 'filter[q]' => 'author:' . $options['user_id']);
            $v['status_link'] = $this->controller->getLink($options['module'], $options['page'], false, false, $more);
            $v['status_title'] = $v['title'];
            $total += $v['num'];
        
            $tpl->tplParse($v, 'row');
        }
        
        if(!$total) {
            $tpl->tplParse('', 'empty');
        }
            
        $tpl->tplAssign('title_msg', $options['title']);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getArticleStat($manager) {
        
        // $manager2 = new KBEntryModel;
        $manager2 = $this->instance('KBEntryModel');
        
        // roles sql
        $manager2->setSqlParams('AND ' . $manager2->getCategoryRolesSql(false));
        $manager2->setSqlParams('AND author_id = ' . $manager->user_id);
        
        $rows = $manager2->getStatRecords();
        $statuses = $manager2->getEntryStatusData('article_status');
        
        $options = array(
            'user_id' => $manager->user_id,
            'module' => 'knowledgebase',
            'page' => 'kb_entry',
            'title' => $this->msg['my_article_stats_msg']
        );
        
        return $this->getStat($rows, $statuses, $options);
    }
    
    
    function getFileStat($manager) {
        
        // $manager2 = new FileEntryModel;
        $manager2 = $this->instance('FileEntryModel');
        
        // roles sql
        $manager2->setSqlParams('AND ' . $manager2->getCategoryRolesSql(false));
        $manager2->setSqlParams('AND author_id = ' . $manager->user_id);
        
        $rows = $manager2->getStatRecords();
        $statuses = $manager2->getEntryStatusData('file_status');
        
        $options = array(
            'user_id' => $manager->user_id,
            'module' => 'file',
            'page' => 'file_entry',
            'title' => $this->msg['my_file_stats_msg']
        );
        
        return $this->getStat($rows, $statuses, $options);
    }
    
    
    function getDraftArticleStat($manager) {

        $manager2 = $this->instance('KBDraftModel');
        $manager2->setSqlParams('AND author_id = ' . $manager->user_id);
        
        $rows = $manager2->getDraftStatRecords($manager2->from_entry_type);
        $statuses = $manager2->getDraftStatusData();
        
        $options = array(
            'user_id' => $manager->user_id,
            'st_correction' => -1,
            'module' => 'knowledgebase',
            'page' => 'kb_draft',
            'title' => $this->msg['my_draft_article_stats_msg']
        );
        
        return $this->getStat($rows, $statuses, $options);
    }
    
    
    function getDraftFileStat($manager) {

        $manager2 = $this->instance('FileDraftModel');
        $manager2->setSqlParams('AND author_id = ' . $manager->user_id);
        
        $rows = $manager2->getDraftStatRecords($manager2->from_entry_type);
        $statuses = $manager2->getDraftStatusData();
        
        $options = array(
            'user_id' => $manager->user_id,
            'st_correction' => -1,
            'module'  => 'file',
            'page'    => 'file_draft',
            'title'   => $this->msg['my_draft_file_stats_msg']
        );
        
        return $this->getStat($rows, $statuses, $options);
    }
    
    
    function getApprovalStat($manager) {
        
        $reg =& Registry::instance();
        $priv = $reg->getEntry('priv');
        
        $manager2 = $this->instance('KBDraftModel');
        $rows = $manager2->getAwaitingDrafts();
                
        $msg = AppMsg::getMsgs('ranges_msg.ini', false, 'approval_rule_match');
        $range = array(
            'article' => array(1,7), 
            'file' => array(2,8)
            );
            
        
        $tpl = new tplTemplatez($this->template_dir . 'article_stat.html');
        
        $total = 0;
        foreach ($range as $entry_type_str => $v) {
            
            $entry_type = $v[0];
            $draft_type = $v[1];
            
            list($module, $page) = $manager->entry_type_to_url[$draft_type];
            
            if ($priv->isPriv('select', $page)) {
                if(empty($rows[$entry_type])) {
                    continue;
                }
                
                // $v['num'] = (!empty($rows[$entry_type])) ? $rows[$entry_type] : 0;
                $v['num'] = $rows[$entry_type]; 
                $v['color'] = ($v['num']) ? 'red' : '#BFBFBF';
            
                $more = array(
                    'filter[s]' => 2, 
                    'filter[t]' => $manager->user_id
                    );
                
                $link = $this->controller->getLink($module, $page, false, false, $more);
                $v['status_link'] = $link;
                $v['status_title'] = $msg[$entry_type_str];
                $total += $v['num'];
            
                $tpl->tplParse($v, 'row');
            }
        }
        
        if($total) {
            // $tpl->tplSetNeeded('/total');
            $tpl->tplAssign('total_num', $total);
        } else {
            $tpl->tplParse('', 'empty');
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign('title_msg', $this->msg['my_approval_stats_msg']);        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxSetUserHome($ids) {

        $sm = new SettingModelUser(AuthPriv::getUserId());
        $sm->user_id = AuthPriv::getUserId();
        
        $setting_id = $sm->getSettingIdByKey('home_user_portlet_order');
        
        $column1_ids = implode(',', $ids[0]);
        $column2_ids = implode(',', $ids[1]);
        
        $value = $column1_ids . '|' . $column2_ids;
        if (strlen($column1_ids) == 0 && strlen($column2_ids) == 0) {
            $value = 'empty';
        }
        
        $sm->setSettings(array($setting_id => $value));

        $objResponse = new xajaxResponse();
        
        return $objResponse;    
    }
    
    
    function ajaxSetUserHomeDefault() {

        $sm = new SettingModelUser(AuthPriv::getUserId());
        $sm->user_id = AuthPriv::getUserId();
        
        $setting_id = $sm->getSettingIdByKey('home_user_portlet_order');
        $sm->setDefaultValues($setting_id);

        $objResponse = new xajaxResponse();
        
        $link = $this->controller->getAjaxLink('this', 'this');
        $objResponse->addRedirect($link);
        
        return $objResponse;    
    }
    
    
    function ajaxHideGetStartedBlock() {
        $objResponse = new xajaxResponse();
        
        $setting_id = $this->manager->sm->getSettingIdByKey('display_start_wizard');
        $this->manager->sm->setSettings(array($setting_id => 0));
        
        return $objResponse; 
    }
}
?>