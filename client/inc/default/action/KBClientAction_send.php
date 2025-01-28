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

class KBClientAction_send extends KBClientAction_common
{

    function &execute($controller, $manager) {
        
        // not allowed
        if(!$manager->getSetting('show_send_link')) {
            $controller->go();
        }
        
        // need to login
        if(!$manager->is_registered && $manager->getSetting('show_send_link') == 2) { 
            $controller->go('login', $this->category_id, $this->entry_id, 'send');
        }
        
        
        $view = &$controller->getView();
        
        if(isset($this->rp->submit)) {
            
            $errors = $view->validate($this->rp->vars, $manager);    
    
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);
            
            } else {
                
                $this->rp->setHtmlValues('entry_link');
                $this->rp->stripVars('stripslashes');
                
                $article = array();
                if($manager->getSetting('show_send_link_article') && isset($this->rp->attach_article)) {
                    
                    $view2 = &$controller->getView('print');
                    $page = new KBClientPageRenderer($view2, $manager);
                    $article['body'] = $page->renderPrint(true); // true for base href
                    
                    $filename = $controller->getUrlTitle($manager->getEntryTitle($this->entry_id)) . '.html';
                    $article['filename'] = $filename;
                }
                
                
                $friend_email = explode(',', $this->rp->vars['friend_email']);
                $friend_email = array_unique($friend_email);
                
                // strip more than $allowed
                $allowed = 5;                
                if(count($friend_email) > $allowed) {
                    $friend_email = array_slice($friend_email, 0, $allowed);
                }
                
                $this->rp->vars['friend_email'] = implode(',', $friend_email);
                                
                $sent = $manager->sendToFriend($this->rp->vars, $article);
                
                if($sent) {
                    $controller->go('success_go', $this->category_id, $this->entry_id, 'friend_sent');
                
                } else {
                    $this->rp->stripVars(true);
                    $view->setFormData($this->rp->vars);
                    $view->msg_id = 'entry_not_sent';
                }
            }
        }
            
        return $view;
    }
       
}
?>