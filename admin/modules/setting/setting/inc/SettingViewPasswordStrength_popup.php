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



class SettingViewPasswordStrength_popup extends SettingView_form
{

    var $tmpl = 'password_strength.html';
    

    function execute(&$obj, &$manager, $extra_options = []) {

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $tpl->tplAssign('hint_msg', AppMsg::hintBoxCommon('note_password_strength'));
        $tpl->tplAssign('min_special_tooltip', str_replace('\\', '', PasswordUtil::getSpecialChars()));
        
        $rules = PasswordUtil::parsePasswordStrengthRules($obj->get('password_strength'));
        $tpl->tplAssign($rules);
        
        $parser = &$manager->getParser();
        $setting_msg = $parser->getSettingMsg($manager->module_name);
        
        $popup = $this->controller->getMoreParam('popup');
        $tpl->tplAssign('setting_name', $popup);
        $tpl->tplAssign('popup_title', $setting_msg[$popup]['title']);
        $tpl->tplAssign($setting_msg[$popup . '_rule']);
        
        $tpl->tplParse($this->msg);

        return $tpl->tplPrint(1);
    }

}
?>