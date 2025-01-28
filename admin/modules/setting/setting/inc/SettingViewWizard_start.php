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


class SettingViewWizard_start extends AppView
{
    
    var $tmpl = 'form_wizard_start.html';
    
    
    function execute(&$obj, &$manager) {
    	
		$this->addMsg('start_wizard_msg.ini');
		
		$tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
		$desc = ($this->controller->getMoreParam('done')) ? $this->msg['wizard_completed_msg'] : $this->msg['group_admin']['desc'];
        $tpl->tplAssign('desc', $desc);
		
		$link = $this->getLink('this', 'this', false, false, array('group' => 'admin'));
		$tpl->tplAssign('link', $link);
		
		$button_title = ($this->controller->getMoreParam('done')) ? $this->msg['start_again_msg'] : $this->msg['start_wizard_msg'];
		$tpl->tplAssign('button_title', $button_title);
		
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }
	
}
?>