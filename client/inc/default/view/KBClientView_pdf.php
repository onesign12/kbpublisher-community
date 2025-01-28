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


class KBClientView_pdf extends KBClientView_common
{

    static $cache = true; // or false
    static $cache_lifetime = null; // in sec, null = never expired
    

    function execute($manager) {
        
        $is_plugin = AppPlugin::isPlugin('export');        
        $plugin = BaseModel::getExportTool();
        if(!$is_plugin || !$plugin) {
            $this->controller->go('index', $this->category_id, false, 'no_export_plugin');
        }
        
        $category = ($this->view_id == 'pdf-cat');
        $list = (!empty($_GET['id']));
        
        if($category) {
            return $this->getCategory($manager, $plugin);
            
        } elseif($list) {
            return $this->getEntryList($manager, $plugin, $_GET['id']);
        
        } else {
            return $this->getEntry($manager, $plugin);
        }
    }
    
    
    function getCategory($manager, $plugin) {
        if($plugin == 'wkhtmltopdf') {
            return $this->getCategoryWkHtmlToPdf($manager);
        
        } elseif($plugin == 'htmldoc') {
            return $this->getCategoryHtmlDoc($manager);
        }
        
        return false;
    }
    

    function getCategoryHtmlDoc($manager) {

        $cats = KBExport::getData($manager, $this->category_id);
        $full_cats = $manager->getCategorySelectRangeFolow();
        $cat_name = $full_cats[$this->category_id];
        
        $config = array(
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'temp_dir'      => KBExportHtmldoc::getTempDir(APP_CACHE_DIR, 'pdf'),
            'tool_path'     => $manager->getSetting('plugin_htmldoc_path'),
            'http_host'     => $_SERVER['HTTP_HOST']
        );
        
        $settings = array(
            'book'        => true,
            'no-title'    => true,
            'fontsize'    => $manager->getSetting('htmldoc_fontsize'),
            'bodyfont'    => $manager->getSetting('htmldoc_bodyfont')
            // 'fontspacing' => 1.2
        );

        $export = KBExport::factory('pdf');
        $export->setConfig($config);
        $export->setSettings($settings);
        $export->createTempDir();

        $data = $export->export($cats, $manager, $this->controller, $this);
        $export->removeTempDir();
        
        $this->sendFile($data, $cat_name);
    }
    
    
    function getCategoryWkHtmlToPdf($manager) {

        $cats = KBExport2::getData($manager, $this->category_id);
        $full_cats = $manager->getCategorySelectRangeFolow();
        $cat_name = $full_cats[$this->category_id];
		
		$config = $this->getWkHtmlToPdfConfig($manager->setting);		
		$config['category_id'] = $this->category_id;
        $config['print_entry_info'] = false; // disabled in client for category

        $export = KBExport2::factory('pdf');
        $export->setComponents($manager, $this->controller, $this);
        $export->setConfig($config);
        $export->createTempDirs();

        $data = $export->export($cats);
        $export->removeTempDir();
                               
        $this->sendFile($data, $cat_name);
    }
    
        
    function getEntryList($manager, $plugin, $ids) {
        if($plugin == 'wkhtmltopdf') {
            return $this->getEntryListWkHtmlToPdf($manager, $ids);
        }
        
        return false;
    }
    
    
    function getEntryListWkHtmlToPdf($manager, $ids) {
        
        $ids = array_slice($ids, 0, 50); // set limit
        $ids = array_unique(array_map('intval', $ids));
        
        $manager->setting['private_policy'] = 1; // set Login policy not to display
        $manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
        $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));        
        
        $manager->setSqlParams(sprintf("AND e.id IN (%s)", implode(',', $ids)));
        $rows = $manager->getEntryList(-1, -1);
        
        foreach (array_keys($rows) as $k) {
            $rows[$k] = $this->_getEntryBody($manager, $rows[$k]);
        }
		
		$config = $this->getWkHtmlToPdfConfig($manager->setting);
		
        $export = KBExport2::factory('pdf');
        $export->setComponents($manager, $this->controller, $this);
        $export->setConfig($config);
        $export->createTempDirs();

        $data = $export->exportEntry($rows);
        $export->removeTempDir();
        
        $this->sendFile($data, 'export');
    }


    function getEntry($manager, $plugin) {
    
        if($plugin == 'wkhtmltopdf') {
            return $this->getEntryWkHtmlToPdf($manager);
    
        } elseif($plugin == 'htmldoc') {
            return $this->getEntryHtmlDoc($manager);
        }
    
        return false;
    }


    function getEntryHtmlDoc($manager) {
                
        // get entry
        $row = $this->_getEntryBody($manager);
        
        if(empty($row)) { return; }

        $config = array(
            'document_root'    => $_SERVER['DOCUMENT_ROOT'],
            'temp_dir'         => KBExportHtmldoc::getTempDir(APP_CACHE_DIR, 'pdf'),
            'tool_path'        => $manager->getSetting('plugin_htmldoc_path'),
            'http_host'        => $_SERVER['HTTP_HOST'],
            'print_entry_info' => $manager->getSetting('show_pdf_link_entry_info')
        );
        
        $settings = array(
            'webpage'     => true,
            'no-title'    => true,
            'no-toc'      => true,
            'fontsize'    => $manager->getSetting('htmldoc_fontsize'),
            'bodyfont'    => $manager->getSetting('htmldoc_bodyfont')
            // 'fontspacing' => 1.2
        );

        $pdfs = $settings;
        $pdfs['print_entry_info'] = $config['print_entry_info'];
        $cache = $this->getCache($row, $pdfs);
        if($cache && !is_a($cache, 'Cache_Lite')) {
            return $this->sendFile($cache, $row['title']);
        }

        $export = KBExport::factory('pdf');
        $export->setConfig($config);
        $export->setSettings($settings);
        $export->createTempDir();

        $data = $export->exportEntry($row, $this->msg); 
        $export->removeTempDir();
        $cache->save($data);
        
        $this->sendFile($data, $row['title']);
    }
    
    
    function getEntryWkHtmlToPdf($manager) {
        
        $row = $manager->getEntryById($this->entry_id, $this->category_id);
        $row = $this->stripVars($row);
        
        if(empty($row)) {
            return;
        }        
        
        $row['full_path'] = $manager->getCategorySelectRangeFolow();
        $row['custom'][$this->entry_id] = $manager->getCustomDataByEntryId($this->entry_id);
        
        if(DocumentParser::isLink($row['body'])) {
            $related = $manager->getEntryRelatedInline($this->entry_id);
            DocumentParser::parseLink($row['body'], array($this, 'getLink'), $manager, 
                                      $related, $row['id'], $this->controller);
        }
        
        $config = $this->getWkHtmlToPdfConfig($manager->setting);
        $config['title'] = $row['title'];
        
        $pdfs = $config['settings'];
        $pdfs['print_entry_info'] = $config['print_entry_info'];
        $cache = $this->getCache($row, $pdfs);
        if($cache && !is_a($cache, 'Cache_Lite')) {
            return $this->sendFile($cache, $row['title']);
        }
        
        $export = KBExport2::factory('pdf');
        $export->setComponents($manager, $this->controller, $this);
        $export->setConfig($config);
        $export->createTempDirs();

        $data = $export->exportEntry(array($row));
        $export->removeTempDir();
        $cache->save($data);
        
        $this->sendFile($data, $row['title']);
    }

	
	function getWkHtmlToPdfConfig($setting) {
		
        $config = array(
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'temp_dir' => KBExport2::getTempDir(APP_CACHE_DIR, 'pdf'),
            'tool_path' => $setting['plugin_wkhtmltopdf_path'],
            'http_host' => $_SERVER['HTTP_HOST'],
            'print_entry_info' => $setting['show_pdf_link_entry_info'],
            'title' => '',
            'settings' => array(
				'orientation' => 'portrait',
                'fontsize' => $setting['htmldoc_fontsize'],
                'font' => $setting['htmldoc_bodyfont'],
				'dpi' => $setting['plugin_wkhtmltopdf_dpi'],
				'margin_top' => $setting['plugin_wkhtmltopdf_margin_top'],
				'margin_bottom' => $setting['plugin_wkhtmltopdf_margin_bottom']
			)
        );
		
        $keys = array('header', 'footer');
        foreach ($keys as $key) {
            $param = 'plugin_export_' . $key;
            if ($setting[$param]) {
                $config['settings'][$key] = $setting[sprintf('plugin_export_%s_tmpl', $key)];
            }
        }
		
		return $config;
	}


    function &_getEntryBody($manager, $row = false) {
        
        if (!$row) {
            $row = $manager->getEntryById($this->entry_id, $this->category_id);
            $row = $this->stripVars($row);
            
            if(empty($row)) { return; }
        }
        
        $full_path = &$manager->getCategorySelectRangeFolow();
        $full_path = $full_path[$row['category_id']];
        
        if(DocumentParser::isTemplate($row['body'])) {
            DocumentParser::parseTemplate($row['body'], array($manager, 'getTemplate'));
        }        
        
        if(DocumentParser::isLink($row['body'])) {
            $related = $manager->getEntryRelatedInline($this->entry_id);
            DocumentParser::parseLink($row['body'], array($this, 'getLink'), $manager, 
                                      $related, $row['id'], $this->controller);
        }    
        
        if(DocumentParser::isCode($row['body'])) {
            DocumentParser::parseCodePrint($row['body']);    
        }
        
        DocumentParser::parseCurlyBraces($row['body']);
        
        // custom    
        $rows =  $manager->getCustomDataByEntryId($this->entry_id);
        $custom_data = $this->getCustomData($rows);

        $row['custom_tmpl_top'] = $this->parseCustomData($custom_data[1], 1);
        $row['custom_tmpl_bottom'] = $this->parseCustomData($custom_data[2], 2);                


        $row['body'] = $this->controller->_replaceArgSeparator($row['body']);
        
        $row['category_title_full'] = $full_path;
        $row['formated_date'] = $this->getFormatedDate($row['date_updated']);
        
        if(AppPlugin::isPlugin('history')) {
            $row['revision'] = $manager->getRevisionNum($this->entry_id);
            $tpl->tplSetNeeded('/revision');    
        }

        $link = $this->controller->getLink('entry', $this->category_id, $this->entry_id);
        $row['entry_link'] = $this->controller->_replaceArgSeparator($link);
        
        $updater = $manager->getUserInfo($row['updater_id']);
        $row['updater'] = $updater['first_name'] . ' ' . $updater['last_name'];
        
        return $row;
    }
    
    // rewrite 
    function getCustomDataCheckboxValue() {
        return 'image';
    }
    
    
    // cache id depends on ['id', 'title', 'body'];
    // if someting changed new cache will be generated, 
    // old one will be removed by cron in 30 days, so maximim lfe is 30 days
    // self::$cache_lifetime set to null by defult never expiered 
    function getCache($row, $pdf_settings = []) {
        
        require_once 'Cache/Lite.php';
        
        $cache = new Cache_Lite();
        $cache->setOption('caching', self::$cache);// it will not write if false
        $cache->setOption('cacheDir', APP_CACHE_DIR);
        $cache->setOption('lifeTime', self::$cache_lifetime);

        $keys = ['id', 'title', 'body'];
        $cache_id = md5(implode('', array_intersect_key($row, array_flip($keys))));
        $cache_id = md5($cache_id . implode('', $pdf_settings));
        $cache_gr = 'pdf';

        $data = $cache->get($cache_id, $cache_gr);
        if($data !== false) {
            return $data;
        }
        
        return $cache;
    }
    
    
    function sendFile($data, $title) {
        $params = [
            'data' => $data,
            'gzip' => false,
            'contenttype' => 'application/pdf'
        ];
        
        WebUtil::sendFile($params, $title . '.pdf', false);
    }
        
}
?>