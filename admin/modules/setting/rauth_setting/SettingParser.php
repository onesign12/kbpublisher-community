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

namespace RAuthSetting;

use SettingParserCommon;


class SettingParser extends SettingParserCommon
{
        
    function parseInputOptions($key, $value) {
        $ret = false;
        
        if($key == 'remote_auth_script' && $this->isAuthRemoteDisabled()) {
            $ret = ' disabled';
        }
        
        return $ret;
    }
}
?>