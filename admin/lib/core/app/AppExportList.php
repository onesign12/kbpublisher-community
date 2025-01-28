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


class AppExportList extends AppExport
{
    
    function __construct($vars = array()) {
        
        $reg = &Registry::instance();
        $options = [
            'type' => 'xls',
            'excel_delim' => $reg->getEntry('conf')['lang']['excel_delim']
        ];
        
        if($vars) {
            $options['type'] = $vars['type'];
            $options['fparams'] = $vars;
        }
        
        $this->setParams($options);
    }
    
        
    function getData($obj, $manager, $view) {
        
        $export_columns_key = $view->controller->getMoreParam('page');
        $columns = $this->getColumns($export_columns_key, $view);
        $columns = array_flip($columns['disp']);
        
        // $data = $this->getRecords($obj, $manager, $view);
        $data = $view->getExportRecords($obj, $manager);
        foreach(array_keys($data) as $k) {
            $data[$k] = array_intersect_key($data[$k], $columns);
            $data[$k] = array_merge($columns, $data[$k]);
        }
        
        switch($this->type) {
            case 'xml':
                
                $html_keys = ['body'];
                foreach (array_keys($data) as $id) {
                    $data[$id]['@attributes'] = ['id' => $id];
                    foreach($html_keys as $v) {
                        if (isset($data[$id][$v])) {
                            $data[$id][$v] = ['@cdata' => $data[$id][$v]];
                        }
                    }
                }
                
                // $data = array_values($data); // reset keys
                $xml = Array2XML::createXML('entries', ['entry' => $data]);
                $data = $xml->saveXML();
                
                break;

            case 'csv':
            case 'xls':
        
                // with header option
                if (!empty($this->csv_params['hr'])) {
                    $titles[0] = $this->getTitles($data, $view);
                    $data = $titles + $data; 
                }
        
                $data = RequestDataUtil::parseCsv($data, $this->csv_params);
                break;
        }
        
        return $data;
    }
    
    
    function export($obj, $manager, $view) {
        $data = $this->getData($obj, $manager, $view);
        $this->sendFile($data, $this->filename);
        exit;
    }
    
    
    function getTitles($data, $view) {
        $list = new ListBuilderView_customize_export;
        $titles = array_keys($data[array_key_first($data)]);
        foreach($titles as $k => $v) {
            $titles[$k] = $list->getFieldTitleKey($v, $view);
        }
        
        return $titles;
    }

    
    function getColumns($column_key, $view) {
        $list = new ListBuilderView_customize_export;
        return $list->getColumns($column_key, $view);
    }

}
?>