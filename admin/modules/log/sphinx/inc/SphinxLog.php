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

class SphinxLog extends AppObj
{
    
    var $properties = array('id'           => NULL,
                            'date_executed'=> '',
                            'entry_type'   => 0,
                            'action_type'  => 0,
                            'output'       => '',
                            'exitcode'     => 1
                            );
    
    
}
?>