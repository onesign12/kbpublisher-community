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




class SettingViewSocialDebug_popup extends AppView
{

    var $tmpl = 'social_debug.html';


    function getLoginPage($user_info, $user_mapped) {

        $this->addMsg('common_msg.ini', 'sauth_setting');


        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('hint_msg', AppMsg::hintBox('saml_debug_login_success', 'saml_setting'));


        foreach ($user_mapped as $name => $value) {
            if (is_object($value)) {
                $value = (array) $value;
            }

            if (is_array($value)) {
                $value = print_r($value, true);
                $tpl->tplSetNeeded('row_mapped/array');

            } else {
                $tpl->tplSetNeeded('row_mapped/string');
            }

            $v = array(
                'attr_name' => $name,
                'attr_value' => $value,
            );

            $tpl->tplParse($v, 'row_mapped');
        }


        foreach ($user_info as $name => $value) {
            if (is_object($value)) {
                $value = (array) $value;
            }

            if (is_array($value)) {
                $value = print_r($value, true);
                $tpl->tplSetNeeded('row_responce/array');

            } else {
                $tpl->tplSetNeeded('row_responce/string');
            }

            $v = array(
                'attr_name' => $name,
                'attr_value' => $value,
            );

            $tpl->tplParse($v, 'row_responce');
        }

        $tpl->tplAssign('debug_info', '');
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }


    function getLogoutPage() {

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);

        $tpl->tplAssign('hint_msg', AppMsg::hintBox('saml_debug_logout_success', 'saml_setting'));

        $tpl->tplAssign($this->msg);
        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }


    function getErrorPage($error_msg, $debug_arr_msg = array()) {

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);

        $msg_vars = array('body' => $error_msg);
        $tpl->tplAssign('hint_msg', BoxMsg::factory('error', $msg_vars));

        $debug = '';
        if($debug_arr_msg) {
            $debug = DebugUtil::getDebugBlock($debug_arr_msg);
        }
        
        $tpl->tplAssign('debug_info', $debug);
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }

}
?>