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

namespace PluginSetting;

use BaseModel;
use Validator;
use AppMsg;


class SettingValidator
{
     
    function validate($values) {
        $v = new Validator($values, true);
        

    
        return $v->getErrors();
    }
    
}
?>