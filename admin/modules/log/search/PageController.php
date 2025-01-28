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


$obj = new SearchLog;

$manager =& $obj->setManager(new SearchLogModel());
$priv->setCustomAction('file', 'select'); 
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'file': // ------------------------------

    

    $view = new SearchLogView_list;

    $options = array(
        'type' => $rp->type,
        'excel_delim' => $view->conf['lang']['excel_delim'],
        'fparams' => $rp
    );

    $export = new SearchLogExport($options);

    $data = $export->getData($obj, $manager, $view);
    $filename = sprintf('report_%s_%s', $view->start_day, $view->end_day);

    $export->sendFile($data, $filename);
    exit;
          
    break;

case 'detail': // --------------------------------
     
    $data = $manager->getById($rq->id); 
    $data['search_option'] = unserialize($data['search_option']);

    $rp->stripVarsValues($data);
    $obj->set($data);
    $obj->set('user_ip', $data['user_ip_formatted']);
    
    $view = $controller->getView($obj, $manager, 'SearchLogView_form', $data);

    break;
       
default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'SearchLogView_list');
    
}

?>