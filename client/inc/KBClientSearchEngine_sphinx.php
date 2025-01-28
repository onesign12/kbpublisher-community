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


class KBClientSearchEngine_sphinx
{
    
    // here we can have array of managers for manager param
    function getManager($manager, $values, $entry_type) {
        
        if(is_array($manager)) {
            list($manager, $managers) = $manager;
        } else {
            $managers = array($entry_type => $manager);
        }
        
        $smanager = new KBClientSearchModel_sphinx($values, $manager);
        $smanager->setCategoryRolesParams($managers);
        $smanager->setCategoryStatusParams($managers);
        $smanager->setStatusParams($managers);
        $smanager->setPrivateParams($manager);
        $smanager->setEntryRolesParams($manager);
        $smanager->setFullTextParams();
        $smanager->setDateParams();
        
        if($entry_type != 'all') {
            $smanager->setCustomFieldParams($manager);
        }
        
        if(count($managers) == 1) {
            
            $entry_type = key($managers);
            if(array_intersect(array_keys($managers), array('article', 'file'))) {
                $smanager->setCategoryParams($managers[$entry_type]->categories);
            }
        
            if(array_intersect(array_keys($managers), array('article'))) {
                $smanager->setEntryTypeParams();  // article type
            }
        
            $smanager->setOrderParams($values, $entry_type);
        
        } else {
            $smanager->setOrderParams($values, 'article'); // date_updated
        }
        
        return $smanager;
    }
    
    
    function getSearchData($manager, $controller, $values, $limit, $offset) {
        
        $rows = array();
        $count = array();
        $managers = array();
        $info = array();
        $source_id = array();
        
        $article_setting = $manager->setting;
        $in = (isset($values['in'])) ? $values['in'] : 'all';
        $in = (is_array($in)) ? $in : array($in);

        if(array_intersect(['all','article'], $in)) {    
            $source_id[] = 1;
            $managers['article'] = $manager;
        }

        if(array_intersect(['all','file'], $in)) {
            if($manager->isModule('file')) {
                $source_id[] = 2;
                $managers['file'] = &KBClientLoader::getManager($manager->setting, $controller, 'files');
            }
        }

        if(array_intersect(['all','news'], $in)) {
            if($manager->isModule('news')) {
                $source_id[] = 3;
                $managers['news'] = &KBClientLoader::getManager($article_setting, $controller, 'news');
            }
        }

        if(array_intersect(['all','file'], $in)) {
            if(KBClientSearchModel::isSearchInAttachments($values)) {
                $source_id[] = 5;
                $managers['attachment'] = &KBClientLoader::getManager($manager->setting, $controller, 'articles');
                $managers['attachment']->entry_type = 5;    // to set correct source
                $managers['attachment']->entry_list_id = 2; // files to find published statuses
            }
        }
        
        
        $smanager = $this->getManager([$manager, $managers], $values, 'article');
        
        // set skip in attachments if found in files
        if(isset($managers['file']) && isset($managers['attachment'])) {
            $fmanager = $this->getManager($managers['file'], $values, 'file');
            $fmanager->smanager->setIndexParams('file');

            if($frows = $fmanager->smanager->getRecords($limit, $offset)) {                
                $file_ids = $smanager->getValuesString($frows, 'entry_id', true);
                $smanager->setSkipAttachmentsParams($file_ids);
            }
        }
        
        $smanager->smanager->setSourceParams($source_id);
        $smanager->smanager->setSqlParamsSelect('source_id');
        $smanager->smanager->setSqlParamsFrom($smanager->smanager->idx->client);
        $_rows = $smanager->smanager->getRecords($limit, $offset);
        
        $data = array();
        if (is_array($_rows)) {
            foreach ($_rows as $v) {
                $data[$v['source_id']][$v['entry_id']] = $v['score'];
            }
        }
        
        if (!empty($data[1])) {
            $rows['article'] = $smanager->_getSearchRows($data[1], 'getArticlesSql', $managers['article']);
            if(KBClientSearchHelper::isUseFilter($in, 'article', $values, $manager)) {    
                $info['article'] = $smanager->getArticleFilterInfo($managers['article']);
            }
        }

        if (!empty($data[2])) {
            $rows['file'] = $smanager->_getSearchRows($data[2], 'getFilesSql', $managers['file']);
            if(KBClientSearchHelper::isUseFilter($in, 'file', $values, $manager)) {    
                $info['file'] = $smanager->getFileFilterInfo($managers['file']);
            }
        }

        if (!empty($data[3])) {
            $rows['news'] = $smanager->_getSearchRows($data[3], 'getNewsSql', $managers['news']);
            
            // 16-05-2024
            if(KBClientSearchHelper::isUseFilter($in, 'news', $values, $manager)) {    
                $info['news'] = $smanager->getNewsFilterInfo($managers['news']);
            }
        }

        if (!empty($data[5])) {
            $rows['attachment'] = $smanager->_getSearchRows($data[5], 'getAttachmentsSql', $managers['attachment']);
        }
        
        // count
        if (!empty($data)) {
            $smanager->smanager->setSqlParamsSelect('COUNT(*) as count, source_id');
            $smanager->smanager->sql_params_group2 = 'GROUP BY source_id';
            $smanager->smanager->match_check = false; 
            $count_rows = $smanager->smanager->getRecords(5, 0);
            
            if (is_array($count_rows)) {
                foreach ($count_rows as $v) {
                    $entry_type = $smanager->record_type[$v['source_id']];
                    $count[$entry_type] = $v['count'];
                }
            }
        }
        
        return array($count, $rows, $managers, $info);
    }
    
}
?>