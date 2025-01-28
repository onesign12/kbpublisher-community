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


class SettingViewPluginExportTest_popup extends SettingView_form
{
    
    function execute(&$obj, &$manager, $extra_options = array()) {
        
        $this->addMsg('common_msg.ini', 'export_setting');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        $this->addMsg('client_msg.ini', 'public');
        
        if ($extra_options['check_setting'] && $obj->get('plugin_wkhtmltopdf_path') == 'off') {
            die('WKHTMLTOPDF is not installed! Test is disabled.');
        }
        
        $config = array(
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'temp_dir' => KBExport2::getTempDir(APP_CACHE_DIR, 'pdf'),
            'http_host' => $_SERVER['HTTP_HOST'],
            'title' => 'Test',
            'settings' => array(
                'orientation' => 'Portrait',
                'fontsize' => $obj->get('htmldoc_fontsize'),
                'font' => $obj->get('htmldoc_bodyfont'),
                'dpi' => $obj->get('plugin_wkhtmltopdf_dpi'),
				'margin_top' => $obj->get('plugin_wkhtmltopdf_margin_top'),
				'margin_bottom' => $obj->get('plugin_wkhtmltopdf_margin_bottom')
            ),
            'placeholders' => array(
                'top_category_title' => 'Test Category',
                'top_category_description' => 'Test Category Description',
                'export_title' => 'Export Title',
                'article_title' => 'Article Title',
                'category_title' => 'Test Category',
                'category_description' => 'Test Category Description'
            )
        );
        
        $config = array_merge_recursive($config, $extra_options['config']);
		// echo '<pre>', print_r($obj->get(), 1), '<pre>'; exit;
        
        $setting['view_format'] = 'default';
        $setting['private_policy'] = 1;

        $controller2 = new KBClientController();
        $controller2->setDirVars($setting);
        $controller2->setModRewrite(false);
        
        $export = KBExport2::factory('pdf');
        $export->setComponents($manager, $controller2, $this);
        $export->setConfig($config);
        $export->createTempDirs();
        
        $row = array(
            'id' => 1,
            'updater' => 'Test User',
            'title' => 'Test Title',
            'body' => FileUtil::read(APP_PLUGIN_DIR . 'export/export2/template/preview.html'),
            'custom_tmpl_top' => '',
            'custom_tmpl_bottom' => '',
            'category_title_full' => 'Test Category',
            'revision' => 1,
            'formated_date' => $this->getFormatedDate(time()),
            'entry_link' => $controller2->getLink('entry', 1, 1),
            'full_path' => '',
            'category_title_full' => 'Test Category',
            'date_updated' => time(),
            'revision' => 1,
            'custom' => array(
                1 => array()
            )
        );
        
        $data = $export->exportEntry(array($row));
        $export->removeTempDir();
        
        $params['data'] = $data;
        $params['gzip'] = false;
        $params['contenttype'] = 'application/pdf';

        WebUtil::sendFile($params, 'export.pdf', false);
    }
}
?>