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

class KBClientModel_common extends KBClientModel
{
    
    function setCustomSettings($controller) {
        
        // always not display others in category if left menu
        $this->setting['num_entries_category'] = 0;


        // do not display categories on index page  if left menu
        $views = array('index', 'files');
        if(in_array($controller->view_id, $views) && !$controller->category_id) {
            $this->setting['num_category_cols'] = 0;
        }
        
    }

}
?>