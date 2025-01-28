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


class SettingViewAllowedDirectory_popup extends SettingViewExtraItems_popup
{

    var $setting_module_id = 1;
    var $tmpl = 'allowed_directory.html';
    

    function execute(&$obj, &$manager, $extra_options = array()) {

        $this->addMsg('common_msg.ini', 'file');

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

            $v['delete_msg'] = $this->msg['delete_msg'];
            $v['title'] = trim($items[$i]);            $v['line'] = $i;

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

        $tpl->tplParse($this->msg);

        return $tpl->tplPrint(1);
    }
    
    
    function ajaxAddRule($data) {
        $objResponse = new xajaxResponse();
        
        $setting_key = $this->controller->getMoreParam('popup');
        
        $items = $this->manager->getSettings($this->setting_module_id, $setting_key);
        $items_split = ($items) ? explode('||', $items) : array();
        
        if (!empty($items)) {
            $items .= '||';
        }
        
        $data = str_replace('|', '&#124;', $data);
        $item = implode('|', $data);
        $items .= $item;
        
        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($items)));
        
        // get html to insert
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $line_num = count($items_split);
        
        $v = array(
            'line' => $line_num,
            'title' => $data['title'],
            'delete_msg' => $this->msg['delete_msg']
        );

        $tpl->tplParse($v, 'rule');
        $html = $tpl->parsed['rule'];

        $objResponse->call('SettingPopupList.showAddedRule', $html, $line_num);

        return $objResponse;
    }
    
    
    function ajaxGetSortableList() {
        $tpl = new tplTemplatez($this->template_dir . 'extra_items_sortable.html');

        $popup = $this->controller->getMoreParam('popup');

        $items = $this->obj->get($popup);
        $items = explode('||', $items);

        // current rules
        for ($i = 0; $i < count($items); $i ++) {
            $v = $this->msg;

            $v['title'] = trim($items[$i]);
            $v['line'] = $i;

            $tpl->tplParse($v, 'rule');
        }

        $tpl->tplParse($this->msg);


        $objResponse = new xajaxResponse();

        $objResponse->script("$('.bb_popup').remove();");
        $objResponse->addAssign('extra_list', 'innerHTML', $tpl->tplPrint(1));
        $objResponse->call('initSort');

        return $objResponse;
    }

}
?>