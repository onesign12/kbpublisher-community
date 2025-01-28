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

$obj = '';
$manager = new AppModel();

$priv->setCustomAction('check_updates', 'select');
//$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'check_updates': // ------------------------------
    
    $view = $controller->getView($obj, $manager, 'KBHelpView_updates');
    break;
    
default: // ------------------------------------

    $view = $controller->getView($obj, $manager, 'KBHelpView_index');
}    
    

?>