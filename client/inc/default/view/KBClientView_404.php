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


class KBClientView_404 extends KBClientView_common
{    
    
    function &execute(&$manager) {
        
        $this->meta_title = $this->msg['page_not_found_msg'];
        
        $this->nav_title = $this->meta_title;
        $this->home_link= true;
        // $this->parse_form = false;
        
        $data = $this->getForm($manager);
                        
        return $data;
    }


    function getForm($manager) {
        
        $tpl = new tplTemplatez($this->getTemplate('404.html'));
        
        $file = $this->getMsgFile('after_action_msg.ini', 'public');
        $msgs = AppMsg::parseMsgs($file, 'http_404', true);
        $tpl->tplAssign('error_title', $msgs['title']);
        $tpl->tplAssign('error_body', $msgs['body']);
        

        // category
        // $this->controller->getView('index');
        // $view = new KBClientView_index($manager);
        //
        // $rows = $this->stripVars($manager->getCategoryList($this->top_parent_id));
        // $title = $this->msg['category_title_msg'];
        //
        // $block_settings = array();
        // $block_settings['num_columns'] = $manager->getSetting('num_category_cols');
        // $block_settings['title'] = $this->msg['category_title_msg'];
        // $view = $view->getCategoryList($rows, $block_settings, $manager);
        // // $view = $view->getCategoryList($rows, $title, $manager);
        // $tpl->tplAssign('category_list_tmpl', $view);
         
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
}
?>