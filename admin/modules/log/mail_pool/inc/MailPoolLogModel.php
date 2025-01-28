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

class MailPoolLogModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table' => 'email_pool');

    
    function getLetterTypeSelectRange($msg) {
        
        $msg = AppMsg::getMsg('log_msg.ini', false, 'letter_type');
        $sender = new AppMailSender();
                             
        foreach ($sender->letter_type as $id => $letter_type) {
            $data[$id] = $msg[$letter_type];            
        }
        
        return $data;
    }
    
}
?>