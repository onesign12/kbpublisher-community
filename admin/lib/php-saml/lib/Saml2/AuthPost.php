<?php

require_once 'Auth.php';


class OneLogin_Saml2_Auth_Post extends OneLogin_Saml2_Auth
{
    
    public function login($returnTo = null, $parameters = array(), $forceAuthn = false, $isPassive = false, $stay=false, $setNameIdPolicy = true)
    {
        assert('is_array($parameters)');

        $authnRequest = new OneLogin_Saml2_AuthnRequest($this->_settings, $forceAuthn, $isPassive, $setNameIdPolicy);

        $this->_lastRequestID = $authnRequest->getId();

        $samlRequest = $authnRequest->getRequest();
        
        
        $samlRequestXML = gzinflate(base64_decode($samlRequest));
        $key = $this->_settings->getSPkey();
        $cert = $this->_settings->getSPcert();
        $sigAlg = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
        
        if ($key && $cert) {
            $samlRequestXML = OneLogin_Saml2_Utils::addSign($samlRequestXML, $key, $cert);
        }
        
        $encoded_saml_request = base64_encode($samlRequestXML);
        
        
        $parameters['SAMLRequest'] = $encoded_saml_request;

        if (!empty($returnTo)) {
            $parameters['RelayState'] = $returnTo;
        } else {
            $parameters['RelayState'] = OneLogin_Saml2_Utils::getSelfRoutedURLNoQuery();
        }

        $security = $this->_settings->getSecurityData();
        if (isset($security['authnRequestsSigned']) && $security['authnRequestsSigned']) {
            $signature = $this->buildRequestSignature($samlRequest, $parameters['RelayState'], $security['signatureAlgorithm']);
            $parameters['SigAlg'] = $security['signatureAlgorithm'];
            $parameters['Signature'] = $signature;
        }
        
        
        return $this->submitPostData($this->getSSOurl(), $parameters);
        //return $this->redirectTo($this->getSSOurl(), $parameters, $stay);
    }
    
    
    public function logout($returnTo = null, $parameters = array(), $nameId = null, $sessionIndex = null, $stay=false)
    {
        assert('is_array($parameters)');
        
        $sloUrl = $this->getSLOurl();
        if (empty($sloUrl)) {
            throw new OneLogin_Saml2_Error(
                'The IdP does not support Single Log Out',
                OneLogin_Saml2_Error::SAML_SINGLE_LOGOUT_NOT_SUPPORTED
            );
        }

        if (empty($nameId) && !empty($this->_nameid)) {
            $nameId = $this->_nameid;
        }

        $logoutRequest = new OneLogin_Saml2_LogoutRequest($this->_settings, null, $nameId, $sessionIndex);

        $this->_lastRequestID = $logoutRequest->id;
        
        $samlRequest = $logoutRequest->getRequest();
        
        
        $samlRequestXML = gzinflate(base64_decode($samlRequest));
        $key = $this->_settings->getSPkey();
        $cert = $this->_settings->getSPcert();
        $sigAlg = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
        
        if ($key && $cert) {
            $samlRequestXML = OneLogin_Saml2_Utils::addSign($samlRequestXML, $key, $cert);
        }
        
        $encoded_saml_request = base64_encode($samlRequestXML);
        
        $parameters['SAMLRequest'] = $encoded_saml_request;
        if (!empty($returnTo)) {
            $parameters['RelayState'] = $returnTo;
        } else {
            $parameters['RelayState'] = OneLogin_Saml2_Utils::getSelfRoutedURLNoQuery();
        }

        $security = $this->_settings->getSecurityData();
        if (isset($security['logoutRequestSigned']) && $security['logoutRequestSigned']) {
            $signature = $this->buildRequestSignature($samlRequest, $parameters['RelayState'], $security['signatureAlgorithm']);
            $parameters['SigAlg'] = $security['signatureAlgorithm'];
            $parameters['Signature'] = $signature;
        }
        
        //$data = http_build_query($parameters);
        
        return $this->submitPostData($sloUrl, $parameters);
        //return $this->redirectTo($sloUrl, $parameters, $stay);
    }
    
    
    public static function submitPostData($url, $parameters)
    {
        
        $tpl = new tplTemplatez(APP_CLIENT_DIR . 'client/skin/view_default/default/auto_form.html');
            
        $tpl->tplAssign('action', $url);
        
        foreach ($parameters as $k => $v) {
            $v1 = array(
                'name' => $k,
                'value' => $v,
            );
            
            $tpl->tplParse($v1, 'param');
        }
        
        $tpl->tplParse();
        
        echo $tpl->tplPrint(1);
        exit;
    }
    
}
