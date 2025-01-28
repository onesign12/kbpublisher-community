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

class KBClientSearchEngine_mysql
{
    
    function getManager($manager, $values, $entry_type) {
        
        $smanager = new KBClientSearchModel_mysql($values, $manager);
        
        $smanager->user_id = $manager->user_id;
        $smanager->user_role_id = $manager->user_role_id;
        $smanager->entry_published_status = $manager->entry_published_status;
        $smanager->entry_type = $manager->entry_type;

        $smanager->entry_role_sql_from = $manager->entry_role_sql_from;
        $smanager->entry_role_sql_where = $manager->entry_role_sql_where;
        $smanager->sql_params_group =  $manager->sql_params_group;
        
        $smanager->setFullTextParams();
        $smanager->setDateParams();
        
        if($entry_type != 'all') {
            $smanager->setCustomFieldParams($manager);
        }

        if(in_array($entry_type, array('article', 'file'))) {
            $smanager->setCategoryParams($manager->categories);
        }

        if(in_array($entry_type, array('article'))) {
            $smanager->setEntryTypeParams(); // article type
        }

        $smanager->setSqlParams('AND ' . $manager->getPrivateSql(false), 'category');
        $smanager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
        
        $smanager->setOrderParams($values, $entry_type);
        $smanager->setGroupParams($values);
        
        return $smanager;
    }
    
    
    function getSearchData($manager, $controller, $values, $limit, $offset) {
        
        $rows = array();
        $count = array();
        $managers = array();
        $info = array();
        
        $article_setting = $manager->setting;
        $in = (isset($values['in'])) ? $values['in'] : 'all';
        $in = (is_array($in)) ? $in : array($in);
        
        if(array_intersect(['all','article'], $in)) {
            $values['in'] = 'article';
            $smanager = $this->getManager($manager, $values, 'article');
         
            $rows['article'] = $smanager->getArticleList($limit, $offset);
            $count['article'] = $smanager->getArticleCount();
            $managers['article'] = $manager;
            
            if(KBClientSearchHelper::isUseFilter($in, 'article', $values, $manager)) {
                $info['article'] = $smanager->getArticleFilterInfo($manager);
            }
        }

        
        $skip_in_attach = false;
        if(array_intersect(['all','file'], $in)) {
            
            if($manager->isModule('file')) {
                $values['in'] = 'file';
                $manager = &KBClientLoader::getManager($manager->setting, $controller, 'files');
                $smanager = $this->getManager($manager, $values, 'file');
            
                $rows['file'] = $smanager->getFileList($limit, $offset, $manager);
                $count['file'] = $smanager->getFileCount($manager);
                $managers['file'] = $manager;
                
                if(KBClientSearchHelper::isUseFilter($in, 'file', $values, $manager)) {    
                    $info['file'] = $smanager->getFileFilterInfo($manager);
                }
                
                $skip_in_attach = $smanager->getValuesString($rows['file'], 'id');
            }
        }
        
        // attachment
        if(array_intersect(['all','file'], $in)) {
            if(KBClientSearchModel::isSearchInAttachments($values)) {
                
                $smanager = $this->getManager($manager, $values, 'article');
                $smanager->setSearchAttachmentParams($skip_in_attach); // $search_type = 1; fulltext
                
                $rows['attachment'] = $smanager->getArticleList($limit, $offset);
                $count['attachment'] = $smanager->getArticleCount();
            }
        }
        
        
        if(array_intersect(['all','news'], $in)) {
            if($manager->isModule('news')) {
                $values['in'] = 'news';
                $manager = &KBClientLoader::getManager($article_setting, $controller, 'news');
                $smanager = $this->getManager($manager, $values, 'news');
            
                $rows['news'] = $smanager->getNewsList($limit, $offset, $manager);
                $count['news'] = $smanager->getNewsCount($manager);
                $managers['news'] = $manager;
                
                // 16-05-2024
                if(KBClientSearchHelper::isUseFilter($in, 'news', $values, $manager)) {    
                    $info['news'] = $smanager->getNewsFilterInfo($manager);
                }
            }
        }
        
        
        return array($count, $rows, $managers, $info);
    }
    
}
?>