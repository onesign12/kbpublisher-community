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


class KBClientView_rssfeed extends KBClientView_common
{

    function &execute(&$manager) {
        
        $this->home_link = true;
        $this->meta_title = &$this->msg['rss_title_msg'];
        //$this->meta_keywords = &$row['meta_keywords'];
        //$this->meta_description = &$row['meta_description'];
        $this->nav_title = &$this->msg['rss_title_msg'];

        $data = &$this->getList($manager);
        
        return $data;        
    }
    
    
    function &getList(&$manager)  {
        
        $tpl = new tplTemplatez($this->getTemplate('rss_list.html'));
        
        $rss_title = $manager->getSetting('rss_title');
        $rss_description = $manager->getSetting('rss_description');
        $rss_file = $this->controller->kb_path . 'rss.php';

        $rows = array();
        $rows[0]['title'] = $rss_title;
        $rows[0]['description'] = $rss_description;
        $rows[0]['entry_link'] = $rss_file;
        
        // news
        if($manager->isModule('news')) {
            $title = sprintf('%s (%s)', $rss_title, $this->msg['news_title_msg']);
            $rows['n']['title'] = $title;
            $rows['n']['description'] = '';
            $rows['n']['entry_link'] = $rss_file . '?t=n';
        }
        
        
        // top
        if($manager->getSetting('rss_generate') == 'top') {
            
            $categories = $manager->getCategoryRssData();
            $categories = $this->stripVars($categories);
            
            foreach(array_keys($categories) as $category_id) {
                $v = $categories[$category_id];
                $rows[$category_id]['title'] = sprintf('%s', $v['title']);
                $rows[$category_id]['description'] = sprintf('%s', $v['description']);
                $rows[$category_id]['entry_link'] = $rss_file . '?c=' . $category_id;
                
                unset($categories[$category_id]);
            }
        }
        
        $rows = $this->stripVars($rows);
        foreach($rows as $k => $v) {
            $v['item_img'] = $this->_getItemImg(1, false, 'rss');
            $tpl->tplParse($v, 'row');
        }
        
        $tpl->tplAssign('list_title', $this->msg['rss_title_msg']);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>