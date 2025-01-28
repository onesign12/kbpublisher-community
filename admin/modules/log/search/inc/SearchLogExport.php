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


class SearchLogExport extends AppExport implements iAppExport
{
    
    function &getXml($obj, $manager, $view) {
        
        $view->addMsg('report_msg.ini');
        $view->addMsg('log_msg.ini');
        
        $tpl = new tplTemplatez($view->template_dir . 'export_xml.html');
        $tpl->strip_vars = true;
                                    
        // filter sql
        $params = $view->getFilterSql($manager);
        $manager->setSqlParams($params);
        
        $manager->setSqlParams('ORDER BY date_search');
          
        // get records
        $rows = RequestDataUtil::stripVarsXml($manager->getRecords());
        
        $search_type = $manager->getSearchTypeSelectRange();
        
        foreach ($rows as $row) {
            $row['search_type_formatted'] = $search_type[$row['search_type']];
            $tpl->tplParse($row, 'row');     
        }
        
        if (!$view->start_day) {
            $start_day = explode(' ', $rows[0]['date_search']);
            $view->start_day = $start_day[0];
        }
        
        if (!$view->end_day) {
            $last_index = count($rows) - 1;
            $end_day = explode(' ', $rows[$last_index]['date_search']);
            $view->end_day = $end_day[0];
        }
        

        $d = sprintf('%s - %s', $view->start_day, $view->end_day);
        $tpl->tplAssign('date_period', $d);
        
        $tpl->tplAssign('encoding', $view->encoding);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

     
    function &getCsv($obj, $manager, $view, $opts) {

        $view->addMsg('report_msg.ini');
        $view->addMsg('log_msg.ini');
        $view->addMsg('user_msg.ini');

        // filter sql
        $params = $view->getFilterSql($manager);
        $manager->setSqlParams($params);
        
        // get records
        $rows = $manager->getRecords();
        
        // all period
        if (!$view->start_day) {
            $start_day = explode(' ', $rows[0]['date_search']);
            $view->start_day = $start_day[0];
            
            $last_index = count($rows) - 1;
            $end_day = explode(' ', $rows[$last_index]['date_search']);
            $view->end_day = $end_day[0];
        }
        
        $search_type = $manager->getSearchTypeSelectRange();   


        $titles = array($view->msg['date_msg'],
                        $view->msg['user_id_msg'],
                        $view->msg['username_msg'],
                        $view->msg['user_ip_msg'],
                        $view->msg['type_msg'],
                        $view->msg['search_string_msg'],
                        $view->msg['exitcode_msg']);
        
        // with header option
        if (!empty($opts['hr'])) {
            $data[] = $titles;        
        }                  
                   
        foreach ($rows as $row) {
            $a = array($row['date_search']); 
            
            $a[] = $row['user_id'];
            $a[] = $row['username'];
            $a[] = $row['user_ip_formatted'];
            $a[] = $search_type[$row['search_type']];
            $a[] = $row['search_string'];
            $a[] = $row['exitcode'];
            
            $data[] = $a;
        }
        
        $data = RequestDataUtil::parseCsv($data, $opts);   
        return $data;
    }
}
?>