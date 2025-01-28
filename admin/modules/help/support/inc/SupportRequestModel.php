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


class SupportRequestModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table'=>'SupportRequest','user');
    var $custom_tables = array('category'=>'kb_category', 'list', 'list_value');
    
    
    function getData() {
        
    }
}
?>