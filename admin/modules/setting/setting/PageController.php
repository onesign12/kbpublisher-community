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


$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

$obj = new Setting;

$action = new SettingAction($rq, $rp);

if($controller->page == 'account_setting') {
    $manager = new SettingModelUser(AuthPriv::getUserId());
} else {
    $manager = new SettingModel();
    $_action = (isset($rp->set_default) || isset($rp->submit)) ? 'update' : $controller->action;
    $manager->checkPriv($priv, $_action, @$rq->id);    
}

$setting_ar = SettingModel::$key_to_module;
                    
$s = (isset($setting_ar[$controller->sub_page])) ? $setting_ar[$controller->sub_page] : $controller->page;
$manager->setModuleId($s);
$manager->loadParser();

$stored_settings_keys = array(
    'ldap' => array(
        'remote_auth_map_group_to_priv',
        'remote_auth_map_group_to_role',
        'remote_auth_map_priv_id',
        'remote_auth_map_role_id'
    )
);

$popup = $controller->getMoreParam('popup');

switch ($popup) {

case 'password_strength':
    $view = $action->getPasswordStrengthPopup($obj, $manager, $controller, $popup);
    break;

case 'file_local_allowed_directories':
    $view = $action->getAllowedDirectoryPopup($obj, $manager, $controller, $popup);
    break;

case 'register_agree_terms':
    $view = $action->getAgreeTermsPopup($obj, $manager, $controller, $popup);
    break;

case 'nav_extra':
    $view = $action->getExtraItemsPopup($obj, $manager, $controller, $popup);
    break;

case 'menu_main':
    $view = $action->getMainMenuItemsPopup($obj, $manager, $controller, $popup);
    break;
    
case 'page_to_load':
case 'page_to_load_mobile':    
    $view = $action->getPageTemplatePopup($obj, $manager, $controller);
    break;
    
case 'float_panel':
    $view = $action->getFloatPanelPopup($obj, $manager, $controller);
    break;
    
case 'ldap_debug':    
    $view = $action->getLdapDebugPopup($obj, $manager, $controller, $stored_settings_keys['ldap']);
    break;

case 'saml_debug':
    $view = $action->startSamlDebug($manager);
    break;
    
case 'saml_debug_slo':
    $view = $action->startSamlLogoutDebug();
    break;
    
case 'saml_metadata':
    $view = $action->getSamlMetadataPopup($obj, $manager, $controller);
    break;

case 'google_debug':
case 'facebook_debug':
case 'yandex_debug':
case 'twitter_debug':
case 'vk_debug':
    $view = $action->startSocialDebug($obj, $manager, $controller, $popup);
    break;

case 'remote_auth_map_group_to_priv':
case 'remote_auth_map_group_to_role':
    $view = $action->getLdapGroupPopup($obj, $manager, $controller, $popup);
    break;
    
case 'saml_map_group_to_priv':
case 'saml_map_group_to_role':
    $view = $action->getSamlGroupPopup($obj, $manager, $controller, $popup);
    break;

case 'saml_idp_certificate':
case 'saml_sp_certificate':
case 'saml_sp_private_key':
    $view = $action->getSamlCertPopup($obj, $manager, $controller, $popup);
    break;
    
case 'header_logo':
case 'header_logo_mobile':
    $view = $action->getHeaderPopup($obj, $manager, $controller, $popup);
    break;
    
case 'search_spell_suggest':
    $view = $action->getSpellSuggestPopup($obj, $manager, $controller);
    break;
    
case 'plugin_export_cover':
case 'plugin_export_header':
case 'plugin_export_footer':
    $view = $action->getExportPopup($obj, $manager, $controller, $popup);
    break;
    
case 'plugin_export_test':
    $view = $action->getExportTestPopup($obj, $manager, $controller);
    break;
    
case 'item_share_link':
    $view = $action->getSharePopup($obj, $manager, $controller);
    break;
    
case 'plugin_sphinx_index':
    $view = $action->getSphinxIndexPopup($obj, $manager, $controller);
    break;
    
case 'aws_s3_allow2':
    $view = $action->getAwsS3Popup($obj, $manager, $controller);
    break;
    
case 'search_filter_item':
    $view = $action->getSearchFilterItemPopup($obj, $manager, $controller);
    break;
    
    
default:

    // settings wizard
    if ($controller->page == 'common_setting') {
        
        $view = $action->getSettingViewWizard($obj, $manager, $controller);
        
        
        // $wizard_group_id = 1;
        // if(!empty($rq->group) && $ret = array_search($rq->group, $manager->wizard_groups)) {
        //     $wizard_group_id = $ret;
        // }
        // 
        // $manager->wizard_group_id = $wizard_group_id;
        // 
        // $view_class = 'SettingViewWizard';
        // 
        // if(isset($rp->submit)) {
        // 
        //     // TODO hardcoded smtp password, need to change 
        //     if(isset($rp->values[46])) {
        //         $rp->values[46] = EncryptedPassword::encode($rp->values[46]);
        //     }
        // 
        //     // TODO we do not have email sent validator here 
        //     // ../email_setting/SettingValidator.php -> if(!AppController::isAjaxCall()) {
        // 
        //     // $manager->setModuleId('email_setting');
        //     // $manager->loadParser(false, 'email_setting');
        //     // $values = $obj->prepareValues($rp->values, $manager);
        //     // $is_error = $obj->validate($values, $manager);
        //     // echo '<pre>', print_r($values,1), '<pre>';
        //     // echo '<pre>', print_r($is_error,1), '<pre>';
        //     // echo '<pre>', print_r($rp->values,1), '<pre>';
        //     // exit;
        // 
        //     $rp->stripVarsValues($rp->values, false);
        //     $manager->setSettings($rp->values);
        // 
        //     $next_group = $manager->wizard_groups[$wizard_group_id + 1];
        // 
        //     $more = array(
        //         'group' => $next_group, 
        //         'popup' => $controller->getMoreParam('popup')
        //     ); 
        //     $controller->goPage('this', 'this', false, false, $more);
        // 
        // } else {
        // 
        //     if (!$controller->getMoreParam('popup') && empty($rq->group) && !isset($rq->ajax)) {
        //         $view_class = 'SettingViewWizard_start';
        //     }
        // 
        //     $module_ids = $manager->getCommonGroupModules($wizard_group_id);
        // 
        //     if (!empty($module_ids)) {
        //         $data = $manager->getSettings($module_ids);
        // 
        //         // TODO hardcoded smtp password, need to change 
        //         if(isset($data[46])) {
        //             $data[46] = EncryptedPassword::decode($data[46]);
        //         }     
        // 
        //         $rp->stripVarsValues($data);
        //         $obj->set($data);
        //     }
        // }
        // 
        // $view = $controller->getView($obj, $manager, $view_class);
        
    
    // normal settings     
    } else {
        if(isset($rp->submit) || isset($rp->submit1)) {
    
            if(APP_DEMO_MODE) {
                $controller->go('not_allowed_demo', true);
            }
            
            $values = $obj->prepareValues($rp->values, $manager);
            $is_error = $obj->validate($values, $manager);
        
            if(!$is_error) {
                $v = new Validator($_POST);
                $v->csrf();
                $is_error = $obj->errors = $v->getErrors();
            }
        
            if($is_error) {
                
                if ($manager->module_id == 160) {
                    $stored_settings = $manager->getSettings(160);
                    foreach ($stored_settings_keys['ldap'] as $key) {
                        $values[$key] = $stored_settings[$key];
                    }    
                }
                
                $rp->stripVarsValues($values, true);
                $obj->set($values);
            
            } else {
                
                $old_values = &$manager->getSettings();
                $rp->stripVarsValues($old_values);
                
                $rp->stripVarsValues($values, false);
                $manager->save($values);
                
                $parser = $manager->getParser();
                $parser->manager->callOnSave($values, $old_values);
                
                $_GET['saved'] = 1;
                $controller->setMoreParams('saved');
                $controller->setMoreParams('popup');
                $controller->setMoreParams('tkey');
            
                $controller->go();
            }
    
        
        } elseif(isset($rp->set_default)) {
            
            $old_values = &$manager->getSettings();
            $rp->stripVarsValues($old_values);
            
            if(APP_DEMO_MODE) { 
                $controller->go('not_allowed_demo', true); 
            }    
            
            $id = (!empty($rq->id)) ? $rq->id : false;
            $manager->setDefaultValues($id);
            
            $values = &$manager->getSettings();
            $rp->stripVarsValues($values);
            
            $parser = $manager->getParser();
            $parser->manager->callOnSave($values, $old_values);
                
            $controller->go();
    
        } else {
    
            $data = $manager->getSettings();
            $rp->stripVarsValues($data);
            $obj->set($data);
        }
    
        $view = $controller->getView($obj, $manager, 'SettingView_form');
    }
    
}
?>