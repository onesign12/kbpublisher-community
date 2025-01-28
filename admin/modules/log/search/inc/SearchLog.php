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

class SearchLog extends AppObj
{
    
    var $properties = array('user_id'    => '',
                            'date_search'=> '',
                            'search_type'    => '',
                            'search_option'    => '',
                            'search_string'    => '',
                            'user_ip'    => '',                          
                            'exitcode'    => 0
                            );
    
}
?>