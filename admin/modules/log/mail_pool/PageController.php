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

$obj = new MailPoolLog;

$manager =& $obj->setManager(new MailPoolLogModel());
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {    

case 'status': // ------------------------------

    $rq->status = 2; // set as not sent, no try to send anymore
    $manager->status($rq->status, $rq->id, 'status');
    $controller->go();

    break;


case 'detail': // --------------------------------
     
    $data = $manager->getById($rq->id);
    $data['message'] = unserialize($data['message']);
    
    $rp->setHtmlValues('Body'); 
    $rp->stripVarsValues($data);
    $obj->set($data);    
    
    if(is_array($data['message'])) {
        
        $body = $data['message']['Body'];
        if($data['message']['ContentType'] == 'text/plain') {
            $body = nl2br($body);
        }
        
        $obj->set('message_email', $body);
        
        // move body to bottom in array
        $b = $data['message']['Body'];
        unset($data['message']['Body']);
        $data['message']['Body'] = $b;
        
        $obj->set('mail_object', print_r($data['message'], 1));    
    }
    
    
    $view = $controller->getView($obj, $manager, 'MailPoolLogView_form');

    break; 


default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'MailPoolLogView_list');
}
?>