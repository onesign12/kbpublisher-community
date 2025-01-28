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



class SettingViewSamlMetadata_popup extends SettingView_form
{
    
    
    function execute(&$obj, &$manager, $extra_options = array()) {
        
        AuthProvider::loadSaml();
        
        $saml_settings = array(
            'sp' => AuthSaml::getSPSettings()
        );
        
        if ($this->controller->getMoreParam('file')) { // xml
            
            try {
                $settings = new OneLogin_Saml2_Settings($saml_settings, true);
            
                $metadata = $settings->getSPMetadata();
                $errors = $settings->validateMetadata($metadata);
                
                if (empty($errors)) {
                    
                    $params['data'] = $metadata;
                    $params['gzip'] = false;
                    $params['contenttype'] = 'text/xml';
            
                    $filename = 'metadata.xml';
            	    WebUtil::sendFile($params, $filename);
                    
                    exit;
                    
                } else {
                    throw new OneLogin_Saml2_Error(
                        'Invalid SP metadata: ' . implode(', ', $errors),
                        OneLogin_Saml2_Error::METADATA_SP_INVALID
                    );
                }
            
            } catch (Exception $e) {
                echo $e->getMessage();
                die();
            }
        }
        
        $this->addMsg('common_msg.ini', 'saml_setting');
        
        $tpl = new tplTemplatez($this->template_dir . 'saml_metadata.html');
        
        $tpl->tplAssign('acs_url', $saml_settings['sp']['assertionConsumerService']['url']);
        $tpl->tplAssign('sls_url', $saml_settings['sp']['singleLogoutService']['url']);
        $tpl->tplAssign('entity_id', $saml_settings['sp']['entityId']);
        
        $more = array('popup' => 'saml_metadata', 'file' => 1);
        $link = $this->controller->getLink('this', 'this', false, false, $more);
        $tpl->tplAssign('metadata_link', $this->controller->_replaceArgSeparator($link));
        
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();
    
        return $tpl->tplPrint(1);
    }
}
?>