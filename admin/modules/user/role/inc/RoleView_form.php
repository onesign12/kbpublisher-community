<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KnowledgebasePublisher package                   |
// | KnowledgebasePublisher - web based knowledgebase publishing tool          |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+

class RoleView_form extends AppView
{
    
    var $tmpl = 'form.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        
        $rows = $manager->getSelectRecords();
        $range = &$manager->getSelectRange($rows);
        $js_values =& $manager->getSortJsArray($rows);
        
        foreach($js_values as $k => $v) {
            $a['js_option_list_values'] = $v;
            
            $key = ($obj->get('id')) ? $obj->get('sort_order') : 'sort_end';
            $a['js_option_default'] = sprintf("'%s', '%s'", $k, $key);
            $tpl->tplParse($a, 'js_option_list');
        }        
        
        // category
        $select = new FormSelect();
        $select->setFormMethod($_POST);
        $select->setSelectWidth(250);
        $select->select_tag = false;
        
        // set disabled for self and all childs
        if($obj->get('id')) { 
            $select->setOptionParam($obj->get('id'), 'disabled');
            foreach($manager->getChilds($rows, $obj->get('id')) as $v) {
                $select->setOptionParam($v, 'disabled');
            }
        }
        
        $select->setMultiple(5, false);
        $select->setSelectName('parent_id');
        $select->setRange($range, array(0=>$this->msg['top_level_msg']));
        $tpl->tplAssign('role_select', $select->select($obj->get('parent_id')));
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($this->setRefererFormVars(@$_GET['referer']));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        // popup
        if($this->controller->getMoreParam('popup')) {
            $tpl->tplAssign('action_title', $this->msg['add_new_role_msg']);
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }    
}
?>