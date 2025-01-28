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


class LetterTemplateView_list extends AppView
{
    
    var $template = 'list.html';
    
    
    function execute(&$obj, &$manager) {

        $this->addMsg('common_msg.ini', 'email_setting');
    
        $tpl = new tplTemplatez($this->template_dir . $this->template);

        // get records
        $rows = $this->stripVars($manager->getRecords());
        
        // remove pluginable 
        $pluginable = AppPlugin::getPluginsFiltered('letters', 1);
        
        foreach(array_keys($rows) as $k1) {
            foreach($rows[$k1] as $k2 => $v2) {
                if(isset($pluginable[$v2['letter_key']])) {
                    if(!AppPlugin::isPlugin($pluginable[$v2['letter_key']])) {
                        unset($rows[$k1][$k2]);
                    }
                }
            }
        }
        $rows = array_filter($rows);
        
        
        $rows_msg = AppMailParser::getTemplateMsg();
        $group_titles = AppMsg::getMsg('ranges_msg.ini', false, 'letter_template_group_title');
        $group_sorting = array(1,2,3,6,5,4);
        
        foreach($group_sorting as $group_id) {
            
            if(!isset($rows[$group_id])) {
                continue;
            }
            
            $group = $rows[$group_id];
            
            $tpl->tplSetNeeded('row/group');
            $tpl->tplSetNeeded('row/group_delim');
            
            $tpl->tplAssign('group_id', $group_id);
            $tpl->tplAssign('delim_id', $group_id);
            
            $key_last = end($group);
            $key_last = $key_last['id'];
            
            foreach($group as $row) {
                
                $end = ($row['id'] == $key_last) ? '</div>' : '';
                $tpl->tplAssign('end', $end);
                
                $obj->set($row);
                if(!$obj->get('title')) {
                    $obj->set('title', $rows_msg[$row['letter_key']]['title']);
                }
                
                $deprecated = array(
                    'article_approve_to_admin', 'article_approve_to_user', 'article_approved',
                    'file_approve_to_admin', 'file_approve_to_user', 'file_approved',
                    );
                    
                if(in_array($obj->get('letter_key'), $deprecated)) {
                    $obj->set('title', '[DEPRECATED] ' . $obj->get('title'));
                }
                
                $v = $obj->get();
                $v['group_title_msg'] = $group_titles[$group_id];
                
                $tpl->tplAssign($this->getViewListVarsCustom($obj->get('id'), $obj->get('active'), $obj->get('in_out')));
                $tpl->tplParse(array_merge($v, $this->msg, $v), 'row');
            }
        } 

        
        $tpl->tplAssign($this->msg);
        //$tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function &getSort() {
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->default_order = 2;
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        //$sort->setOrder('sort_order', 1);
        
        //$sort->setSortItem('title_msg',  'title', 'letter_key',   $this->msg['title_msg']);
        //$sort->setSortItem('status_msg', 'status', 'active',  $this->msg['status_msg']);
        //$sort->setSortItem('', 'sort_order', 'sort_order',  '', 1);
        
        //$sort->getSql();
        //$sort->toHtml();
        return $sort;
    }
    
    
    function getViewListVarsCustom($record_id = false, $active = false, $in_out = false) {
        
        $row = parent::getViewListVars($record_id, $active);
        
        if($in_out == 1) {
            $row['in_out_img'] = $this->getImgLink(false, 'arrow_f', '');
        } else {
            $row['in_out_img'] = $this->getImgLink(false, 'arrow_b', '');
        }
        
        return $row;
    }
 
}
?>