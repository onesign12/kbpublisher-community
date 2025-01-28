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


$obj = new CronLog;

$manager =& $obj->setManager(new CronLogModel());

$priv->setCustomAction('magic', 'select');
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'detail': // --------------------------------
     
    $data = $manager->getById($rq->id);
    $obj->set($data);
    $view = $controller->getView($obj, $manager, 'CronLogView_form', $data);

    break;    


default: // ------------------------------------
    
    if(isset($rq->filter)) {
        $view = $controller->getView($obj, $manager, 'CronLogView_list');
    } else {
        $view = $controller->getView($obj, $manager, 'CronLogView_list_group');
    }
    
}
?>