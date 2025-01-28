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



class StuffEntryView_form extends AppView
{
    
    var $template = 'form.html';
    
    function execute(&$obj, &$manager) {
                                          
        $this->addMsg('user_msg.ini');
        $this->addMsgPrepend('common_msg.ini', 'file');
                
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
                             
                             
        $select = new FormSelect();
        $select->select_tag = false;
        $select->setRange($manager->getCategories(), array(0 => '---'));
        
        $tpl->tplAssign('category_select', $select->select($obj->get('category_id')));
        
        
        // status
        $cur_status = ($this->controller->action == 'update') ? $obj->get('active') : false;
        $range = ListValueModel::getListSelectRange('file_status', true, $cur_status);
        $range = $this->getStatusFormRange($range, $cur_status);
        $status_range = $range;

        $select->resetOptionParam();
        $select->setRange($range);            
        $tpl->tplAssign('status_select', $select->select($obj->get('active')));        
            
        
        $tpl->tplAssign('file_size_max', WebUtil::getFileSize($manager->setting['file_max_filesize']*1024));
        $tpl->tplAssign('file_link', $this->getActionLink('file', $obj->get('id')));
        
        
        // info
        if($this->controller->action == 'update') {
            // CommonEntryView::parseInfoBlock($tpl, $obj, $this);
        } else {
            $this->msg['file_help_msg'] = '';
        }
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($this->setRefererFormVars(@$_GET['referer'], array('files', $obj->get('category_id'))));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
}
?>