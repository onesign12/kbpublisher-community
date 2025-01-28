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

class UserView_role_private extends UserView_role
{
    
    function getSelectId() {
        $select_id = 'role_read';
        if(isset($_GET['field_id']) && $_GET['field_id'] == 'selRoleWriteHandler') {
            $select_id = 'role_write';
        }
        
        if(isset($_GET['field_id']) && $_GET['field_id'] == 'selMustreadRoleHandler') {
            $select_id = 'mustread_role';
        }
        
        return $select_id;
    }
    
} 
?>