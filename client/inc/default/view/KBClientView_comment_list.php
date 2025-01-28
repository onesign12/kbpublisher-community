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


class KBClientView_comment_list extends KBClientView_comment
{

    function &execute(&$manager) {
        
        $row = $manager->getEntryById($this->entry_id, $this->category_id);
        $row = $this->stripVars($row);
        if(!$row) { $a = ''; return $a; }
        
        $this->parse_form = true;
        $this->meta_title = $this->getSubstring($row['title'] , 100). ' / ' . $this->msg['comment_title_msg'];
        
        //$this->nav_title = false;
        //if($manager->getSetting('show_title_nav')) {
            $type = ListValueModel::getListRange('article_type', false);        
            $prefix = $this->getEntryPrefix($row['id'], $row['entry_type'], $type, $manager);
            $title = $prefix . $this->getSubstring($row['title'], 50, '...');

            $entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
            $link = $this->getLink('entry', false, $entry_id);
            $this->nav_title = array($link => $title, $this->msg['comment_title_msg']);
        //}
        
        
        $data = $this->getList($manager, $row);
        
        if($this->isCommentable($manager, $row['commentable'])) {
            $data .= $this->getForm($manager, $row, $this->msg['add_comment_msg'], 'comment');
        }
        
        return $data;        
    }
}
?>