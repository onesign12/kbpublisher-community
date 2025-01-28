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



class UserListModelBulk extends BulkModel
{

    var $actions = array('enable_mail', 'disable_mail', 'remove');
    var $actions_immidiate = array('enable_mail', 'disable_mail', 'remove');
    var $msg_key = 'bulk_user_list';

}
?>