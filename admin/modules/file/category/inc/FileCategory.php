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



class FileCategory extends KBCategory
{
    
    var $properties = array('id'             => NULL,
                            'parent_id'      => 0,
                            'name'           => '',
                            'description'    => '',
                            'sort_order'     => 'sort_end',
                            'sort_public'    => 'default',
                            //‘num_entry'      => 0,
                            'attachable'     => 1,
                            'private'        => 0,
                            // 'active_real' => 1,
                            'active'         => 1
                            );
                            
    
}
?>