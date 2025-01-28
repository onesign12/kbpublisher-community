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

class EmailBoxView_form extends AppView
{
    
    var $tmpl = 'form.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('trigger_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $options = $obj->get('data_string');
        if ($options) {
            $tpl->tplAssign('ssl_checked', (!empty($options['ssl'])) ? 'checked' : '');
            $tpl->tplAssign($options);
            
        } else {
            $tpl->tplAssign('mailbox', 'INBOX');
            $tpl->tplAssign('max_count', 20);
        }
        
        if($this->controller->getMoreParam('popup')) {
            $tpl->tplSetNeeded('/close_button');
            
        } else {
            $tpl->tplSetNeeded('/cancel_button');
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }    
}
?>