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


class KBClientView_dynamic_file extends KBClientView_files
{
    
    var $dynamic_limit = 25;
    var $dynamic_reload_limit = 200;
    var $dynamic_sname = 'kb_dynamic_loaded_file_%s_';
    // var $load_button = true;
    
    
    function &execute(&$manager) {

        $limit = $this->dynamic_limit;
        $sname = sprintf($this->dynamic_sname, $this->dynamic_type);
        
        // setcookie($sname, null, time(), '/'); unset($_COOKIE[$sname]);
        
        if (!empty($_COOKIE[$sname])) {
            $limit = $_COOKIE[$sname];
            if ($limit > $this->dynamic_reload_limit) {
                $limit = $this->dynamic_reload_limit;
            }
        }
        
        
        list($rows, $title) = $this->getRows($manager, $limit);
        
        if (count($rows) <= $limit) {
            // $this->load_button = false;
            $this->dynamic_limit = false;
            
        } else {
            array_pop($rows);
        }
        
        
        $this->home_link = true;
        $this->nav_title = $title;
        
        $this->meta_title = $title;
        // $this->meta_keywords = $manager->getSetting('site_keywords');
        // $this->meta_description = $manager->getSetting('site_description');
        
        $data = $this->parseFileList($manager, $this->stripVars($rows), $title);
                
        return $data;
    }
    
    
    function getRows($manager, $limit, $offset = 0) {
        
        $rows = array();
        
        $manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
        $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
        
        switch ($this->dynamic_type) {
        case 'recent':
            $title = $this->msg['recently_posted_files_title_msg'];
            $this->setRecentlyPostedSqlParams($manager);
            $rows =  $manager->getEntryList($limit + 1, $offset, 'index', 'FORCE INDEX (date_updated)');
            break;
            
        case 'popular':
            $title = $this->msg['most_downloaded_files_title_msg'];
            $this->setMostViewedSqlParams($manager);
            $rows = $manager->getEntryList($limit + 1, $offset, 'index', 'FORCE INDEX (downloads)'); 
        	break;
        }
        
        return array($rows, $title);  
    }
    
    
    function &getBlockListOption(&$tmpl, $manager, $options = array()) {
        $a = '';
        return $a;
    }
    
}
?>