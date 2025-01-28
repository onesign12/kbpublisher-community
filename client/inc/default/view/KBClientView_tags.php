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


class KBClientView_tags extends KBClientView_common
{


    function &execute(&$manager) {
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['menu_tags_msg'];
        $this->nav_title = $this->msg['menu_tags_msg'];
        
        $data = &$this->getTagsList($manager, $this->nav_title);
        
        return $data;
    }
    
    
    function &getTagsList($manager, $title) {
        
        $medium_cell_num = 3;
		$large_cell_num = 6;
		
        $limit = 40;
        
        $sort_param = 'ORDER BY entry_num DESC';
        
        $bp_hidden = false;
        if(!empty($_GET['qf'])) {
            $str = addslashes(stripslashes($_GET['qf']));
            $bp_hidden = array('qf' => $_GET['qf']);
            
            
            if(SphinxModel::isSphinxOnSearch($str)) {
                $sphinx['match'] = $str;
                $sphinx['where'] = 'AND active = 1';
                
                $options = array('index' => 'tag', 'limit' => $limit, 'sort' => $sort_param);
                $params = KBClientSearchModel_sphinx::parseFilterSql($sphinx, $options);
                
                $manager->setSqlParams($params['where']);
            
                if (!empty($params['sort'])) {
                    $sort_param = $params['sort'];
                }
                
                if (!empty($params['count'])) {
                    $count = $params['count'];
                }
                
            } else {
                $manager->setSqlParams("AND title LIKE '%{$str}%' OR description LIKE '%{$str}%'");
            }
        }
        
        $manager->setSqlParamsOrder($sort_param);
        
        if (!isset($count)) {
            $count = $manager->getTagCount();
        }
        
        $by_page = $this->pageByPage($limit, $count, false, false, $bp_hidden);
        // echo '<pre>', print_r($by_page, 1), '</pre>';
        
        $rows = $manager->getTagList($by_page->limit, $by_page->offset);
        $rows = $this->stripVars($rows);
		
		$medium_grid_num = round(12 / $medium_cell_num);
		$large_grid_num = round(12 / $large_cell_num);
        
        $tpl = new tplTemplatez($this->getTemplate('tags_list.html'));

        foreach($rows as $k => $v) {
			$v['medium_grid_num'] = $medium_grid_num;
			$v['large_grid_num'] = $large_grid_num;
			
            $more = array('s' => 1, 'q' => $v['title'], 'in' => 'all', 'by' => 'keyword');
            $v['tag_link'] = $this->getLink('search', false, false, false, $more);
			
			if ($v['description']) {
				$tpl->tplSetNeeded('row_td/description');
				$v['description'] = nl2br($v['description']);
			}

            $tpl->tplParse($v, 'row_td');
        }

        $form_hidden = '';
        if(!$this->controller->mod_rewrite) {
            $arr = array($this->controller->getRequestKey('view') => 'tags');
            $form_hidden = http_build_hidden($arr, true);
        }

        $tpl->tplAssign('hidden_search', $form_hidden);
        $tpl->tplAssign('form_search_action', $this->getLink('tags'));
        $tpl->tplAssign('qf', $this->stripVars(trim(@$_GET['qf']), array(), 'asdasdasda'));        
        
        $tpl->tplAssign('list_title', $title);
        
        // by page
        if($by_page->num_pages > 1) {
            $tpl->tplAssign('page_by_page_bottom', $by_page->navigate());
            $tpl->tplSetNeeded('/by_page_bottom');            
        }
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
}
?>