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


class AppExport
{

    var $types = array(
        'xml' => array('ctype' => 'application/xml', 'ext' => 'xml'),
        'csv' => array('ctype' => 'application/csv', 'ext' => 'csv'),
        'xls' => array('ctype' => 'application/xls', 'ext' => 'xls')
    );
    
    var $contenttype;
    var $extension;
    var $type;
    var $csv_params;


    function __construct($options = array()) {
        if($options) {
            $this->setParams($options);
        }
    }
    

    function setParams($options) {
        
        $this->type = $type = $options['type'];
        $fparams = $options['fparams'];
        
        $this->contenttype = $this->types[$type]['ctype'];
        $this->extension = $this->types[$type]['ext'];
        
        if($type == 'csv') {
            $this->csv_params = array(
                'ft' => $fparams->fields_terminated,
                'oe' => $fparams->optionally_enclosed,
                'lt' => $fparams->lines_terminated,
                'hr' => (isset($fparams->header_row)),
                'tr' => (isset($fparams->total_row))
            );
            
        } elseif($type == 'xls') {
            // $this->xls_params = array(
            $this->csv_params = array(
                'ft' => $options['excel_delim'],
                'oe' => '"',
                'lt' => "\r\n",
                'hr' => true,
                'tr' => true
            );
        }
    }
    
    
    function getData($obj, $manager, $view) {
        switch($this->type) {
            case 'xml':
                $data = &$this->getXml($obj, $manager, $view);
                break;

            case 'csv':
            case 'xls':
                $data = &$this->getCsv($obj, $manager, $view, $this->csv_params);
                break;

            // case 'csv':
            //     $data = &$this->getCsv($obj, $manager, $view, $this->csv_params);
            //     break;
            // 
            // case 'xls':
            //     $data = &$this->getCsv($obj, $manager, $view, $this->xls_params);
            //     break;
        }
        
        return $data;
    }
    
    
    function sendFile($data, $filename) {
        $filename = sprintf('%s.%s', $filename, $this->extension);
        $params = array(
            'data' => $data,
            'contenttype' => $this->contenttype,
            // 'gzip' => false
        );
        
        WebUtil::sendFile($params, $filename);
    }
}


interface iAppExport
{
    public function getXml($obj, $manager, $view);
    public function getCsv($obj, $manager, $view, $params);
}
?>