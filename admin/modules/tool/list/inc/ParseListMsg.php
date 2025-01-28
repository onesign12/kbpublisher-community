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

class ParseListMsg
{

    static function getGroupMsg() {
        return AppMsg::getMsg('setting_msg.ini', false, 'list_group');
    }
    
    
    static function getValueMsg($key) {
        return AppMsg::getMsg('setting_msg.ini', false, 'list_' . $key);
    }
    
    
    static function getValueAllMsg() {
        return AppMsg::getMsg('setting_msg.ini', false);
    }
}
?>