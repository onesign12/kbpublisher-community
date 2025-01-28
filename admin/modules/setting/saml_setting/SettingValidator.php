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

namespace SamlSetting;

use Validator;
use SettingModel;

class SettingValidator
{
     
    function validate($values) {
        $required = array(
            'saml_name', 'saml_issuer', 'saml_sso_endpoint',
            'saml_map_fname', 'saml_map_lname', 'saml_map_email',
            // 'saml_map_username'
        );
    
        $v = new Validator($values, true);
        
        // required
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            return $v->getErrors();
        }
        
        $url_settings = array('saml_sso_endpoint', 'saml_slo_endpoint');
        foreach ($url_settings as $setting) {
            if (!empty($values[$setting])) {
                $url = filter_var($values[$setting], FILTER_VALIDATE_URL);
                if (empty($url)) {
                    $v->setError('invalid_url_msg', $setting);
                }
            }
        }
        
        if ($values['saml_algorithm'] != 'off') {
            $signature_settings = array('saml_sp_certificate', 'saml_sp_private_key');
            foreach ($signature_settings as $setting) {
                $value = SettingModel::getQuick(162, $setting);
                if (empty($value)) {
                    $v->setError('required_msg', $setting);
                    return $v->getErrors();
                }
            }
        }
        
        return $v->getErrors();
    }
    
}
?>