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


class SubscriptionView_list_files_cat extends SubscriptionView_list_articles_cat
{

    var $tmpl = 'articles_list_cat.html';
    var $table_name = 'category_files';
    var $entry_type = 12;
    var $client_view_str = 'files';


    function getCategoryManager($manager) {
        return $manager->getFileManager();
    }    
}
?>