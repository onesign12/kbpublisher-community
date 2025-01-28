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


class KBClientAction_account extends KBClientAction_common
{

    function &execute($controller, $manager) {
        
        // check if registered
        if(!$manager->is_registered) {
            $controller->go('login', false, false, 'account');
        }
        
        
        // redirection to admin url map
        $rmap = [
            'password' => ['page' => 'account_security', 'action' => 'password'],
            'security' => ['page' => 'account_security']
        ];
        
        if($this->msg_id && isset($rmap[$this->msg_id])) {
            $more = $rmap[$this->msg_id];
            $link = $controller->getRedirectLink('account', false, false, false, $more);
            $controller->goUrl($link);
        }
        
        $view = &$controller->getView();
        
        // will caal page_popup_client from admin templates
        // just not to call admin href in url for popups
        if(isset($_GET['popup'])) {
            $view->page_popup = true; 
        }
        
        return $view;
    }    
}
?>