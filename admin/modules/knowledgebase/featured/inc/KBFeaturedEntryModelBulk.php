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



class KBFeaturedEntryModelBulk extends BulkModel
{

    var $actions = array('remove');
    var $actions_immidiate = array('remove');
    var $msg_key = 'bulk_featured';


    function setActionsAllowed($manager, $priv, $allowed = array()) {

        $actions = $this->getActionAllowedCommon($manager, $priv, $allowed);

        $this->actions_allowed = array_keys($actions);
        return $this->actions_allowed;
    }
    
}
?>