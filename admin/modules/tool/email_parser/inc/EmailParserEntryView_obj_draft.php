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

require_once 'EmailParserEntryView_obj_article.php';


class EmailParserEntryView_obj_draft extends AppView
{
        
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('trigger_msg.ini');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        $view = new EmailParserEntryView_obj_article;
        $tpl = $view->_executeTpl($obj, $manager);
        
        $tpl->tplAssign('popup_title', $this->msg['draft_option_msg']);
        
        $vars = $this->setCommonFormVars($obj);
        $vars['title_required_class'] = $vars['required_class'];
        $vars['required_class'] = '';
        $tpl->tplAssign($vars);
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $more = array('item' => $this->controller->getMoreParam('item'));
        $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormDraft'));
        
        
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxValidateFormDraft($values, $options = array()) {
        
        
        $this->obj = new KBDraft;
        $objResponse = $this->ajaxValidateForm($values, $options);
        
        return $objResponse;
    }

}
?>