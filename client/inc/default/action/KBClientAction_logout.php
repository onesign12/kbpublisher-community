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

class KBClientAction_logout extends KBClientAction_common
{

    function &execute($controller, $manager) {
        
        $auth_setting = AuthProvider::getSettings();
        
        // saml
        if (AuthProvider::isSamlAuth() && AuthPriv::isSaml() && $auth_setting['saml_slo_endpoint']) {
            AuthProvider::loadSaml();
            
            $ol_auth = AuthSaml::getOneLogin($auth_setting, $auth_setting['saml_slo_binding']);
            
            $relay_state = (!empty($this->rq->return)) ? $this->rq->return : $controller->getLink();
            $user = $manager->getUserInfo(AuthPriv::getUserId());
            
            $remote_provider_id = AuthProvider::getProviderId('saml');
            $sso = $manager->getUserSso(AuthPriv::getUserId());
            $name_id = (!empty($sso[$remote_provider_id])) ? $sso[$remote_provider_id] : null;
            
            AuthPriv::logout();
            $ol_auth->logout($relay_state, array(), $name_id);
            exit;
        }
    
        AuthPriv::logout();
        $controller->go();
    }
    
}
?>