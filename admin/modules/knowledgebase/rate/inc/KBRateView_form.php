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

class KBRateView_form extends AppView
{
    
    var $tmpl = 'form.html';
    
    
    function execute(&$obj, &$manager, $data) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $tpl->tplAssign($data);
        
        
        if($this->controller->action == 'insert') {
            
        } else {
            if($obj->get('user_id')) {
                $tpl->tplSetNeeded('/if_registered');
            } else {
                $tpl->tplSetNeeded('/else_registered');
            }
        }
        
        
        // update article link
        $more = array('id'=>$obj->get('entry_id'), 
                      'referer'=>WebUtil::serialize_url($this->controller->getCommonLink()));
        $link = $this->controller->getLink('knowledgebase', 'kb_entry', false, 'update', $more);
        $tpl->tplAssign('entry_link_update', $link);
        
        
        // public article link
        $client_controller = &$this->controller->getClientController();
        $link = $client_controller->getLink('entry', false, $obj->get('entry_id'));
        $tpl->tplAssign('entry_link', $link);
        
        if($this->controller->getAction() == 'update') {
            $link = $this->getActionLink('delete', $obj->get('id'));
            $tpl->tplAssign('delete_link', $link);            
            $tpl->tplSetNeeded('/update');
        }
        
        
        // status
        $cur_status = ($this->controller->getAction() == 'update') ? $obj->get('active') : false;
        $range = $manager->getListSelectRange(true, $cur_status);         
        $range = $this->getStatusFormRange($range, $cur_status);

        $select = new FormSelect();
        $select->setSelectWidth(250);
        $select->setSelectName('active');
        $select->setRange($range);            
        $tpl->tplAssign('status_select', $select->select($obj->get('active')));
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));        
        
    
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>