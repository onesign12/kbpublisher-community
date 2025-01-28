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


class SettingViewSocialSites_detail_popup extends AppView
{
    
    function execute(&$obj, &$manager, $data) {
        $this->addMsg('error_msg.ini');
        $this->addMsg('client_msg.ini', 'public');
        $this->addMsg('common_msg.ini', 'public_setting');

        $tpl = new tplTemplatez($this->template_dir . 'form_social_custom_item.html');
        
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        if(!empty($_GET['saved'])) {
            $tpl->tplSetNeeded('/close_window');
        }
        
        $popup = $this->controller->getMoreParam('popup');
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $more_ajax = array('popup' => $popup, 'detail' => 1);
        
        $icon_display = 'none';
        
        $group_key = $this->controller->getMoreParam('group');
        if ($group_key) {
            $popup_title = $this->msg['detail_msg'];
            
            $line_num = $this->controller->getMoreParam('line');
            
            $items = $obj->get($popup);
            $items = unserialize($items);
            
            $item = $items[$group_key][$line_num];
            $item = RequestDataUtil::stripVars($item, array('icon'), true);
            $tpl->tplAssign($item);
            
            if ($item['icon']) {
                $icon_display = 'block';
                
                $tpl->tplAssign('icon', sprintf('data:image/svg+xml;base64,%s', base64_encode($item['icon'])));
            }
            
            $more_ajax['group'] = $group_key;
            $more_ajax['line'] = $line_num;
            
        } else {
            $popup_title = $this->msg['add_extra_item_msg'];
        }
        
        if (!empty($data)) {
            $tpl->tplAssign($data);
        }
        
        $tpl->tplAssign('icon_display', $icon_display);
        
        $tpl->tplAssign('popup_title', $popup_title);
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        
        $tpl->tplParse($this->msg);

        return $tpl->tplPrint(1);
    }

}
?>