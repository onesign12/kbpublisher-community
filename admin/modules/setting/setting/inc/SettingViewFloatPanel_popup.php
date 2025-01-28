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



class SettingViewFloatPanel_popup extends SettingView_form
{

    function execute(&$obj, &$manager, $extra_options = array()) {
        $this->addMsg('common_msg.ini', 'knowledgebase');
        $this->addMsg('common_msg.ini', 'public_setting');
        $this->addMsg('client_msg.ini', 'public');
        
        $tpl = new tplTemplatez($this->template_dir . 'float_panel.html');
        
        $parser = &$manager->getParser();
        $setting_msg = $parser->getSettingMsg($manager->module_name);
        
        $tpl->tplAssign('popup_title', $setting_msg['float_panel']['title']);
        
        $social_items = $manager->getSettings(100, 'item_share_link');
        $social_items = unserialize($social_items);
        
        $reg =& Registry::instance();
        $conf =& $reg->getEntry('conf');
        
        $client_manager = new KBClientModel;
        $client_manager->setting = KBClientModel::getSettings('2, 100');
        
        $setting['auth_check_ip'] = $conf['auth_check_ip'];
        $setting['auth_captcha'] = false;
        $setting['view_style'] = 'default';
        $setting['view_format'] = 'default';
        $setting['private_policy'] = 1;

        $controller2 = new KBClientController();
        $controller2->setDirVars($setting);
        $controller2->setModRewrite(false);
        
        $reg->setEntry('controller', $controller2);
        
        $view = new KBClientView;
        $view->view_id = 'entry';
        
        $items = SettingData::getFloatPanelItems($client_manager, $view);
        
        $groups = array(
            'active', 'inactive' 
        );
        
        foreach ($groups as $group) {
            foreach ($items[$group] as $item => $link) {
                $v = array();
                $v['key'] = $item;
                
                if (!empty(SettingData::$sharing_sites[$item])) {
                    $v['title'] = SettingData::$sharing_sites[$item]['title'];
                    
                } elseif(substr($item, 0, 7) == 'custom_') {
                    $custom_id = substr($item, 7);
                    foreach ($social_items['active'] as $v1) {
                        if (is_array($v1) && $v1['id'] == $custom_id) {
                            $v['title'] = $v1['title'];
                        }
                    }
                    
                } else {
                    $msg_key = SettingData::$panel_items[$item]['title'];
                    $v['title'] = $this->msg[$msg_key];
                    
                    $msg_key = SettingData::$panel_items[$item]['desc'];
                    $v['desc'] = ($msg_key) ? $this->msg[$msg_key] : '';
                }
                
                $tpl->tplParse($v, $group . '_item');
            }
        }

        $more = array('panel' => 1);
        $link = $this->getLink('setting', 'public_setting', 'kba_setting', false, $more);
        $tpl->tplAssign('social_link', $link);

        $tpl->tplParse($this->msg);

        return $tpl->tplPrint(1);
    }

}

?>