<?php

class SettingAction extends AppAction
{

    function getPasswordStrengthPopup($obj, $manager, $controller, $popup) {

        // sort order
        if(isset($this->rp->submit)) {
            
            $original_error = AppMsg::getMsgs('error_msg.ini')['pass_weak_msg'];
            if($original_error == stripslashes($this->rp->vars['rule']['error'])) {
                unset($this->rp->vars['rule']['error']);
            }
            
            // $items = implode('|', $this->rp->vars['rule']);
            $rules = $this->rp->vars['rule'];
            $rules['length'] = (intval($rules['length']) < 8) ? 8 :  (int) $rules['length'];
            $rule = implode('|', 
                        array_map(
                            function ($v, $k) {
                                $v = ($k == 'error') ? $v : (int) $v;
                                return sprintf("%s=%s", $k, $v); 
                            },
                            $rules,
                            array_keys($rules)  
                        ));
            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setSettings(array($setting_id => addslashes($rule)));

            $controller->go();
        
        // default 
        } elseif (isset($this->rp->set_default)) {
            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setDefaultValues($setting_id, false);

            $controller->go();
        }

        $data = $manager->getSettings(1, $popup);
        $this->rp->stripVarsValues($data);
        $obj->set(array($popup => $data));

        $view = $controller->getView($obj, $manager, 'SettingViewPasswordStrength_popup');

        return $view;
    }
    
    
    function getAllowedDirectoryPopup($obj, $manager, $controller, $popup) {

        // sort order
        if(isset($this->rp->submit)) {
            $data = $manager->getSettings(1, $popup);
            $items = explode('||', $data);

            $sorted_items = array();
            foreach ($this->rp->sort_id as $line_num) {
                $sorted_items[$line_num] = $items[$line_num];
            }

            $sorted_items = implode('||', $sorted_items);

            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setSettings(array($setting_id => addslashes($sorted_items)));
        
        // default 
        } elseif (isset($this->rp->set_default)) {
            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setDefaultValues($setting_id, false);

            $controller->go();
        }

        $data = $manager->getSettings(1, $popup);
        //$this->rp->stripVarsValues($data);
        $obj->set(array($popup => $data));

        $view = $controller->getView($obj, $manager, 'SettingViewAllowedDirectory_popup');

        return $view;
    }
    
    
    function getExtraItemsPopup($obj, $manager, $controller, $popup) {

        // sort order
        if(isset($this->rp->submit)) {
            $data = $manager->getSettings(2, $popup);
            $items = explode('||', $data);

            $sorted_items = array();
            foreach ($this->rp->sort_id as $line_num) {
                $sorted_items[$line_num] = $items[$line_num];
            }

            $sorted_items = implode('||', $sorted_items);

            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setSettings(array($setting_id => addslashes($sorted_items)));
        }

        $data = $manager->getSettings(2, $popup);
        //$this->rp->stripVarsValues($data);
        $obj->set(array($popup => $data));

        $view = $controller->getView($obj, $manager, 'SettingViewExtraItems_popup');

        return $view;
    }


    function getAgreeTermsPopup($obj, $manager, $controller, $popup) {

        // sort order
        if(isset($this->rp->submit)) {
            $data = $manager->getSettings(2, $popup);
            $items = explode('||', $data);

            $sorted_items = array();
            foreach ($this->rp->sort_id as $line_num) {
                $sorted_items[$line_num] = $items[$line_num];
            }

            $sorted_items = implode('||', $sorted_items);

            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setSettings(array($setting_id => addslashes($sorted_items)));
         
        // default 
        } elseif (isset($this->rp->set_default)) {
            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setDefaultValues($setting_id, false);

            $controller->go();
        }

        $data = $manager->getSettings(2, $popup);
        //$this->rp->stripVarsValues($data);
        $obj->set(array($popup => $data));

        $view = $controller->getView($obj, $manager, 'SettingViewAgreeTerms_popup');

        return $view;
    }


    function getMainMenuItemsPopup($obj, $manager, $controller, $popup) {
        

        $view = 'SettingViewMainMenuItems_popup';

        // sort order
        if(isset($this->rp->submit)) {
            $items = $manager->getSettings(2, $popup);
            $items = unserialize($items);
            $items = SettingData::getMainMenu($items);

            // visible
            $visible_items = array();
            foreach ($this->rp->visible_id as $line_num) {
                $visible_items[$line_num] = $items['active'][$line_num];
                $visible_items[$line_num]['dropdown'] = false;
            }
            $visible_items = array_values($visible_items);

            // dropdown
            $dropdown_items = array();
            foreach ($this->rp->dropdown_id as $line_num) {
                $dropdown_items[$line_num] = $items['active'][$line_num];
                $dropdown_items[$line_num]['dropdown'] = true;
            }
            $dropdown_items = array_values($dropdown_items);

            $items['active'] = array_merge($visible_items, $dropdown_items);
            $items = serialize($items);

            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setSettings(array($setting_id => addslashes($items)));

            $controller->go();

        } elseif(!empty($this->rq->detail)) {
            $view = 'SettingViewMainMenuItems_detail_popup';

        } elseif(!empty($this->rq->default)) {
            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setDefaultValues($setting_id, false);

            // hidden settings
            $settings = array();
            foreach (SettingData::$main_menu as $item) {
                if (!empty($item['setting'])) {
                    $setting_id = $manager->getSettingIdByKey($item['setting']);
                    $settings[$setting_id] = 1;
                }
            }

            $manager->setSettings($settings);

            $controller->go();
        }

        $data = $manager->getSettings(2, $popup);
        //$this->rp->stripVarsValues($data);
        $obj->set(array($popup => $data));

        $view = $controller->getView($obj, $manager, $view);

        return $view;
    }


    function getPageTemplatePopup($obj, $manager, $controller) {

        $manager->module_id = 10; // hidden, popup setting
        $html_keys = array('page_to_load_tmpl', 'page_to_load_tmpl_mobile');

        if(isset($this->rp->submit)) {

            if(APP_DEMO_MODE) {
                $controller->go('not_allowed_demo', true);
            }

            $delim = '--delim--';

            $html_ids = array_values($manager->getSettingIdByKey($html_keys));
            foreach ($html_ids as $v) {
                if (!empty($this->rp->values[$v])) {
                    ksort($this->rp->values[$v]);
                    $this->rp->values[$v] = implode($delim, $this->rp->values[$v]);

                     // (int) to be parsed as skipped as we do strict in_array comparsion
                    $this->rp->setCurlyBracesValues((int) $v);
                    $this->rp->setHtmlValues((int) $v);
                }
            }

            $this->rp->stripVarsValues($this->rp->values, false);


            $setting_keys = $manager->getSettingKeys();
            $non_color_keys = array('page_to_load_tmpl', 'page_to_load_tmpl_mobile', 'left_menu_width');
            $color_pattern = '/^#[0-9A-Fa-f]+$/';

            $values = array();
            foreach($this->rp->values as $setting_id => $v) {
                if (!empty($setting_keys[$setting_id])) {

                    if (!in_array($setting_keys[$setting_id], $non_color_keys)) { // it's a color
                        if (!preg_match($color_pattern, $v)) {
                            $v = '';
                        }
                    }

                    if ($setting_keys[$setting_id] == 'left_menu_width') { // left block
                        $v = (int) $v;
                        if ($v < 230) {
                            $v = 230;
                        }
                    }
                }

                $values[$setting_id] = array($setting_id, $v);
            }

            $manager->saveQuery($values);

            $_GET['saved'] = 1;
            $controller->setMoreParams('saved');
            $controller->setMoreParams('popup');
            $controller->go('success', true);

        } else {

            $data = $manager->getSettings();
            foreach ($html_keys as $v) {
                $this->rp->setCurlyBracesValues($v);
                $this->rp->setHtmlValues($v);
            }

            $this->rp->stripVarsValues($data);
            $obj->set($data);
        }

        $view = $controller->getView($obj, $manager, 'SettingViewPageTemplate_popup');
        return $view;
    }


    function getFloatPanelPopup($obj, $manager, $controller) {
        $popup = 'float_panel';

        if(isset($this->rp->submit)) {
            $items = ($this->rp->visible_id) ? implode(',', $this->rp->visible_id) : false;

            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setSettings(array($setting_id => addslashes($items)));

            $controller->go();

        } elseif (isset($this->rp->set_default)) {
            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setDefaultValues($setting_id, false);

            $controller->go();
        }

        $data = $manager->getSettings(100, $popup);
        //$this->rp->stripVarsValues($data);
        $obj->set(array($popup => $data));

        $view = $controller->getView($obj, $manager, 'SettingViewFloatPanel_popup');
        return $view;
    }


    function getLdapDebugPopup($obj, $manager, $controller, $stored_settings_keys) {

        $manager->module_id = 160;

        $setting_keys = $manager->getSettingKeys();

        $values = array();
        foreach ($setting_keys as $k => $v) {
            if (!empty($this->rp->values[$k])) {
                $values[$v] = $this->rp->values[$k];
            }
        }

        $stored_settings = $manager->getSettings(160);
        foreach ($stored_settings_keys as $key) {
            $values[$key] = $stored_settings[$key];
        }

        $obj->set($values);

        $view = $controller->getView($obj, $manager, 'SettingViewAuthDebug_popup');

        return $view;
    }


    function startSamlDebug($manager) {
        AuthProvider::loadSaml();

        // submitted from form
        $values = array();
        $setting_keys = $manager->getSettingKeys();
        foreach ($setting_keys as $k => $v) {
            if (!empty($this->rp->values[$k])) {
                $values[$v] = $this->rp->values[$k];
            }
        }

        // stored
        $stored_settings = $manager->getSettings(162);
        $stored_settings_keys = array(
            'saml_map_group_to_priv', 'saml_map_group_to_role',
            'saml_idp_certificate', 'saml_sp_certificate',
            'saml_sp_private_key'
        );

        foreach ($stored_settings_keys as $key) {
            $values[$key] = $stored_settings[$key];
        }

        try {

            // initiating a request
            $ol_auth = AuthSaml::getOneLogin($values, $values['saml_sso_binding']);

            $necessary_keys = array(
                'saml_issuer', 'saml_sso_endpoint',
                'saml_sso_binding', 'saml_slo_endpoint',
                'saml_slo_binding', 'saml_map_fname',
                'saml_map_lname', 'saml_map_email',
                'saml_map_username', 'saml_map_remote_id',
                'saml_algorithm'
            );

            // for later use
            foreach ($necessary_keys as $k) {
                $_SESSION['saml_settings_'][$k] = $values[$k];
            }

            $ol_auth->login('debug');

        } catch (Exception $e) {
            echo $e->getMessage();
        }

        exit;
    }


    function startSamlLogoutDebug() {
        AuthProvider::loadSaml();

        if (empty($_SESSION['saml_settings_'])) {
            exit;
        }

        try {
            // initiating a request
            $ol_auth = AuthSaml::getOneLogin($_SESSION['saml_settings_'], $_SESSION['saml_settings_']['saml_slo_binding']);
            $ol_auth->logout('debug', array(), $_SESSION['saml_settings_']['name_id']);

        } catch (Exception $e) {
            echo $e->getMessage();
        }

        exit;
    }


    function getSamlCertPopup($obj, $manager, $controller, $popup) {

        if(isset($this->rp->submit)) {
            $setting_id = $manager->getSettingIdByKey($popup);

            $values = array();
            $values[$setting_id] = array($setting_id, $this->rp->values[$setting_id]);
            $manager->saveQuery($values);

            $_GET['saved'] = 1;
            $controller->setMoreParams('saved');
            $controller->setMoreParams('popup');
            $controller->go();
        }

        $data = $manager->getSettings(162, $popup);
        $this->rp->stripVarsValues($data);
        $obj->set(array($popup => $data));

        $view = $controller->getView($obj, $manager, 'SettingViewSamlCert_popup');

        return $view;
    }


    function getSamlMetadataPopup($obj, $manager, $controller) {

        $manager->module_id = 162;

        $setting_keys = $manager->getSettingKeys();

        $values = array();
        foreach ($setting_keys as $k => $v) {
            if (!empty($this->rp->values[$k])) {
                $values[$v] = $this->rp->values[$k];
            }
        }

        $obj->set($values);

        $view = $controller->getView($obj, $manager, 'SettingViewSamlMetadata_popup');

        return $view;
    }


    function getLdapGroupPopup($obj, $manager, $controller, $popup) {

        $manager->module_id = 160;

        $setting_keys = $manager->getSettingKeys();

        $values = array();
        foreach ($setting_keys as $k => $v) {
            if (!empty($this->rp->values[$k])) {
                $values[$v] = $this->rp->values[$k];
            }
        }

        $obj->set($values);

        $view = ($popup == 'remote_auth_map_group_to_priv') ? 'SettingViewAuthMapPriv_popup' : 'SettingViewAuthMapRole_popup';
        $view = $controller->getView($obj, $manager, $view);

        return $view;
    }


    function getSamlGroupPopup($obj, $manager, $controller, $popup) {

        $manager->module_id = 162;

        $setting_keys = $manager->getSettingKeys();

        // sort order
        if(isset($this->rp->submit)) {
            $data = $manager->getSettings(162, $popup);
            $items = explode("\n", $data);

            $sorted_items = array();
            foreach ($this->rp->sort_id as $line_num) {
                $sorted_items[$line_num] = $items[$line_num];
            }

            $sorted_items = implode("\n", $sorted_items);

            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setSettings(array($setting_id => addslashes($sorted_items)));
        }

        $values = array();
        foreach ($setting_keys as $k => $v) {
            if (!empty($this->rp->values[$k])) {
                $values[$v] = $this->rp->values[$k];
            }
        }

        $obj->set($values);

        $view = $controller->getView($obj, $manager, 'SettingViewSamlMap_popup');

        return $view;
    }


    function startSocialDebug($obj, $manager, $controller, $popup) {
        

        $provider = str_replace('_debug', '', $popup);
        $key_client_id = $provider . '_client_id';
        $key_client_secret = $provider . '_client_secret';

        $values = $obj->prepareValues($this->rp->values, $manager);
        $credentials = array(
            $key_client_id => $values[$key_client_id],
            $key_client_secret => $values[$key_client_secret]
        );

        $_SESSION['social_auth_debug_'] = $credentials;

        $auth = AuthSocial::factory($provider, $credentials);
        $link = $auth->getLoginLink(true);

        // no state param for twitter
        // $_SESSION['twitter_debug'] = 1;

        header('Location: ' . $link);
        exit;
    }


    function getHeaderPopup($obj, $manager, $controller, $popup) {

        if(isset($this->rp->submit) && !empty($_FILES)) {

            $is_error = false;

            if (empty($_FILES['logo_1']['name'])) {
                $is_error = true;

                $msgs = AppMsg::getMsgs('error_msg.ini', false, 'nothing_to_upload', 0);
                $obj->errors['formatted'][]['msg'] = BoxMsg::factory('error', $msgs);

            } else {
                $upload = $manager->getUploader();
                $upload->setMaxSize(48); // due to the text datatype limit of 64kb
                $upload->setAllowedExtension('jpg', 'png', 'gif');

                $upload = $manager->upload($upload);

                if(!empty($upload['error_msg'])) {
                    $is_error = true;
                    $obj->errors['formatted'][]['msg'] = $upload['error_msg'];
                }
            }

            if (!$is_error) {
                
                

                $image_data = FileUtil::read($upload['filename']);
                $mime_type = mime_content_type($upload['filename']);
                $encoded_image = base64_encode($image_data);

                $value = sprintf('data:%s;base64,%s', $mime_type, $encoded_image);

                $values = array();
                $header_logo_id = $manager->getSettingIdByKey($popup);

                $values[$header_logo_id] = array($header_logo_id, $value);
                $manager->saveQuery($values);

                $_GET['saved'] = 1;
                $controller->setMoreParams('saved');
                $controller->setMoreParams('popup');
                $controller->go();
            }

        } elseif(isset($this->rp->submit_delete)) {
            $values = array();
            $header_logo_id = $manager->getSettingIdByKey($popup);
            $values[$header_logo_id] = array($header_logo_id, '');
            $manager->saveQuery($values);

            $_GET['saved'] = 1;
            $controller->setMoreParams('saved');
            $controller->setMoreParams('popup');
            $controller->go();
        }

        $data = $manager->getSettings(2, $popup);
        $this->rp->stripVarsValues($data);
        $obj->set(array('header_logo' => $data));

        $view = $controller->getView($obj, $manager, 'SettingViewHeaderLogo_popup');

        return $view;
    }


    function getSpellSuggestPopup($obj, $manager, $controller) {

        $controller->setMoreParams('tkey');

        $source = '';
        if(!empty($this->rq->source)) {
            $source = $this->rq->source;
            if(!in_array($source, SpellSuggest::$sources)) {
                echo 'Wrong Spell Suggest Source!';
                exit;
            }
        }

        if(isset($this->rp->submit)) {

            $spell_check = 0;
            if (isset($this->rp->primary)) {
                $spell_check = $source;
            }

            if ($source == 'bing') {
                $val = array(
                    'search_spell_bing_spell_check_key' => $this->rp->bing_spell_check_key,
                    'search_spell_bing_spell_check_url' => $this->rp->bing_spell_check_url,
                    'search_spell_bing_autosuggest_key' => $this->rp->bing_autosuggest_key,
                    'search_spell_bing_autosuggest_url' => $this->rp->bing_autosuggest_url
                );

                if ($spell_check) {
                    $ret = PublicSetting\SettingValidator::validateBing($this->rp->bing_spell_check_url, $this->rp->bing_spell_check_key);
                    $ret2 = PublicSetting\SettingValidator::validateBing($this->rp->bing_autosuggest_url, $this->rp->bing_autosuggest_key);
                    if (!$ret || !$ret2) {
                        $spell_check = 0;
                        $more = array('popup' => 'search_spell_suggest', 'bad_url' => 1);
                        $controller->goPage('this', 'this', 'this', false, $more);
                    }
                }

            } elseif ($source == 'pspell') {
                $val = array(
                    'search_spell_pspell_dic' => $this->rp->dictionary
                );

            } else {

                $val = array(
                    'search_spell_enchant_provider' => $this->rp->provider,
                    'search_spell_enchant_dic' => $this->rp->dictionary
                );
            }

            $val['search_spell_custom'] = $this->rp->custom_words;
            $val['search_spell_suggest'] = $spell_check;

            $values = array();
            $keys = $manager->getSettingIdByKey(array_keys($val));
            foreach($keys as $k => $id) {
                $values[$id] = array($id, $val[$k]);
            }

            $manager->saveQuery($values);

            $_GET['saved'] = 1;
            $controller->setMoreParams('saved');
            $controller->setMoreParams('popup');

            $controller->go();
        }

        $manager->module_id = 2;
        $data = $manager->getSettings();
        $this->rp->stripVarsValues($data);
        $obj->set($data);

        $view_name = (empty($source)) ? 'SettingViewSpellCheck_list_popup' : 'SettingViewSpellCheck_popup';
        $view = $controller->getView($obj, $manager, $view_name);

        return $view;
    }


    function getExportPopup($obj, $manager, $controller, $popup) {

        if(isset($this->rp->submit) || isset($this->rp->submit_disable)) {

            $is_error = false;
            $on = (isset($this->rp->submit));

            if (!$is_error) {
                $this->rp->setCurlyBracesValues('body');
                $this->rp->setHtmlValues('body');

                $val = array(
                    $popup => ($on) ? 1 : '',
                    $popup . '_tmpl' => $this->rp->body
                );

                if ($popup == 'plugin_export_header') {
                    $val['plugin_wkhtmltopdf_margin_top'] = $this->rp->margin;
                }

                if ($popup == 'plugin_export_footer') {
                    $val['plugin_wkhtmltopdf_margin_bottom'] = $this->rp->margin;
                }

                $values = array();
                $keys = $manager->getSettingIdByKey(array_keys($val));
                foreach($keys as $k => $id) {
                    $values[$id] = array($id, $val[$k]);
                }

                $manager->saveQuery($values);

                if ($on) {
                    $_GET['saved'] = 1;
                    $controller->setMoreParams('saved');

                } else {
                    $_GET['disabled'] = 1;
                    $controller->setMoreParams('disabled');
                }

                $controller->setMoreParams('popup');
                $controller->go();
            }

        }

        $data = $manager->getSettings(140);

        $this->rp->setCurlyBracesValues($popup . '_tmpl');
        $this->rp->setHtmlValues($popup . '_tmpl');

        $this->rp->stripVarsValues($data);
        $obj->set($data);

        $view = $controller->getView($obj, $manager, 'SettingViewPluginExport_popup');

        return $view;
    }


    function getExportTestPopup($obj, $manager, $controller) {

        $manager->module_id = 140;

        $setting_keys = $manager->getSettingKeys();

        $values = array();
        foreach ($setting_keys as $k => $v) {
            if (isset($this->rp->values[$k])) {
                $values[$v] = $this->rp->values[$k];
            }
        }

        $stored_settings = $manager->getSettings(140);
        if(BaseModel::isCloud()) {
            $plugin_wkhtmltopdf_path = $stored_settings['plugin_wkhtmltopdf_path'];
        } else {
            $plugin_wkhtmltopdf_path = $values['plugin_wkhtmltopdf_path'];
        }

        $options = array(
            'check_setting' => true,
            'config' => array(
                'tool_path' => $plugin_wkhtmltopdf_path
            )
        );

        $keys = array('cover', 'header', 'footer');
        foreach ($keys as $key) {
            if (isset($this->rp->vars['plugin_export_' . $key])) {
                $values['plugin_export_' . $key] = 1;
                $values['plugin_export_' . $key . '_tmpl'] = $this->rp->vars['plugin_export_' . $key];

            } else {
                $values['plugin_export_' . $key] = $stored_settings['plugin_export_' . $key];
                $values['plugin_export_' . $key . '_tmpl'] = $stored_settings['plugin_export_' . $key . '_tmpl'];
            }
        }


        $keys2 = array('top', 'bottom');
        foreach ($keys2 as $key) {
            if (isset($this->rp->vars['plugin_wkhtmltopdf_margin_' . $key])) {
                $values['plugin_wkhtmltopdf_margin_' . $key] = $this->rp->vars['plugin_wkhtmltopdf_margin_' . $key];

            } else {
                $values['plugin_wkhtmltopdf_margin_' . $key] = $stored_settings['plugin_wkhtmltopdf_margin_' . $key];
            }
        }

        $obj->set($values);

        foreach ($keys as $key) {
            $has_param = $obj->get('plugin_export_' . $key);
            if ($has_param) {
                $options['config']['settings'][$key] = $obj->get(sprintf('plugin_export_%s_tmpl', $key));
            }
        }

        $view = $controller->getView($obj, $manager, 'SettingViewPluginExportTest_popup', $options);

        return $view;
    }


    function getSharePopup($obj, $manager, $controller) {

        $popup = 'item_share_link';
        $view = 'SettingViewSortableItems';
        $post_data = array();

        // seems we do need this sort order block
        // sort order
        if(isset($this->rp->sort)) {
        //     $items = $manager->getSettings(100, $popup);
        //     $items = unserialize($items);
        // 
        //     // visible
        //     $visible_items = array();
        //     foreach ($this->rp->active_id as $v) {
        //         $parts = explode('_', $line_num);
        //         $line_num = $v[0];
        // 
        //         $group_key = (isset($v[1])) ? 'inactive' : 'active';
        //         $visible_items[] = $items[$group_key][$line_num];
        // 
        //         if ($group_key == 'inactive') {
        //             unset($items[$group_key][$line_num]);
        //         }
        //     }
        // 
        //     $items['active'] = array_values($visible_items);
        //     $items['inactive'] = array_values($items['inactive']);
        //     $items = serialize($items);
        // 
        //     $setting_id = $manager->getSettingIdByKey($popup);
        //     $manager->setSettings(array($setting_id => addslashes($items)));
        // 
        //     $controller->go();

        // add new
        } elseif (isset($this->rp->submit_detail)) {
            $post_data = $this->rp->vars;
            $is_error = false;

            if (!empty($_FILES['icon_1']['name'])) {
                $upload = $manager->getUploader();
                $upload->setMaxSize(5);
                $upload->setAllowedExtension('svg');

                $upload = $manager->upload($upload);

                if(!empty($upload['error_msg'])) {
                    $is_error = true;
                    $obj->errors['formatted'][]['msg'] = $upload['error_msg'];
                }
            }

            if(strpos($this->rp->url, '[url]') === false) {
                $is_error = true;

                $msgs = AppMsg::getMsgs('error_msg.ini');
                $msg['body'] = $msgs['share_url_format_msg'];
                $obj->errors['formatted'][]['msg'] = BoxMsg::factory('error', $msg);
            }

            $controller->setMoreParams('popup');
            $controller->setMoreParams('detail');

            $view = 'SettingViewSocialSites_detail_popup';

            if (!$is_error) {
                $items = $manager->getSettings(100, $popup);
                $items = unserialize($items);

                if (!empty($this->rp->id)) { // update

                    $group_key = $controller->getMoreParam('group');
                    $line_num = $controller->getMoreParam('line');

                    $item = $items[$group_key][$line_num];

                    $icon = $item['icon'];
                    if (!empty($upload['good'])) {
                        $icon = FileUtil::read($upload['filename']);
                    }

                    $items[$group_key][$line_num] = array(
                        'id' => $this->rp->id,
                        'title' => $this->rp->title,
                        'url' => $this->rp->url,
                        'icon' => $icon
                    );

                } else { // new
                    $id = md5(time() . count($items));

                    $icon = '';
                    if (!empty($upload['good'])) {
                        $icon = FileUtil::read($upload['filename']);
                    }

                    $items['active'][] = array(
                        'id' => $id,
                        'title' => $this->rp->title,
                        'url' => $this->rp->url,
                        'icon' => $icon
                    );
                }

                $items['active'] = array_values($items['active']);

                $items = serialize($items);

                $setting_id = $manager->getSettingIdByKey($popup);
                $manager->setSettings(array($setting_id => addslashes($items)));

                $_GET['saved'] = 1;
                $controller->setMoreParams('saved');

                $controller->go('success', true);
            }

        } elseif(!empty($this->rq->detail)) {
            $view = 'SettingViewSocialSites_detail_popup';

        } elseif(!empty($this->rq->default)) {
            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setDefaultValues($setting_id, false);

            $controller->go();
        }

        $data = $manager->getSettings(100, $popup);
        //$this->rp->stripVarsValues($data);
        $obj->set(array($popup => $data));
        
        // for list 
        $post_data['module_id'] = 100;
        $post_data['add_button'] = 1;
        foreach(SettingData::$sharing_sites as $k => $v) {
            $post_data['titles'][$k] = $v['title'];
        }

        $view = $controller->getView($obj, $manager, $view, $post_data);
        return $view;
    }


    function getPageDesignPopup($obj, $manager, $controller) {
        
        $data = $manager->getSettings(2, $popup);
        //$this->rp->stripVarsValues($data);
        $obj->set(array('page_design' => $data));

        

        $manager = new PageDesignModel;
        $manager->sm->module_id = 11;

        switch ($this->rq->action) {
            case 'setting': // ------------------------------
                $view = $controller->getView($obj, $manager, 'PageDesignView_setting_form');

                break;

            case 'custom_block': // ------------------------------
                $b_obj = new PageDesignCustomBlock;

                if (isset($rp->submit)) {
                    $is_error = $b_obj->validate($rp->vars);

                    if($is_error) {
                        $rp->stripVars(true);
                        $b_obj->set($rp->vars);

                    } else {
                        $rp->stripVars();
                        $b_obj->set($rp->vars);

                        $b_obj->set('data_string', addslashes(serialize($rp->vars)));

                        $block_id = $manager->save($b_obj, $controller->action);
                        $_GET['block_id'] = $block_id;

                        $controller->setMoreParams('action');
                        $controller->setMoreParams('block_id');

                        $controller->go('success', true);
                    }
                }

                if (!empty($rq->id)) {
                    $data = $manager->getById($rq->id);

                    //$rp->stripVarsValues($data, array('data_string'));
                    $b_obj->set($data);
                }

                $view = $controller->getView($b_obj, $manager, 'PageDesignCustomBlockView_form');
                break;


            case 'update': // ------------------------------
                if (isset($rp->set_default)) {
                    $setting_id = $manager->sm->getSettingIdByKey($rq->key);
                    $manager->sm->setDefaultValues($setting_id);

                    $setting_id = $manager->sm->getSettingIdByKey($rq->key . '_html');
                    $manager->sm->setDefaultValues($setting_id);

                    $setting_id = $manager->sm->getSettingIdByKey($rq->key . '_menu');
                    if (!is_null($setting_id)) {
                        $manager->sm->setDefaultValues($setting_id);
                    }

                    $controller->goPage('this', 'this', 'this', 'this', array('key' => $rq->key));
                }

                
                $view = new PageDesignView_form();      
                break;


            default: // ------------------------------------
                
                $view = new PageDesignView_list;

                return $view->execute($obj, $manager, $values);
        }
    }


    // we may need to make such popus smarter and not duplicate code 
    // almost the same code as on normal form, non popup
    function getAwsS3Popup($obj, $manager, $controller) {
        
        $manager->module_id = 12; // hidden, popup setting
        // $manager->loadParser();
        
        if(isset($this->rp->submit) || isset($this->rp->submit1)) {
    
            if(APP_DEMO_MODE) {
                $controller->go('not_allowed_demo', true);
            }
            
            $values = $obj->prepareValues($this->rp->values, $manager);
            $is_error = $obj->validate($values, $manager);
            
            if(!$is_error) {
                $v = new Validator($_POST);
                $v->csrf();
                $is_error = $obj->errors = $v->getErrors();
            }
        
            if($is_error) {
                
                $this->rp->stripVarsValues($values, true);
                $obj->set($values);
            
            } else {
                
                $old_values = &$manager->getSettings();
                $this->rp->stripVarsValues($old_values);
                
                $values['aws_secret_key'] = \EncryptedPassword::encode($values['aws_secret_key']);
                $this->rp->stripVarsValues($values, false);
                $manager->save($values);
                
                $aws_s3_allow = (!empty($values['aws_s3_allow'])) ? 1 : 0;
                $setting_id = $manager->getSettingIdByKey('aws_s3_allow2');
                $edata = array($setting_id => array($setting_id, $aws_s3_allow));
                $manager->saveQuery($edata);
                
                $_GET['saved'] = 1;
                $_GET['tkey'] = 1;
                $controller->setMoreParams('saved');
                // $controller->setMoreParams('popup');
                $controller->setMoreParams('tkey');
            
                $controller->go();
            }
        
        } else {
            
            $data = $manager->getSettings();
            $data['aws_secret_key'] = \EncryptedPassword::decode($data['aws_secret_key']);
            $this->rp->stripVarsValues($data);
            $obj->set($data);
        }
        
        $options = [
            'submit_tmpl' => 'form_submit_simple.html',
            'default_btn' => false
        ];
        
        $view = $controller->getView($obj, $manager, 'SettingView_form', $options);
        return $view;
    }
    

    function getSearchFilterItemPopup($obj, $manager, $controller) {

        $popup = 'search_filter_item';
        $view = 'SettingViewSortableItems';
        $post_data = array();

        if(!empty($this->rq->default)) {
            $setting_id = $manager->getSettingIdByKey($popup);
            $manager->setDefaultValues($setting_id, false);
            $controller->go();
        }

        $data = $manager->getSettings(2, $popup);
        //$this->rp->stripVarsValues($data);
        $obj->set(array($popup => $data));

        // for list 
        $msg = AppMsg::getMsg('public/client_msg.ini');
        $post_data['module_id'] = 2;
        $post_data['add_button'] = 0;
        $post_data['titles'] = [
            'cat' => $msg['category_msg'],
            'entry_type' => $msg['entry_type_msg'],
            'custom' => $msg['custom_fields_title_msg']
        ];

        $view = $controller->getView($obj, $manager, $view, $post_data);
        return $view;
    }
    
    
    function getSettingViewWizard($obj, $manager, $controller) {
        
        $wizard_group_id = 1;
        if(!empty($this->rq->group) && $ret = array_search($this->rq->group, $manager->wizard_groups)) {
            $wizard_group_id = $ret;
        }
        
        $manager->wizard_group_id = $wizard_group_id;
        
        $view_class = 'SettingViewWizard';
        
        if(isset($this->rp->submit)) {
            
            // TODO hardcoded smtp password, need to change 
            if(isset($this->rp->values[46])) {
                $this->rp->values[46] = EncryptedPassword::encode($this->rp->values[46]);
            }
            
            // TODO we do not have email sent validator here 
            // ../email_setting/SettingValidator.php -> if(!AppController::isAjaxCall()) {
            
            // $manager->setModuleId('email_setting');
            // $manager->loadParser(false, 'email_setting');
            // $values = $obj->prepareValues($rp->values, $manager);
            // $is_error = $obj->validate($values, $manager);
            // echo '<pre>', print_r($values,1), '<pre>';
            // echo '<pre>', print_r($is_error,1), '<pre>';
            // echo '<pre>', print_r($rp->values,1), '<pre>';
            // exit;
            
            $this->rp->stripVarsValues($this->rp->values, false);
            $manager->setSettings($this->rp->values);
            
            $next_group = $manager->wizard_groups[$wizard_group_id + 1];
            
            $more = array(
                'group' => $next_group, 
                'popup' => $controller->getMoreParam('popup')
            ); 
            $controller->goPage('this', 'this', false, false, $more);
            
        } else {
            
            if (!$controller->getMoreParam('popup') && empty($this->rq->group) && !isset($this->rq->ajax)) {
                $view_class = 'SettingViewWizard_start';
            }
            
            $module_ids = $manager->getCommonGroupModules($wizard_group_id);
            
            if (!empty($module_ids)) {
                $data = $manager->getSettings($module_ids);
                
                // TODO hardcoded smtp password, need to change 
                if(isset($data[46])) {
                    $data[46] = EncryptedPassword::decode($data[46]);
                }     
                
                $this->rp->stripVarsValues($data);
                $obj->set($data);
            }
        }
        
        $view = $controller->getView($obj, $manager, $view_class);
        return $view;
    }

}

?>