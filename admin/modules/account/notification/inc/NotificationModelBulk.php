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



class NotificationModelBulk extends BulkModel
{

    var $actions = array('read', 'unread', 'delete');
    var $actions_immidiate = array('read', 'unread', 'delete');
    var $msg_key = 'bulk_notification';

}
?>