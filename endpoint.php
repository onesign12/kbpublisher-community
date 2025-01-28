<?php
$app_dir = str_replace('\\', '/', getcwd()) . '/admin/';     // trying to guess admin directory
//$app_dir = '/path_to/kb/admin/';                            // set it manually


require_once $app_dir . 'config.inc.php';
$conf['use_ob_gzhandler'] = false;
$conf['ssl_admin'] = 0; // not to be redirected for https

require_once $app_dir . 'config_more.inc.php';
require_once $app_dir . 'common.inc.php';

@session_name($conf['session_name']);
session_start();


$priv = Auth::factory('Priv');
$reg->setEntry('priv', $priv);

$controller = new AppController();
$controller->setWorkingDir();
$reg->setEntry('controller', $controller);


$type = (isset($_GET['type'])) ? $_GET['type'] : false;
switch ($type) {

    // case 'search_category':
    //
    //     $more = array('mode' => 'search');
    //     $article_cat_link = $controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'category', $more);
    //     $file_cat_link = $controller->getAdminRefLink('file', 'file_entry', false, 'category', $more);
    //
    //     break;


    case 'image':

        if(empty($_GET['id'])) {
            exit;
        }

        $id = (int) $_GET['id'];
        
        $manager = new StuffEntryModel;
        $manager->sendFileInline($id);
        break;


    case 'suggest':

        if(empty($_GET['term'])) {
            exit;
        }
        
        $term = addslashes($_GET['term']);
        $term_raw = $_GET['term'];

        $limit = 10;

        $manager = new SearchSuggestModel;
        $suggestions = $manager->getSearchSuggestions($term, $limit);

        $diff = $limit - count($suggestions);

        if ($diff > 0 && _strlen($term) >= SpellSuggest::$min_length) {
            $settings = SettingModel::getQuick(2);

            $source = $settings['search_spell_suggest'];
            $custom_words = $settings['search_spell_custom'];
            $custom_words = explode(' ', $custom_words);

            if ($source == 'enchant') {
                $provider = $settings['search_spell_enchant_provider'];
                $dictionary = $settings['search_spell_enchant_dic'];
                
                $spell = SpellSuggest::factory($source);
                $spell_suggestions = $spell::suggest($provider, $dictionary, $custom_words, $term_raw);

            } elseif ($source == 'pspell') {
                $dictionary = $settings['search_spell_pspell_dic'];
                
                $spell = SpellSuggest::factory($source);
                $spell_suggestions = $spell::suggest($dictionary, $custom_words, $term_raw);

            } elseif ($source == 'bing') {
                $key = $settings['search_spell_bing_autosuggest_key'];
                if ($key) {
                    $url = $settings['search_spell_bing_autosuggest_url'];

                    $spell = SpellSuggest::factory($source);
                    $spell_suggestions = $spell::suggest($key, $url, $term_raw, $custom_words);
                    
                }
            }

            if (!empty($spell_suggestions)) {
                $suggestions += $spell_suggestions;
                $suggestions = array_slice($suggestions, 0, $limit);
            }

            /*if (!empty($suggestions)) {
                $suggestion = key($suggestions);
                $second_suggestions = $manager->getSearchSuggestions($suggestion, 10);
            }*/
        }

        $js_values = array();
        foreach ($suggestions as $msg => $score) {
            $js_values[] = '"' . str_replace('"', '\"', $msg) . '"';
        }

        $json = '[%s]';
        echo sprintf($json, implode(',', $js_values));
        break;


    case 'suggest_admin':
        
        if(!$priv->getPrivId() && $priv->isAuth()) {
            exit;
        }

        if (!SphinxModel::isSphinxOn()) {
            exit;
        }
        
        if(empty($_GET['term'])) {
            exit;
        }

        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        $conf['debug_sphinx_sql'] = 0;

        $manager = new SearchSuggestAdminModel(false);

        $items = 10;
        $limit = 100;

        $term = addslashes($_GET['term']);

        $emanager = new CommonEntryModel;
        $emanager->role_manager = new RoleModel;
        $emanager->user_id = $priv->getUserId();
        $emanager->user_priv_id = $priv->getPrivId();
        $emanager->user_role_id = $priv->getRoleId();

        $entry_types = $manager->getEntryTypeSelectRange();
        
        // 7 => 'article_draft', 8 => 'file_draft', 
        if(!AppPlugin::isPlugin('draft')) {
            unset($entry_types[7], $entry_types[8]);
        }
        
        $entry_types_icons = array(
            1 => 'images/sidebar/knowledgebase.svg',
            2 => 'images/sidebar/file.svg',
            3 => 'images/sidebar/news.svg',
            7 => 'images/icons/draft.svg',
            8 => 'images/icons/draft.svg',
            10 => 'images/sidebar/users.svg',
            20 => 'images/icons/email.svg'
        );

        $entry_types_to_model = array(
            1 => array('KBEntryModel', 'main'),
            2 => array('FileEntryModel', 'main'),
            7 => array('KBDraftModel', 'all'),
            8 => array('FileDraftModel', 'all'),
        );


        $etypes = array();

        foreach ($entry_types as $entry_type => $msg) {
            $module = $manager->entry_type_to_url[$entry_type][0];
            $page = $manager->entry_type_to_url[$entry_type][1];
            $priv_area = $page;
            // $priv_area = $priv->getPrivArea($module, $page);

            if ($priv->isPriv('select', $priv_area)) {
                
                // entry types
                $etypes[] = $entry_type;

                // select own entries only
                $emanager->entry_type = $entry_type;
                $manager->setOwnParams($emanager, $priv, $priv_area);

                // categories
                if(isset($entry_types_to_model[$entry_type])) {
                    list($class, $mode) = $entry_types_to_model[$entry_type];
                    $entry_manager = new $class;
                    $manager->setCategoryRolesParams($entry_manager, $mode);
                }
            }
        }

        if (empty($etypes)) {
            exit;
        }

        // select allowed types only
        $manager->setSourceParams($etypes);

        // select allowed entries only, entry roles
        $manager->setEntryRolesParams($emanager, 'write');

        $suggestions = $manager->getSearchSuggestions($term, $limit);
        if (empty($suggestions)) {
            exit;
        }

        $entry_type_num = array();
        $picked_items = array();
        foreach ($suggestions as $suggestion) {
            if (!isset($entry_type_num[$suggestion['entry_type']])) {
                $entry_type_num[$suggestion['entry_type']] = 0;
                $picked_items[$suggestion['entry_type']] = array();
            }

            $entry_type_num[$suggestion['entry_type']] ++;
        }

        $limit_per_entry_type = floor($items / count($entry_type_num));

        $limits = array();
        foreach ($entry_type_num as $entry_type => $num) {
            if ($num < $limit_per_entry_type) {
                $limits[$entry_type] = $num;

            } else {
                $limits[$entry_type] = $limit_per_entry_type;
            }
        }

        $additional_items_num = $items - array_sum($limits);

        $data = array();
        foreach ($suggestions as $suggestion) {
            $entry_type = $suggestion['entry_type'];

            $module = $manager->entry_type_to_url[$entry_type][0];
            $page = $manager->entry_type_to_url[$entry_type][1];
            $link = 'index.php?' . $controller->getShortLink($module, $page, false, false, array('filter[q]' => 'id:' . $suggestion['entry_id']));
            $link = $controller->_replaceArgSeparator($link);

            $section_link = 'index.php?' . $controller->getShortLink($module, $page, false, false, array('filter[q]' => '' . $term));
            $section_link = $controller->_replaceArgSeparator($section_link);

            $item = array(
                'label' => $suggestion['title'],
                'value' => $link,
                'icon' => $entry_types_icons[$suggestion['entry_type']],
                'entry_type' => $entry_types[$suggestion['entry_type']],
                'section_link' => $section_link
            );

            if (count($picked_items[$suggestion['entry_type']]) < $limits[$suggestion['entry_type']]) {
                $picked_items[$suggestion['entry_type']][] = $item;

            } elseif ($additional_items_num > 0) {
                $picked_items[$suggestion['entry_type']][] = $item;
                $additional_items_num --;
            }
        }

        foreach ($picked_items as $k => $v) {
            foreach ($v as $v1) {
                $data[] = $v1;
            }
        }

        echo json_encode($data);
        break;


    case 'suggest_tag':
    
        if(empty($_GET['term'])) {
            exit;
        }

        $term = addslashes($_GET['term']);
        $manager = new TagModel;
        $manager->setSqlParams("AND title LIKE '$term%'");
        $manager->setSqlParamsOrder('ORDER BY title');

        $suggest = $manager->getRecords(10);

        $data = array();
        foreach ($suggest as $v) {
            $data[] = array('id' => $v['id'], 'label' => $v['title'], 'value' => $v['title']);
        }

        echo json_encode($data);
        break;


    case 'suggest_user':
    
        if(empty($_GET['term'])) {
            exit;
        }
            
        $term = addslashes($_GET['term']);
        $manager = new UserModel;
        $manager->setSqlParams("AND email LIKE '$term%' OR first_name LIKE '$term%' OR last_name LIKE '$term%'");
        $manager->setSqlParamsOrder('ORDER BY email');

        $suggest = $manager->getRecords(10);

        $item_str = '<div style="padding: 5px;"><div>%s</b></div><div style="font-size: 0.9em;color: #999999;">%s</div></div>';

        $data = array();
        foreach ($suggest as $v) {
            //$label = (empty($_GET['no_email'])) ? sprintf('<%s>', $v['email']) : '';

            $name = '';
            if ($v['first_name']) {
                $name = sprintf('%s %s', $v['first_name'], $v['last_name']);
            }

            $label = sprintf($item_str, $name, $v['email']);

            $data[] = array(
                'id' => $v['id'],
                'label' => $label,
                'value' => $name,
                'tooltip' => $v['email']
            );
        }

        echo json_encode($data);
        break;


    case 'ck_upload':

        $msg = AppMsg::getErrorMsgs();

        $html_editor_upload_dir = SettingModel::getQuick(1, 'html_editor_upload_dir');
        $html_editor_upload_dir .= 'image/';

        $kbp_root = str_replace('\\', '/', strtolower($_SERVER['DOCUMENT_ROOT']));
        $kbp_upload = str_replace('\\', '/', strtolower($html_editor_upload_dir));
        $kbp_upload_dir = str_replace($kbp_root, '/', $kbp_upload);
        $kbp_upload_dir = str_replace('//', '/', $kbp_upload_dir);


        $upload = new Uploader;
        $upload->store_in_db = false;

        $upload->setAllowedExtension('jpg', 'png', 'gif', 'bmp', 'jpeg');
        $upload->setUploadedDir($html_editor_upload_dir);
        $upload->setMaxSize(WebUtil::getIniSize('upload_max_filesize')/1024);

        $error_str = '%s: %s';

        // upload_max_filesize, post_max_size in action
        if (empty($_FILES)) {
            $error_msg = sprintf($error_str, $msg['uploaded_error_msg'], $msg['big_size_msg']);
            echo $error_msg;
            break;
        }

        $f = $upload->upload($_FILES);

        if(isset($f['bad'])) { // upload failed
            foreach($f['bad'] as $k => $v) {
                $error_msg = sprintf($error_str, $msg['uploaded_error_msg'], $msg[$k . '_msg']);
            }
            echo $error_msg;
        } else {
            echo 'Image:' . $kbp_upload_dir . $f['good']['pfile']['name'];
        }

        break;


    case 'acs': // processes SAML responses (both test and real)

        $return_url = @$_POST['RelayState'];

        if ($return_url != 'debug' && !AuthProvider::isSamlAuth()) {
            die('SAML is disabled');
        }
        
        AuthProvider::loadSaml();

        $controller->working_dir .= 'setting/setting/';
        

        $view = new SettingViewSamlDebug_popup;

        try {

            $settings = AuthProvider::getSettings();

            if($return_url == 'debug') {
                $auth_setting = $_SESSION['saml_settings_'];

                $stored_settings_keys = array(
                    'saml_map_group_to_priv', 'saml_map_group_to_role',
                    'saml_idp_certificate', 'saml_sp_certificate',
                    'saml_sp_private_key'
                );

                foreach ($stored_settings_keys as $key) {
                    $auth_setting[$key] = $settings[$key];
                }

            } else {
                $auth_setting = $settings;
            }

            $auth = new AuthSaml($auth_setting);
            $auth->setCheckIp($conf['auth_check_ip']);
            $saml_settings = $auth->getSettings();
                        
            $ol_auth = new OneLogin_Saml2_Auth($saml_settings);
            $ol_auth->processResponse();

            $errors = $ol_auth->getErrors();
            if (!empty($errors)) {
                throw new Exception($ol_auth->getLastErrorReason());
            }

            if (!$ol_auth->isAuthenticated()) {
                exit;
            }

            $attributes = $ol_auth->getAttributes();
            $name_id = $ol_auth->getNameId();
            $session_index = $ol_auth->getSessionIndex();

            $user = $auth->getUserMapped($name_id, $attributes);

        } catch (Exception $e) {

            if ($return_url == 'debug') {
                $error_msg = $e->getMessage();
                $debug_msg = array('Object OneLogin_Saml2_Auth' => get_object_vars($ol_auth));
                echo $view->getErrorPage($error_msg, $debug_msg);

            } else { // redirecting back to the form
                $cc = $controller->getClientController();
                $return_url = $cc->getLink('login', false, false, 'sso_error');

                $log = new LoggerModel;
                $log->putLogin('Skipping... Error: ' . $e->getMessage());
                //$log->writeLoginLogFile();
                $log->addLogin(0, '', AuthProvider::getAuthType(), 2);

				$cc->goUrl($return_url);
            }

            exit;
        }

        if ($return_url == 'debug') {
            $slo_enabled = (!empty($auth_setting['saml_slo_endpoint']));
            echo $view->getLoginPage($attributes, $name_id, $user, $slo_enabled);

            $_SESSION['saml_settings_']['name_id'] = $name_id;

        } elseif (!$return_url) { // test response, but no initial request

            $cc = $controller->getClientController();
            $return_url = $cc->getLink('login', false, false, 'sso_error');

            $log = new LoggerModel;
            $log->putLogin('Skipping... missing return url, RelayState could be missed or empty.');
            //$log->writeLoginLogFile();
            $log->addLogin(0, '', AuthProvider::getAuthType(), 2);

            $cc->goUrl($return_url);

        } else { // real

            $log = new LoggerModel;
            $log->putLogin('Initializing...');

            $auth->log = &$log;

            $settings = SettingModel::getQuick(2);
            $cc = $controller->getClientController($settings);
            
            try {
                $ret = $auth->doAuthSaml($user);


            // mfa required, will not executed if set no mfa in AuthSaml::doAuthSaml
            } catch(AuthPrivException $e) {
        
                $url = $cc->getLink('mfa');
                if($cc->admin_login) { // not implemented here 
                    $url =  $link = APP_ADMIN_PATH . 'login.php?View=mfa';
                }
                
                //$log->putLogin('MFA required...');
                
                $values = [
                    'auth_type' => 'saml',
                    'username'  => $user['username'],
                    'log'       => serialize($log->_log['_login'])
                ];
                MfaAuthenticator::setSession($values);

                $cc->goUrl($url);

            } catch(Exception $e) {

                $error_code = $e->getCode();

                if ($return_url == 'debug') {
                    echo $view->getErrorPage($e->getMessage());
                    exit;
                }

                // email exists, go to login to merge account
                if ($error_code == AuthPriv::EMAIL_EXISTS_ERROR) {
                    $return_url = $cc->getLink('sso', false, false, 'merge');

                // no required params
                } elseif ($error_code == AuthPriv::WRONG_REMOTE_PARAMS_ERROR) {
                    $return_url = $cc->getLink('login', false, false, 'rpfailed');

                // other error
                } else {
                    $return_url = $cc->getLink('login', false, false, 'sso_error');
                }

                $cc->goUrl($return_url);
                exit;
            }

            if($ret) {  // executes on success and no mfa
                $auth->postAuth();
                $exitcode = 1;
            } else {
                $log->putLogin(sprintf('Login failed. (Username: %s)', $user['username']));
                $exitcode = 2;
            }

            $auth->logAuth($user, $exitcode, 'saml');

            $return_url = 'index.php';
            if(!empty($_REQUEST['RelayState'])) { // August 14, 2020
                $_url = urldecode($_REQUEST['RelayState']);
                if (filter_var($_url, FILTER_VALIDATE_URL) !== FALSE) {
                    $return_url = $_url;
                }
            }
            
            header("Location: {$return_url}");
            exit;

        }

        exit;

        break;


    case 'sls': // processes SAML logout requests and logout responses

        $return_url = @$_REQUEST['RelayState'];

        if ($return_url != 'debug' && !AuthProvider::isSamlAuth()) {
            die('SAML is disabled');
        }

        $controller->working_dir .= 'setting/setting/';
        

        $view = new SettingViewSamlDebug_popup;

        AuthProvider::loadSaml();

        // https://github.com/onelogin/php-saml/issues/131
        if (!empty($_POST['SAMLResponse'])) {
            $_GET['SAMLResponse'] = $_POST['SAMLResponse'];
        }

        if (!empty($_POST['SAMLRequest'])) {
            $_GET['SAMLResponse'] = $_POST['SAMLRequest'];
        }

        try {
            $auth_setting = AuthProvider::getSettings();

            $auth = new AuthSaml($auth_setting);
            $saml_settings = $auth->getSettings();

            $ol_auth = new OneLogin_Saml2_Auth($saml_settings);

            if ($return_url == 'debug') {
                $ol_auth->processSLO(true);

            } else {
                $callback = function() {
                    AuthPriv::logout();
                };

                $ol_auth->processSLO(false, null, false, $callback);
            }

            $errors = $ol_auth->getErrors();
            if (!empty($errors)) {
                throw new Exception($ol_auth->getLastErrorReason());
            }

        } catch (Exception $e) {
            
            if ($return_url == 'debug') {
                echo $view->getErrorPage($e->getMessage());

            } else { // no actions

            }

            exit;
        }

        if (!empty($return_url)) {
            if ($return_url == 'debug') {
                echo $view->getLogoutPage();

                unset($_SESSION['saml_settings_']);

            } else {
                header('Location: ' . $return_url);
            }

            exit;
        }

        break;


/*
    case 'svg':
        

        $svg_path = '%sclient/images/icons/%s.svg';
        $svg_path = sprintf($svg_path, APP_CLIENT_DIR, $_GET['name']);
        $svg_data = FileUtil::read($svg_path);

        $svg_data = str_replace('fill="#fff"', sprintf('fill="#%s"', $_GET['color']), $svg_data);

        header('Content-type: image/svg+xml');
        echo $svg_data;

        exit;

        break;*/


    case 'user_info':
        $data = array();
        if (AuthPriv::getUserId()) {
            $data['user_id'] = AuthPriv::getUserId();
        }

        echo json_encode($data);
        exit;

        break;


    case 'google':
    case 'facebook':
    case 'twitter':
    case 'vk':
    case 'yandex':

		$credentials = array();
		if (AuthSocial::isTest()) {
			$credentials = $_SESSION['social_auth_debug_'];
			$_SESSION['social_auth_debug_'] = array();
			unset($_SESSION['social_auth_debug_']);
		}

        $auth = AuthSocial::factory($type, $credentials);

        try {
            $access_token = $auth->getAccessToken();
            $user_info = $auth->getUserInfo($access_token);

            if ($auth->isTest()) {

				$user_mapped = $auth->getUserMapped($user_info);
                if ($auth->isIncompleteRemoteUser($user_mapped)) {
                	$msg =  'Error: Incomplete responce, returned values: ' . '<pre>' . print_r($user_info,1) . '<pre>';
                	$msg .=  'mapped values: ' . '<pre>' . print_r($user_mapped,1) . '<pre>';
                    throw new Exception($msg);
                }

                $controller->working_dir .= 'setting/setting/';

                $view = new SettingViewSocialDebug_popup;
                echo $view->getLoginPage($user_info, $user_mapped);
                exit;
            }

            $log = new LoggerModel;

            $log->putLogin('Initializing...');
            $log->putLogin('Social Login Provider: ' . $type);

            $auth->log = &$log;

            $user = $auth->getUserMapped($user_info);
            $ret = $auth->doAuthSocial($user);

            if($ret) {
                $exitcode = 1;
                $log->putLogin('Login successful');
                UserActivityLog::add('user', 'login');
            } else {
                $exitcode = 2;
                $log->putLogin('Login failed');
            }

            $user_id = (AuthPriv::getUserId()) ? AuthPriv::getUserId() : 0;
            $username = addslashes($user['email']);
            $auth_type = 'social';

            $log->addLogin($user_id, $username, $auth_type, $exitcode);

            header('Location: index.php');


        } catch(Exception $e) {

            if ($auth->isTest()) {
                $msgs['body'] = $e->getMessage();
                echo BoxMsg::factory('error', $msgs, array(), array('page' => true));
                exit;
            }

            $cc = $controller->getClientController();
            $error_code = $e->getCode();

            // no email in social account
            if ($error_code == AuthSocial::NO_EMAIL_ERROR) {
                $return_url = $cc->getLink('sso', fasle, fasle, 'incomplete');

            // email exists, go to login to merge account
            } elseif ($error_code == AuthSocial::EMAIL_EXISTS_ERROR) {
                $return_url = $cc->getLink('sso', false, false, 'merge');

            // no required params
            } elseif ($error_code == AuthSocial::WRONG_REMOTE_PARAMS_ERROR) {
                $return_url = $cc->getLink('login', false, false, 'rpfailed');

            // other error
            } else {
                $return_url = $cc->getLink('login', false, false, 'sso_error');
            }

            $cc->goUrl($return_url);
        }

        exit;

        break;


    default:
        die('Wrong usage');
}
?>