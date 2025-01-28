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

class KBEntryView_rate extends AppView 
{
    
    function execute(&$obj, &$manager) {
        
        $eobj = new KBRate;
        $eobj->set('entry_id', $obj->get('id'));
        $emanager = new KBRateModel;
    
        $view = new KBRateView_list_entry;
        $view->template_dir = APP_MODULE_DIR . 'knowledgebase/rate/template/';
        $view_html = $view->execute($eobj, $emanager);
        
        $tabs_html = KBEntryView_common::getEntryMenu($obj, $manager, $this);
        $html = $tabs_html . $view_html; 
        
        return $html;
    }
}
?>