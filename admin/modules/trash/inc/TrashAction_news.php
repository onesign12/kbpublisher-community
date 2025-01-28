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


class TrashAction_news extends TrashAction
{
    
    function __construct() {
        $this->emanager = new NewsEntryModel;
    }
    
    
    function restore($entry_obj) {
        
        $id = $entry_obj->get('id');
        
        $manager =& $this->emanager;
        
        $entry_obj->set('body_index', RequestDataUtil::getIndexText($entry_obj->get('body')));
        $entry_obj->set('place_top_date', NULL);
        
        $schedule = $entry_obj->getSchedule();
        foreach (array_keys($schedule) as $num) {
            $schedule[$num]['date'] = date('YmdHi00', $schedule[$num]['date']);
            $entry_obj->setSchedule($num, $schedule[$num]);
        }

        TrashAction_article::setEntryRoles($entry_obj, $manager);
        TrashAction_article::setEntryTags($entry_obj, $manager);
        
        $manager->addRecord($entry_obj);
        
        AppSphinxModel::updateAttributes('is_deleted', 0, $id, $manager->entry_type);
        
        return true;
    }
    
    
    function getPreview($entry_obj, $controller) {
        
        $entry_obj = unserialize($entry_obj);
        
        
        $view = new NewsEntryView_preview;        
        $view = $view->execute($entry_obj, $this->emanager);
        
        return $view;
    }   

}
?>