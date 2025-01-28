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

class UserList extends AppObj
{
    
    var $properties = array(
        'id'                => NULL,
        'notification_key'  => '',
        'user_id'           => 0,
        'date_posted'       => '',
        'notification_type' => 0,
        'title'             => '',
        'message'           => '',
        'active'            => 1
    );
    
}
?>