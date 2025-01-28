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


class TagView_delete extends AppView
{
    
    var $template = 'form_delete.html';
    
    function execute(&$obj, &$manager, $related) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
		
		$msg = array('delete_msg' => $this->msg['delete_msg']);
        $tpl->tplAssign('error_msg', AppMsg::afterActionBox('delete_tag', 'error', false, $msg));
        
        // attached to entries
        $entry_type_msg = AppMsg::getMsg('ranges_msg.ini', false, 'record_type');
        
        $str = '<a href=\'%s\'>%s (%d)</a>';
        $refernces = array();
        
        foreach ($related[$obj->get('id')] as $entry_type => $entry_num) {
            
            $more = array('filter[q]'=>'tag:'.$obj->get('title'));
            $url_params = $manager->entry_type_to_url[$entry_type];
            $link = $this->getLink($url_params[0], $url_params[1], false, false, $more);
            
            $msg_key = $manager->record_type[$entry_type];
            $refernces[] = sprintf($str, $link, $entry_type_msg[$msg_key], $entry_num);  
        }
        
        $tpl->tplAssign('references_str', implode(' | ', $refernces));
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>