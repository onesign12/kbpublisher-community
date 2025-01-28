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

class KBAutosave extends AppObj
{
    
    var $properties = array('id_key'        => '',
                            'entry_id'      => 0,
                            'entry_type'    => 1,
                            'user_id'       => 0,
                            'entry_obj'     => '',
                            'date_saved'    => '',
                            'active'        => 1
                            );
    
    
    var $hidden = array('id_key', 'date_saved');

}
?>