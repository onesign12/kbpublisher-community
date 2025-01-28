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


$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

$obj = new KBPReport;

$action = new KBPReportAction($rq, $rp);

$manager =& $obj->setManager(new KBPReportModel());

if(isset($rp->submit)) {
    $manager->checkPriv($priv, 'update');
    
    $action->runTest($manager);
    $controller->go();
    
} else {
    $manager->checkPriv($priv, 'select');
    
    $data = $manager->getReport();
    $view = $controller->getView($obj, $manager, 'KBPReportView_default', $data); 
}

?>