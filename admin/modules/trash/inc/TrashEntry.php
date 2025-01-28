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

class TrashEntry extends AppObj
{
    
    var $properties = array('id'                => NULL,
                            'entry_id'          => NULL,
                            'entry_type'        => 0,
                            'user_id'           => 0,
                            'entry_obj'         => '',
                            'extra_data'        => '',
                            'date_deleted'      => ''
                            );
}
?>