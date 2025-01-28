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


class KBClientView_pool extends KBClientView_common
{

    function execute($manager) {
        
        $this->home_link = true;
        $this->nav_title = $this->msg['pinned_items_msg'];
        
        $ids = $this->getUserPool('pool');
        if (count($ids) > 0) {
            return $this->parseArticleList($manager, $ids);
            
        } else {
            return $this->getActionMsg('success', 'list_empty');
        }
    }
    
    
    function parseArticleList($manager, $ids) {
        
        $ids_str = implode(',', $ids);
        
        // fetching all
        $manager->setSqlParams(sprintf("AND e.id IN (%s)", $ids_str));
        $all_rows = $manager->getEntryList(-1, -1);
        $alive_ids = $manager->getValuesArray($all_rows, 'id');
        
        // private
        $policy = $manager->getSetting('private_policy');
        $manager->setting['private_policy'] = 1;
        $manager->setSqlParams('AND ' . $manager->getPrivateSql('pool'));
        $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
        
        $rows = $this->stripVars($manager->getEntryList(-1, -1));
        $visible_ids = $manager->getValuesArray($rows, 'id');
        
        $manager->setting['private_policy'] = $policy;
        
        $tpl = new tplTemplatez($this->getTemplate('pool_list.html'));

        // entry_type
        $types = ListValueModel::getListRange('article_type', false);

        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];
            
            $row['updated_date'] = $this->getFormatedDate($row['ts_updated']);

            $entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
            $row['entry_link'] = $this->getLink('entry', $row['category_id'], $entry_id);

            $row['entry_id'] = $this->getEntryPrefix($row['id'], $row['entry_type'], $types, $manager);
            $tpl->tplParse(array_merge($row, $this->msg), 'row');
        }
        
        $tpl->tplAssign('list_title', $this->nav_title);
        
        // actions
        $range = array(
            'print'  => $this->msg['print_msg'],
            'pdf'    => $this->msg['article_pdf_msg'],
            'delete' => $this->msg['remove_msg']);
            
        if(!AppPlugin::isPlugin('export') || !BaseModel::getExportTool()) {
             unset($range['pdf']);
        }
        
        foreach($range as $k => $v) {
            $a['action'] = $k;
            $a['title'] = $v;
            $tpl->tplParse($a, 'actions');
        }
        
        
        // refreshing
        $box = '';
        if (count($alive_ids) != count($ids)) { // dead
            $remove_ids = array_diff($ids, $alive_ids);
            
            $msg = BoxMsg::factory('hint');
            $msg->setMsgs(false, $this->msg['pool_refreshed_deleted_msg']);
            $msg->setOptions(array('effect' => 'fadeOut(7000)', 'close_btn' => true)); 
            $msg->assignVars('num', sprintf('(%s)', count($remove_ids)));
            $box .= $msg->get();
        }
        
        if (count($visible_ids) != count($alive_ids)) { // private
            $hidden_ids = array_diff($alive_ids, $visible_ids);
            
            $msg = BoxMsg::factory('hint');
            $msg->setMsgs(false, $this->msg['pool_refreshed_hidden_msg']);
            $msg->setOptions(array('effect' => 'fadeOut(7000)', 'close_btn' => true)); 
            $msg->assignVars('num', sprintf('(%s)', count($hidden_ids)));
            $msg->assignVars('link', $this->getLink('login', false, false, 'pool'));
            $box .= $msg->get();
        }
        
        
        $refresh_js = '';
        if ($box) {
            if (!empty($alive_ids)) {
                $refresh_js = 'PoolManager.replace([%s]);$(".pool_num").attr("data-badge", %d);';
                $refresh_js = sprintf($refresh_js, implode(',', $alive_ids), count($visible_ids));
                
            } else { // no one's left
                $refresh_js = 'PoolManager.empty();$(".pool_block").hide();';
                $box .= $this->getActionMsg('success', 'list_empty');
            }
            
            $tpl->tplAssign('msg', $box);
        }
        $tpl->tplAssign('refresh_js', $refresh_js);
        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
}
?>