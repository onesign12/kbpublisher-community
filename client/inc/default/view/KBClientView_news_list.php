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


class KBClientView_news_list extends KBClientView_news
{

    function &execute(&$manager) {
        
        $this->home_link = true;
        $this->meta_title = $this->msg['menu_news_msg'];
        $this->nav_title = $this->msg['menu_news_msg'];
        
        if($this->category_id) {
            //$this->meta_title = $this->msg['menu_news_msg'] . ' - ' . $this->category_id;
            $this->nav_title = array( $this->getLink('news') => $this->msg['menu_news_msg'], $this->category_id);
        }        
        
        
        $data = &$this->getList($manager);
        
        return $data;        
    }
}
?>