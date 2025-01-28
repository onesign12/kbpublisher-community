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



class SettingViewAgreeTerms_popup extends SettingViewExtraItems_popup
{
    
    var $setting_module_id = 2;
    var $tmpl = 'agree_terms.html';
    

    function execute(&$obj, &$manager, $extra_options = array()) {
        
        $this->addMsg('common_msg.ini', 'public_setting');

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $parser = &$manager->getParser();
        $setting_msg = $parser->getSettingMsg($manager->module_name);
        
        $popup = $this->controller->getMoreParam('popup');
        $tpl->tplAssign('setting_name', $popup);

        $tpl->tplAssign('popup_title', $setting_msg[$popup]['title']);
        
        $items = $obj->get($popup);
        $items = ($items) ? explode('||', $items) : array();
        
        $button['+'] = 'javascript:$(\'#new_rule\').show();void(0);';
        $button['...'] = array(array(
            'msg' => $this->msg['reorder_msg'],
            'link' => 'javascript:xajax_getSortableList();void(0);'
        ));
        
        $tpl->tplAssign('buttons', $this->getButtons($button));
        
        // current rules
        for ($i = 0; $i < count($items); $i ++) {
            $item = explode('|', trim($items[$i]));

            if (count($item) < 2) { // this item is broken
                continue;
            }

            $v = $this->msg;
            
            $v['title'] = trim($item[0]);
            $v['link'] = trim($item[1]);
            $v['line'] = $i;

            $tpl->tplParse($v, 'rule');
        }

        $msg = AppMsg::getErrorMsgs();
        $tpl->tplAssign('required_msg', $msg['required_msg']);

        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        $more_ajax = array('popup' => $popup);
        $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more_ajax));

        $xajax->registerFunction(array('deleteRule', $this, 'ajaxDeleteRule'));
        $xajax->registerFunction(array('addRule', $this, 'ajaxAddRule'));
        $xajax->registerFunction(array('saveOrder', $this, 'ajaxSaveOrder'));
        $xajax->registerFunction(array('updateItem', $this, 'ajaxUpdateItem'));
        $xajax->registerFunction(array('getSortableList', $this, 'ajaxGetSortableList'));

        $this->msg['agree_terms_ptext_msg'] =
            str_replace('{link}', 'http://domain.com/terms.html', $this->msg['agree_terms_ptext_msg']);

        $tpl->tplAssign('etext', $this->jsEscapeString($this->msg['agree_terms_ptext_msg']));
        $tpl->tplAssign('eerror', $this->jsEscapeString($this->msg['agree_terms_perror_msg']));

        $tpl->tplParse($this->msg);

        return $tpl->tplPrint(1);
    }

}
?>