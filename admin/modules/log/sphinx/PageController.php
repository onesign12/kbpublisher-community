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
       

$rq = new RequestData($_GET);
$rp = new RequestData($_POST);


$obj = new SphinxLog;

$manager =& $obj->setManager(new SphinxLogModel());
$priv->setCustomAction('file', 'select');
$priv->setCustomAction('export', 'select');
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'detail': // --------------------------------
     
    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);
    
    $view = $controller->getView($obj, $manager, 'SphinxLogView_form');

    break;
    
    
default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'SphinxLogView_list');
    
}

?>