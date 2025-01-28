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
//$rp->setHtmlValues('body'); // to skip $_GET['body'] not strip html

$obj = new SupportRequest;

$manager =& $obj->setManager(new SupportRequestModel());
//$manager->checkPriv($priv, $controller->action, @$rq->id);


    $data = array();
    
    if(isset($rp->submit)) {
        
        $is_error = $obj->validate($rp->vars);
        
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
            $data = &$rp->vars;
        
        } else {
            $rp->stripVars('stripslashes');
            $sent = $manager->sendSuppoprtRequest($rp->vars);

            if(!$sent) {
                $rp->stripVars(true);
                $obj->set($rp->vars);    
                $obj->setError('email_not_sent');
                $data = &$rp->vars;
            
            } else {
                $controller->go();                
            }
        }
    
    } else {
        $data = $manager->getData();
        $data = $rp->stripVarsValues($data);
        $obj->set($data);    
    }
    
    $view = $controller->getView($obj, $manager, 'SupportRequestView_form', $data);

?>