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

namespace SAuthSetting;

use Validator;
use AuthSocial;



class SettingValidator
{
     
    function validate($values) {
        
        $providers = AuthSocial::getProviderList();
        foreach ($providers as $provider => $color) {
            if (!empty($values[$provider . '_auth'])) {
                $required = array(
                    $provider . '_client_id',
                    $provider . '_client_secret'
                );
                
                $v = new Validator($values, true);
                        
                // required
                $v->required('required_msg', $required);
                if($v->getErrors()) {
                    return $v->getErrors();
                }
            }
        }
    }
    
}
?>