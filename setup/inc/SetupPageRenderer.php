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

class SetupPageRenderer extends PageRenderer
{


    function setObjects(&$view, &$controller, &$manager) {
        $this->view = &$view;
        $this->manager = &$manager;
        $this->controller = &$controller;
    }
    
    
     function render() {
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        $tpl->strip_vars = true;
        $tpl->strip_double = true;
        
        $content = &$this->view->execute($this->manager);
        $content = &$tpl->tplParseString($content, $this->vars);
        $tpl->tplAssign('content', $content);
        
        
        $next = $this->controller->getNextStep();
        if($next) {
            $tpl->tplSetNeeded('/next_link');
            $tpl->tplAssign('next_page', $this->controller->getLink($next));
        }
        
        $prev = $this->controller->getPrevStep();
        if($prev && $this->view->back_button) {
            $tpl->tplSetNeeded('/prev_link');
            $prev = ($this->view->back_button_minus) ? $prev - $this->view->back_button_minus : $prev;
            $tpl->tplAssign('prev_page', $this->controller->getLink($prev));
        }
        
        if($this->view->refresh_button) {
            $tpl->tplSetNeeded('/refresh_link');
        }
        
        if($this->view->custom_button) {
            $tpl->tplSetNeeded('/custom_link');
            $tpl->tplAssign($this->view->custom_values);
        }
        
        $tpl->tplAssign('current_page', $this->controller->getLink($this->view->view_id));
        
        
        if($this->view->cancel_button) {
            $tpl->tplSetNeeded('/cancel_link');
            $tpl->tplAssign('cancel_page', $this->controller->getLink(1));
        }        
        
        
        $button_state = ($this->view->passed) ? 'false' : 'true';
        $tpl->tplAssign('button_state', $button_state);
        
        $tpl->tplAssign($this->vars);
        $tpl->tplAssign($this->view->msg);
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }
}
?>