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


class SettingViewWizard_view extends AppView
{
    
    var $tmpl = 'form_wizard_view.html';
    
    var $views = array(
        'left',
        'fixed',
        'default'
    ); 
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('start_wizard_msg.ini');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $view_msg = AppMsg::getMsg('public_setting/setting_msg.ini', false, 'view_format');
        $view_msg = [
            'default' => $view_msg['option_2'],
            'left'    => $view_msg['option_1'],
            'fixed'   => $view_msg['option_3'],
        ];
        
        $i = 0;
        foreach ($this->views as $key) {
            $v = array();
            
            $v['id'] = $key;
            $v['title'] = $view_msg[$key];
            $v['desc'] = $this->msg[$key . '_view']['desc'];
            
            $selected = ($obj->get('view_format') == $key);
            $v['class'] = ($selected) ? 'selected' : '';
            $v['checked'] = ($selected) ? 'checked' : '';
            
            $v['slide_num'] = $i;
            
            $tpl->tplParse($v, 'preview_row');
            $tpl->tplParse($v, 'zoomed_row');
            
            $i ++;
        }
        
        $setting_id = $manager->getSettingIdByKey('view_format');
        $tpl->tplAssign('setting_id', $setting_id);
        
        $setting_value = $manager->getSettings(2, 'view_format');
        $tpl->tplAssign('setting_value', $setting_value);
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }
	
}
?>