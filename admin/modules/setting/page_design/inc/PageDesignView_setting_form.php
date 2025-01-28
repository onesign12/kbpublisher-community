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


class PageDesignView_setting_form extends AppView
{
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('client_msg.ini', 'public');
        $this->addMsg('page_design_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . 'setting_form.html');
        
        if($this->controller->getMoreParam('saved')) {
            $tpl->tplSetNeeded('/close_window');
        }
        
        $block_id = $this->controller->getMoreParam('field_id');
        $tpl->tplAssign('block_id', $block_id);
        
        if (!empty(PageDesignData::$blocks[$block_id]['settings'])) {
            
            $settings = array();
            foreach (PageDesignData::$blocks[$block_id]['settings'] as $setting_key => $default_value) {
                $v['title'] = $this->msg[$setting_key]['title'];
                $v['setting_key'] = $setting_key;
                
                $tpl->tplParse($v, 'row');
            }
            
            $tpl->tplAssign('setting_key', $setting_key);
        }
        
        $key = PageDesignData::$blocks[$block_id]['title'];
        $tpl->tplAssign('title', $this->msg[$key]);
        $tpl->tplAssign('setting_title', $this->msg[$key]);
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }

}
?>