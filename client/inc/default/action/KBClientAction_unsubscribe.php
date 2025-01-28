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


class KBClientAction_unsubscribe extends KBClientAction_common
{

    function &execute($controller, $manager) {    
    
        // not implemented yet
        // if(!$manager->getSetting('unsubscribe_policy')) {
            // $controller->go();
        // }    nsusbscription_wrong');
        
        $view = &$controller->getView('unsubscribe');
        
        $values = $this->rq->vars;
        if(empty($values['et'])) {
             $values['et'] = 'all';
        }
        
        $view->sub_type = $values['et'];
 
        if(isset($this->rp->submit)) {
            
            if(!isset($values['ec'])) {
                $controller->go();
            }
            
            $type = $this->validateType($values, $manager);
            if(!$type) {
                $controller->go('unsubscribe', false, false, 'unsusbscription_error');
            }
            
            $user = $this->validateUser($values, $manager);
            if(!$user) {
                $controller->go('unsubscribe', false, false, 'unsusbscription_error');
            }
            
            $s = new SubscriptionModel();
            if($type == 'all') {
                $s->unsubscribeByUserId($user['id']); // all per user
            } else {
                $s->unsubscribeByEntryType($user['id'], $type);
            }

            $controller->go('unsubscribe', false, false, 'unsusbscription_success');   
        }
        
        return $view;
    }
    
    
    function validateUser($values, $manager) {
        
        $user = array();
        if(!empty($values['ec'])) {
            $code = addslashes(stripslashes($values['ec']));
            $user = $manager->isUser($code);
        }

        return $user;
    }


    function validateType($values, $manager) {

        $sub_type = false;
        if(!empty($values['et'])) {
            
            if($values['et'] == 'all') {
                $sub_type = 'all';
                
            } elseif($values['et'] == 'entry') {
                $sub_type = '1,11,2,12'; //all article and files 
            
            } elseif($values['et'] == 'news') {
                $sub_type = 3;
            
            } elseif($values['et'] == 'comment') {
                $sub_type = 31;
            }
        }

        return $sub_type;
    }
    
}
?>