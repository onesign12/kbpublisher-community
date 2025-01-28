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

class TrashAction_file extends TrashAction_article
{
    
    function __construct() {
        $this->emanager = new FileEntryModel_dir;
        $setting = SettingModel::getQuick(1);
        $setting = $this->emanager->setFileSetting($setting);
    }
    
    
    function restore($entry_obj) {
        
        $id = $entry_obj->get('id');
        
        $manager =& $this->emanager;
        
        $category_ids = implode(',', $entry_obj->getCategory());
        $manager->cat_manager->setSqlParams(sprintf('AND c.id IN (%s)', $category_ids));
        $categories = $manager->cat_manager->getRecords();
        
        $entry_obj->setCategory(array_keys($categories));
        
        $sort_values = $manager->updateSortOrder($id, $entry_obj->getSortValues(),
                                                 $entry_obj->getCategory(), 'insert');

        
        $schedule = $entry_obj->getSchedule();
        foreach (array_keys($schedule) as $num) {
            $schedule[$num]['date'] = date('YmdHi00', $schedule[$num]['date']);
            $entry_obj->setSchedule($num, $schedule[$num]);
        }
        
        TrashAction_article::setEntryRoles($entry_obj, $manager);
        TrashAction_article::setEntryTags($entry_obj, $manager);
        
        $manager->addRecord($entry_obj, $sort_values);
        
        AppSphinxModel::updateAttributes('is_deleted', 0, $id, $manager->entry_type);
        
        return true;
    }
    
    
    function getPreview($entry_obj, $controller) {
        
        $manager =& $this->emanager;
        $entry_obj = unserialize($entry_obj);
        
        $setting = SettingModel::getQuick(1);
        $setting = $manager->setFileSetting($setting);
        
        $manager->sendFileDownload($entry_obj->get(), true);
        exit;

        return $view;
    }
    
    
    static function getTitleStr($obj_str) {
        // $search = '#s:5:"title";s:\d+:"(.*?)";s:4:"body";#';
        $search = '#s:8:"filename";s:\d+:"(.*?)";s:14:"filename_index";#';
        preg_match($search, $obj_str, $matches);
        return (!empty($matches[1])) ? $matches[1] : '';
    }
}
?>