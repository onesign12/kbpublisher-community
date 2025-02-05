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


class FileCategoryView_list extends KBCategoryView_list
{
        
    var $columns = array('id', 'private', 'title', 'admin', 'entry_num', 'draft_num', 'sort_order', 'published');
    
    
    function getEntryLink($cat_id) {
        $more = array('filter[c]'=>$cat_id);
        return $this->getLink('file', 'file_entry', false, false, $more);
    }
    
    
    function getDraftLink($cat_id) {
        $more = array('filter[q]' => "cat_id:$cat_id");
        return $this->getLink('file', 'file_draft', false, false, $more);
    }
    
    
    function getBulkModel() {
        return new FileCategoryModelBulk();
    }
    
    
    function getBulkView($obj, $manager) {
        return $this->controller->getView($obj, $manager, 'FileCategoryView_bulk');
    }
    
    
    function getListColumns() {
                
        $options2 = array(
            'attachable' => array(
                'type' => 'bullet',
                'shorten_title' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="#fff" d="M21.586 10.461l-10.05 10.075c-1.95 1.949-5.122 1.949-7.071 0s-1.95-5.122 0-7.072l10.628-10.585c1.17-1.17 3.073-1.17 4.243 0 1.169 1.17 1.17 3.072 0 4.242l-8.507 8.464c-.39.39-1.024.39-1.414 0s-.39-1.024 0-1.414l7.093-7.05-1.415-1.414-7.093 7.049c-1.172 1.172-1.171 3.073 0 4.244s3.071 1.171 4.242 0l8.507-8.464c.977-.977 1.464-2.256 1.464-3.536 0-2.769-2.246-4.999-5-4.999-1.28 0-2.559.488-3.536 1.465l-10.627 10.583c-1.366 1.368-2.05 3.159-2.05 4.951 0 3.863 3.13 7 7 7 1.792 0 3.583-.684 4.95-2.05l10.05-10.075-1.414-1.414z"/></svg>',
                'width' => 'min',
                'align' => 'center',
                'options' => 'text-align: center;',
                'params' => array(
                    'text' => 'attachable')
            )
        );

        $options = parent::getListColumns();
        unset($options['commentable'], $options['ratingable'], $options['type']);
        unset($options[array_search('type', $options)]);
        
        $options = ExtFunc::array_insert($options, $options2, 8);
            
        return $options;
    }
}
?>