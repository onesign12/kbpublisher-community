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


class KBEntryView_tags extends AppView 
{
    
    var $template = 'tags_list.html';
 

    function execute(&$obj, &$manager, $form_action = false, $form_params = false) {
        $limit = 50;
        
        $bp_hidden = false;
        if(isset($_GET['qf'])) {
            $str = addslashes(stripslashes($_GET['qf']));
            $bp_hidden = array('qf' => $_GET['qf']);
            $manager->tag_manager->setSqlParams("AND title LIKE '%{$str}%' OR description LIKE '%{$str}%'");
        }
        
        $this->template_dir = APP_MODULE_DIR . 'knowledgebase/entry/template/';
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        // from triggers        
        $trigger_type = (isset($_GET['trtype'])) ? $_GET['trtype'] : '';
        $trigger_num = (isset($_GET['trnum'])) ? (int) $_GET['trnum'] : '';
        $tpl->tplAssign('trigger_form', ($trigger_type) ? 1 : 0);
        $tpl->tplAssign('num', $trigger_num);
        $tpl->tplAssign('type', $trigger_type);
        
        $manager->tag_manager->setSqlParamsOrder('ORDER BY title');

        // header generate
        $bp_options = array('class' => 'page');
        $bp =& $this->pageByPage($limit, $manager->tag_manager->getRecordsSql(), $bp_options);
        
        // get records
        $rows = $this->stripVars($manager->tag_manager->getRecords($bp->limit, $bp->offset));
        $ids = array();
        
        foreach($rows as $k => $v) {
            $i = 0;
            
            $more = array('s' => 1, 'q' => $v['title'], 'in' => 'article_keyword');
            $v['tag_link'] = $this->getLink('search', false, false, false, $more);
            $v['description'] = nl2br($v['description']);
            
            $ids[] = $v['id'];

            $tpl->tplParse($v, 'row'); // parse nested

            $i ++;
        }
        
        $tpl->tplAssign('tag_ids', implode(',', $ids));
        
        
        // search
        if (!$form_action) {
            $form_action = $this->getActionLink('tags');
            $form_params = array(
                'module' => $this->controller->module,
                'page' => $this->controller->page,
                'action' => 'tags',
                'popup' => 1);
                
            if($this->controller->sub_page) {
                $form_params['sub_page'] = $this->controller->sub_page;
            }
        }
        
        $tpl->tplAssign('form_search_action', $form_action);
        $tpl->tplAssign('hidden_search', http_build_hidden($form_params, true));

        $tpl->tplAssign('qf', $this->stripVars(trim(@$_GET['qf']), array(), 'asdasdasda'));
                
        
        // by page
        if($bp->num_pages > 1) {
            $tpl->tplAssign('page_by_page', $bp->navigate());
            $tpl->tplSetNeeded('/by_page');
        }
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
    
}
?>