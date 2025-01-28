<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KnowledgebasePublisher package                   |
// | KnowledgebasePublisher - web based knowledgebase publishing tool          |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class AppEditor
{



    function __construct($options = array()) {

    }
    
    
    static function getEditor($editor, $value, $options) {
        if($editor == 'ckeditor4') {
            return self::ckeditor4($value, $options['cfile'], $options['fname'], $options['cconfig']);
            
        } elseif($editor == 'ckeditor5') {
            return self::ckeditor5($value, $options['cfile'], $options['fname'], $options['cconfig']);
            
        } elseif($editor == 'ckeditor5_md') {
            return self::ckeditorMd($value, $options['cfile'], $options['fname'], $options['cconfig']);
        }
        
        
    }
    
    
    static function ckeditor4($value, $cfile, $fname = 'body', $cconfig = array()) {

        require_once APP_ADMIN_DIR . 'tools/ckeditor_custom/ckeditor.php';

        $config_file = array(
          'news' => 'ckconfig_news.js',
          'article' => 'ckconfig_article.js',
          'glossary' => 'ckconfig_glossary.js',
          'custom_field' => 'ckconfig_custom_field.js',
          'export' => 'ckconfig_export.js'
        );

        $CKEditor = new CKEditor();
        $CKEditor->returnOutput = true;
        $CKEditor->basePath = APP_ADMIN_PATH . 'tools/ckeditor/';

        $config = array();
        $config['customConfig'] = APP_ADMIN_PATH . 'tools/ckeditor_custom/' . $config_file[$cfile];

        foreach($cconfig as $k => $v) {
            $config[$k] = $v;
        }

        $events = array();
        // $events['instanceReady'] = 'function (ev) {
        //     alert("Loaded: " + ev.editor.name);
        // }';

        return $CKEditor->editor($fname, $value, $config, $events);
    }

    
    static function ckeditor5($value, $cfile, $fname = 'body', $cconfig = array()) {

        $config_file = array(
          // 'news' => 'ckconfig_news.js',
          'news' => 'ckconfig_article.js',
          'article' => 'ckconfig_article.js',
          'glossary' => 'ckconfig_article.js',
          // 'glossary' => 'ckconfig_glossary.js',
          'custom_field' => 'ckconfig_custom_field.js',
          'export' => 'ckconfig_export.js'
        );

        $config = $config_file[$cfile];
        
        $str = file_get_contents(APP_ADMIN_DIR . 'tools/ckeditor5_custom/load.html');
        $str = sprintf($str, $fname, $value, $config);
        
        // echo '<pre>' . print_r($str, 1) . '</pre>';
        // exit;
        
        return $str;
    }
    
    
    static function ckeditorMd($value, $cfile, $fname = 'body', $cconfig = array()) {
        
        $str = file_get_contents('./template/editor_markdown.html');
        $str = sprintf($str, $fname, $value);
        // echo '<pre>' . print_r($str, 1) . '</pre>';
        // exit;
        
        return $str;
    }
}
?>