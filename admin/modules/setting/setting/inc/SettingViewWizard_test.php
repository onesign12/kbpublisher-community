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


class SettingViewWizard_test extends AppView
{
    
    var $tmpl = 'form_wizard_test.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('start_wizard_msg.ini');
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $view = new KBPReportView_default;
        $report_manager = new KBPReportModel;
        
        $rq = array();
        $rp = array();
        $report_action = new KBPReportAction($rq, $rp);
        
        $report_action->runTest($report_manager);
        $data = $report_manager->getReport();
        $tpl->tplAssign('report', $view->execute($obj, $report_manager, $data));
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }
	
}
?>