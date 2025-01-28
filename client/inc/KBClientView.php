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


class KBClientView extends BaseView
{

    var $working_dir;
    var $template_dir;
    var $template_view_dir;
    var $template;
    var $errors = array();
    var $form_data = array();

    var $msg = array();
    var $controller;
    var $css = array();

    var $meta_title;
    var $meta_keywords;
    var $meta_description;
    var $meta_robots = true;

    var $category_key;
    var $category_id;
    var $entry_key;
    var $entry_id;
    var $view_key;

    var $home_link = false;             // use home link n navigation as link
    var $category_nav_generate = true;     // generate or not categories in navigation line
    var $settings = array();

    var $nav_delim = ' / ';
    var $display_categories = true;        // display form with categories and other staff with categories
                                        // later we count categories and set var in KBClientView::getTop

    var $top_parent_id = 0;
    var $extra_breadcrumbs = array();
    var $page_template = 'page.html';
    var $page_print = false; // whether to use page_print.html
    var $page_popup = false; // whether to use page_popup_client.html
    var $page_modal = false; // whether to use getPageInModal.html

    // to manipulate, share, mix templates
    var $own_format = 'all';
    var $default_format = 'default';
    var $view_template = array();        // ???
    var $no_header_templates = array();  // no headers templates


    var $files_views = array('files', 'file', 'download');
    var $news_views = array('news');

    var $parse_form = true;
    var $nav_title = false;
    var $ajax_call = false; // whether ajax was called or not
    var $ajax_post_action = false; // name of the js function to call when the ajax response is completed

    var $encoding;
    var $date_format;
    var $date_convert;
    var $action_msg_format = 'hint';


    function __construct() {

        $reg = &Registry::instance();
        $this->conf = &$reg->getEntry('conf');
        $this->controller = &$reg->getEntry('controller');
        // echo '<pre>' . print_r($this->controller->setting, 1) . '</pre>';
        $this->date_format = $this->controller->getSetting('date_format');
        $this->encoding = $this->conf['lang']['meta_charset'];
        $this->date_convert = $this->getDateConvertFrom($this->conf['lang']);

        $this->setUrlVars();

        $this->setTemplateDir($this->controller->getSetting('view_format'),
                             $this->controller->getSetting('view_template'));

        $this->setCommonStyleCss($this->controller->getSetting('view_format'));
        $this->setStyleCss($this->controller->getSetting('view_format'),
                           $this->controller->getSetting('view_template'),
                           $this->controller->getSetting('view_style'),
                           $this->controller->getSetting('view_header'));

        $this->setMsg();
        //$this->setSettings();

        if(isset($this->conf['lang']['week_start'])) {
            $this->week_start = $this->conf['lang']['week_start'];
            $reg->setEntry('week_start', $this->week_start);
        }
    }


    function setCustomSettings() {

    }


    function setSetting($key, $value) {
        $this->controller->setting[$key] = $value;
    }


    function setUrlVars() {

        //$this->category_key = &$this->controller->category_key;
        $this->category_id = $this->controller->category_id;

        //$this->entry_key = &$this->controller->entry_key;
        $this->entry_id = $this->controller->entry_id;

        //$this->view_key = &$this->controller->view_key;
        $this->view_id = $this->controller->view_id;
        
        //$this->page_key = &$this->controller->page_key;
        $this->page_id = $this->controller->page_id;

        //$this->msg_key = &$this->controller->msg_key;
        $this->msg_id = $this->controller->msg_id;
    }


    function setTemplateDir($format, $skin) {

        if($this->own_format == 'none') {
            $format = $this->default_format;
        } elseif($this->own_format != 'all') {
            $format = (in_array($this->view_id, $this->own_format)) ? $format : $this->default_format;
        }

        $this->template_dir = $this->getTemplateDir($format, $skin);
    }


    function getTemplateDir($format, $skin) {
        return $this->controller->skin_dir . 'view_' . $format . '/' . $skin . '/';
    }


    function getTemplate($template, $template_dir = false) {

        $format = $this->controller->getSetting('view_format');
        $skin = $this->controller->getSetting('view_template');
        $template_dir = ($template_dir) ? $template_dir : $this->template_dir;

        if(in_array($template, $this->view_template)) {
            $template_dir = $this->getTemplateDir($format, $skin);
        }

        // no header
        if(!$this->controller->getSetting('view_header')) {
            if(isset($this->no_header_templates[$template])) {
                $template = $this->no_header_templates[$template];
            }
        }

        return $template_dir . $template;
    }


    function setStyleCss($format, $skin, $style, $header) {

        $css = array();
        $css[] = $this->controller->skin_path . 'view_' . $format . '/' . $skin . '/default.css';

        if(!$header) {
            $css[] = $this->controller->skin_path . 'view_' . $format . '/' . $skin . '/' . $style . '_no_header.css';
        }

        if($style != 'default') {
            $css[] = $this->controller->skin_path . 'view_' . $format . '/' . $skin . '/' . $style . '.css';
        }

        if (!empty($this->conf['lang']['rtl'])) {
            $css[] = $this->controller->skin_path . 'rtl.css';
        }

        $this->style_css = &$css;
    }


    function setCommonStyleCss($format) {
        $css = array();
        $css['common_css'] = '%scommon.css?v=%s';
        $css['common_view_css'] = '%sview_' . $format . '/common_view.css?v=%s';
        $css['common_ie_css'] = '%scommon_ie.css?v=%s';
        $css['common_table_css'] = '%scommon_table.css?v=%s';
        $css['print_css'] = '%sprint.css?v=%s';
    
        foreach($css as $k => $v) {
            $this->css[$k] = sprintf($v, $this->controller->skin_path, $this->conf['product_hash']);
        }
    }


    function getMsgFile($file, $module = true) {
        return ($module) ? AppMsg::getModuleMsgFile($module, $file)
                         : AppMsg::getCommonMsgFile($file);
    }


    function setMsg() {
        // $file = $this->getMsgFile('share_msg.ini');
        // $this->msg = AppMsg::parseMsgs($file, false, false);

        // $file = $this->getMsgFile('client_msg.ini', 'public');
        // $this->msg = array_merge($this->msg, AppMsg::parseMsgs($file, false, false));
        $this->addMsg('share_msg.ini');
        $this->addMsg('client_msg.ini', 'public');
    }


    function addMsg($file, $module = false) {

        // always parse two files for user
        if($file == 'user_msg.ini') {
            $module = 'public';
        }

        $this->msg = array_merge($this->msg, AppMsg::getMsgs($file, $module));
    }

    
    function addMsgData($msg) {
        $this->msg = array_merge($this->msg, $msg);
    }


    function getFormatedDate($timestamp, $format = false) {

        $df = $this->date_format;
        if($format === false || $format === 'date') {
            $format = $df;
        } elseif($format === 'datetime') {
            $format = $df . ' ' . $this->conf['lang']['time_format'];
        }

        return $this->_getFormatedDate($timestamp, $format);
    }


    function getActionMsg($format = 'success', $msg_id = false, $strip = false, $replacements = array()) {

        $msg_id = ($msg_id) ? $msg_id : $this->msg_id;

        $this->controller->getView('success_go');
        if($f = KBClientView_success_go::getMsgId($msg_id, true)) {
            $format = $f;
        }

        if($msg_id) {
            

            $file = $this->getMsgFile('after_action_msg.ini', 'public');
            $msgs = AppMsg::parseMsgs($file, $msg_id, true);

            if($msgs) {
                if($strip) { $msgs = parent::stripVars($msgs, array(), $strip); }
                return BoxMsg::factory($format, $msgs, $replacements);
            }
        }
    }


    // true if commentable
    function isCommentable($manager, $var = true) {
        return ($manager->getSetting('allow_comments') && $var);
    }


    // true if ratingable
    function isRatingable($manager, $var = true) {
        return (bool) ($manager->getSetting('allow_rating') && $var);
    }
    
    
    function isSubscriptionAllowed($block, $manager) {
        $subsc_allowed = $manager->getSetting($block);

        // for users with priv only, false if logged and no priv
        if($subsc_allowed == 3 && $manager->is_registered && !AuthPriv::getPrivId()) {
            $subsc_allowed = false;
        }

        return $subsc_allowed;
    }


    // CAPTCHA // ----------
    
    static function getCaptchaManager($manager) {
        
        $type_to_class_map = array(
            0 => 'builtin', // to load someting if disabled
            1 => 'builtin',
            2 => 'recaptcha2',
            3 => 'recaptcha3',
            4 => 'recaptcha_corp'
        );    
        
        $type = $type_to_class_map[$manager->getSetting('captcha_type')];
        return CaptchaManager::factory($type);
    }

    
    static function useCaptcha($manager, $section, $username = false) {
        $captcha = self::getCaptchaManager($manager);
        return $captcha->useCaptcha($manager, $section, $username);
    }


    static function isCaptchaValid($manager, $values, $unset = true) {
        $captcha = self::getCaptchaManager($manager);
        return $captcha->isCaptchaValid($manager, $values, $unset);
    }

    
    static function getCaptchaBlock($manager, $section, $placeholder = false) {
        $captcha = self::getCaptchaManager($manager);
        return $captcha->getCaptchaBlock($section, $placeholder);
    }

    
    static function validateCaptcha($manager, $values, $section, $username = false) {
        $ret = array();
        $captcha = self::getCaptchaManager($manager);
        
        if($captcha->useCaptcha($manager, $section, $username)) {
            
            $unset = !AppAjax::isAjaxRequest();
            if(!$captcha->isCaptchaValid($manager, $values, $unset)) {
                
                if($captcha instanceof CaptchaManager_builtin) {
                    $error_msg = 'captcha_text_msg';
                    $type = 'key';
                } else {
                    $error_msg = AppMsg::getMsg('error_msg.ini', false, 'unavailable')['body'];
                    $type = 'custom';
                }
            
                $ret = array($error_msg, 'captcha', 'captcha', $type);
            }
        }
        
        return $ret;
    }

    // <---------


    function getPageByPageObj($class, $limit = false, $hidden = false, $action_page = false) {

        $msg = array(
            $this->msg['page_msg'],
            $this->msg['record_msg'],
            $this->msg['record_from_msg'],
            $this->msg['prev_msg'],
            $this->msg['next_msg']
        );

        $options['action_page'] = $action_page;
        $bp = PageByPage::factory('page', $limit, $hidden, $options);
        $bp->setMsg($msg);

        return $bp;
    }


    function pageByPage($limit, $sql, $action_page = false, $db_obj = false, $hidden = false) {

        if(!$action_page) {
            $action_page = $this->getLink($this->view_id, $this->category_id, $this->entry_id);
        }

        // then sql will be executed and we need db obj
        if(!$db_obj && !is_numeric($sql)) {
            $reg = &Registry::instance();
            $db_obj = &$reg->getEntry('db');
        }

        $bp = $this->getPageByPageObj('page', $limit, $hidden, $action_page);
        $bp->countAll($sql, $db_obj);

        return $bp;
    }


    // function pageByPageBottom($bp) {
    //     return PageByPage::factory('page', $bp);
    // }


    function stripVars($values, $html = array('body'), $to_display = 'display') {
        return parent::stripVars($values, $html, $to_display);
    }


    // no summary if private
    function getSummaryLimit($manager, $private, $limit = false) {
        $limit = ($limit === false) ? $manager->getSetting('preview_article_limit') : $limit;
        return ($this->isPrivateEntryLocked($manager->is_registered, $private)) ? 0 : $limit;
    }


    function _getItemImg($registered, $var, $category = false, $ext = false, $path = false, $icon_path = false) {

		$tag = '';
        $icon_path = ($icon_path === false) ? $this->controller->client_path . 'images/icons/' : $icon_path;

		// faq, faq2, book, related, attachments, etc
        if($category === 'list') {
			$file = sprintf("%sbullet.svg", $icon_path);
            $tag = '<img src="%s" alt="b" style="vertical-align: middle;" />';
            $tag = sprintf($tag, $file);

			if($this->isPrivateEntryLocked($registered, $var)) {
				$file = sprintf("%sbullet_red.svg", $icon_path);
	            $tag = '<img src="%s"  alt="private" title="%s" style="vertical-align: middle;" />';
	            $tag = sprintf($tag, $file, $this->msg['login_to_view_msg']);
			}

		// private
        } elseif($this->isPrivateEntryLocked($registered, $var)) {
			$margin = ($category === true) ? 0 : 3;
            $file = sprintf("%slock1.svg", $icon_path);
            $tag = '<img src="%s" alt="private" width="15" height="15" title="%s" style="margin-right: %dpx; vertical-align: middle;" />';
            $tag = sprintf($tag, $file, $this->msg['login_to_view_msg'], $margin);

        } elseif(in_array($category, array('news', 'file', 'article', 'attachment'), true)) {
			$file = sprintf('%s%s.svg', $icon_path, $category);
			$tag = sprintf('<img src="%s" alt="item" width="14" height="14" style="vertical-align: middle;" />', $file);

	    } elseif($category === 'rss') {
	       	$file = sprintf('%srss_feed_color.svg', $icon_path);
	       	$tag = sprintf('<img src="%s" alt="rss" width="14" height="14" />', $file);

		// category
        } elseif($category === true || $category === 'up') {
			$file = ($category === true) ? '%sfolder2.svg' : '%sfolder_up.svg';
            $file = sprintf($file, $icon_path);
            $tag = '<img src="%s" alt="folder" width="14" height="14" style="vertical-align: middle;" />';
            $tag = sprintf($tag, $file);

		} elseif($category)  {
			$file = sprintf('%s%s.svg', $icon_path, $category);
			$tag = sprintf('<img src="%s" alt="item" width="14" height="14" style="vertical-align: middle;" />', $file);
		}


        if($path && $file) {
			$tag = $file;
		}

        return $tag;
    }


    function getItemImgIcon($icon, $title = false) {

        $file = sprintf("%simages/icons/%s.svg", $this->controller->client_path, $icon);
        $tag = '<img src="%s" alt="%s" title="%s" width="14" height="14" style="vertical-align: middle;" />';
        $tag = sprintf($tag, $file, $file, $title);

        return $tag;
    }


    function _getRating($var) {
        if($var) {
            $tag = '<img src="%simages/rating/rate_star/rate_%s.gif" alt="rating" />';
            $rate = sprintf($tag, $this->controller->client_path, round($var));
        } else {
            $tag = '<img src="%simages/rating/rate_star/stars_norate_5_grey.gif" alt="rating" title="%s"/>';
            $rate = sprintf($tag, $this->controller->client_path, $this->msg['not_rated_msg']);
        }

        return $rate;
    }


    function getEntryPrefix($id, $type, $types, $manager) {

        $entry_id = '';
        $pattern = $manager->getSetting('entry_prefix_pattern');
        $padding = $manager->getSetting('entry_id_padding');

        if($pattern) {

            $type = ($type) ? $types[$type] : '';
            $pattern = explode('|', $pattern);

            if($padding) {
                $id = (is_numeric($padding)) ? sprintf("%'0".$padding."s", $id) : sprintf($padding, $id);
            }

            $replace = array('{$entry_id}' => $id, '{$entry_type}' => $type);

            if(isset($pattern[1])) {
                $entry_id = ($type) ? strtr(trim($pattern[1]), $replace) : strtr(trim($pattern[0]), $replace);
            } else {
                $entry_id = strtr(trim($pattern[0]), $replace);
            }

            $entry_id .= ' ';
        }

        return $entry_id;
    }


    function getRowClass() {
        static $i = 1;
        return ($i++ & 1) ? 'trDarker' : 'trLighter'; // rows colors
    }


    function setCrowlMsg($msg_key) {
        $this->controller->setCrowlMsg($msg_key);
    }


    function getLink($view = false, $category_id = false, $entry_id = false, $msg_key = false,
                        $more = array(), $more_rewrite = false) {

        return $this->controller->getLink($view, $category_id, $entry_id, $msg_key,
                                            $more, $more_rewrite);
    }


    function getMoreLink($view_id) {
        $str = '<a href="%s"><b>%s...</b></a>';

        if ($this->category_id) {
            $link = $this->getLink($view_id, $this->category_id);

        } else {
            $link = $this->getLink($view_id);
        }

        return sprintf($str, $link, $this->msg['more_msg']);
    }


    function getRssHeadLinks($manager) {

        $rss = false;
        $rss_setting = $manager->getSetting('rss_generate');
        if($rss_setting == 'none') {
            return $rss;
        }

        $rss_head = '<link rel="alternate" type="application/rss+xml" title="%s" href="%s" />';
        $rss_file = $this->controller->kb_path . 'rss.php';
        $rss_title = $manager->getSetting('rss_title');

        // all articles and  news
        $rss = array();
        $rss[] = sprintf($rss_head, $rss_title, $rss_file);
        if($manager->isModule('news')) {
            $title = sprintf('%s (%s)', $rss_title, $this->msg['news_title_msg']);
            $rss[] = sprintf($rss_head, $title, $rss_file. '?t=n');
        }

        $rss = implode("\n\t", $rss);
        return $rss;
    }


    function &getPageIn(&$manager) {

        $content = $this->execute($manager);

        $tpl = new tplTemplatez($this->getTemplate('page_in.html'));

        // need to print msg if any
        $msg_format = $this->getMsgFormat($this->msg_id);
        $tpl->tplAssign('msg', $this->getActionMsg($msg_format));
        
        $this->parseHeader($tpl, $manager);
        $this->parseCustomPageIn($tpl, $manager);

        $tpl->tplAssign('navigation', $this->_getFormatedNavigation($manager, $this->nav_title));


        $manage_menu = &$this->getManageMenuData($manager);
        
        $login_block = $this->getLoginBlock($manager, $manage_menu);
        $tpl->tplAssign('login_block_tmpl', $login_block);
        
		$login_block_mobile = $this->getLoginBlock($manager, $manage_menu, true);
        $tpl->tplAssign('login_block_mobile_tmpl', $login_block_mobile);
        
        $mobile_user_icon = ($manager->is_registered) ? 'logged' : 'not_logged';
        $tpl->tplSetNeeded('/' . $mobile_user_icon);
        
        
        // if to hide left menu
        $view_format = $manager->getSetting('view_format');        
        if($view_format == 'left') {
            $menu_type = $manager->getSetting('view_menu_type');
            if($menu_type == 'hide') {
                $this->parse_form = false;
            }
        }

        // top menu
        $kb_menu = &$this->getTopMenuData($manager);
        if($kb_menu) {
            $tpl->tplAssign('menu_top_tmpl', $this->getTopMenuBlock($kb_menu));
        }
        $tpl->tplAssign('menu_top_popup_tmpl', $this->getTopMenuBlock($kb_menu, true));
        
        // map link, browse by category 
        $mtype = (in_array($this->view_id, array('files'))) ? 'file' : 'article';
        $mlink = $this->getLink('map', false, false, false, array('type' => $mtype), 1);
		$tpl->tplAssign('map_link', $mlink);
        

        // modified 7 jun, 2016 to always parse form in default view and parse search in fixed
        if($view_format == 'left') {
            if($this->parse_form) {
                $this->parseForm($tpl, $manager);
                $tpl->tplAssign('menu_content_tmpl', $this->getLeftMenu($manager));

                $menu_display = 'block';
                $menu_link_display = 'none';
                if(isset($_COOKIE['kb_sidebar_width_']) && $_COOKIE['kb_sidebar_width_'] == 0) {
                    $menu_display = 'none';
                    $menu_link_display = 'inline';
                }

                $tpl->tplAssign('menu_display', $menu_display);
                $tpl->tplAssign('menu_link_display', $menu_link_display);
            }

        } elseif($view_format == 'fixed') {
			
            if($this->parse_form) {
                $this->parseForm($tpl, $manager);
                $tpl->tplAssign('menu_content_tmpl', $this->getLeftMenu($manager));
            }

            $sidebar_action_title = $this->msg['hide_sidebar_msg'];
            $next_action_title = $this->msg['show_sidebar_msg'];
            if(isset($_COOKIE['kb_sidebar_width_']) && $_COOKIE['kb_sidebar_width_'] == 1) {
                $sidebar_action_title = $this->msg['show_sidebar_msg'];
                $next_action_title = $this->msg['hide_sidebar_msg'];
            }

            $tpl->tplAssign('action_title', $sidebar_action_title);
            $tpl->tplAssign('next_action_title', $next_action_title);

        //default, browsable
        } else {
            
            // search filter only
            if($this->view_id == 'search' && isset($_GET['s'])) {
                $this->parseForm($tpl, $manager, true);
                $tpl->tplAssign('menu_content_tmpl', $this->getLeftMenu($manager));
            }
        }
        // -----------------
        
        $blocks = array();
        $blocks[] = $this->getSearchTopBlock($manager);
        
        if(AppPlugin::isPlugin('mustread')) {
            $blocks[] = MustreadPlugin::getMustreadBlockNote($manager, $this);
        }
        
        $blocks[] = '<div id="loading_spinner"><div></div></div>';
        
        // setcookie('kb_cookieconsent_', null, -1, '/'); // test it
        if(empty($_COOKIE['kb_cookieconsent_']) && $manager->getSetting('cookie_consent_banner')) {
            $blocks[] = $this->getCookieConsentBlock($manager);
        }
        
        $tpl->tplAssign('other_blocks', implode("\n\n", $blocks));
        

        $tpl->tplAssign('content', $content);
        $tpl->tplAssign('kb_path', $this->controller->kb_path);
        $tpl->tplAssign($this->msg);

        if(isset($_GET['q'])) {
            $tpl->tplAssign('q', $this->stripVars(trim($_GET['q']), array(), 'asdasdasda'));
        }


        if($manager->getSetting('rss_generate') != 'none') {
            $tpl->tplAssign('rss_link', $this->getLink('rssfeed'));
            $tpl->tplSetNeeded('/rss_block');
        }

        if(!AppPlugin::isPlugin('copyright')) {
            $str = $this->getCopyrightInfoMsg();
            $tpl->tplAssign('copyright_info', $str);
        }
        
        $dropdown_extra_class = ($manager->getSetting('view_format') == 'fixed') ? 'jq-dropdown-relative' : '';
        $tpl->tplAssign('dropdown_extra_class', $dropdown_extra_class);
        
        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }


    function &getPageInModal(&$manager) {

        $content = $this->execute($manager);

        $tpl = new tplTemplatez($this->getTemplate('page_in_modal.html'));

        // need to print msg if any
        $msg_format = $this->getMsgFormat($this->msg_id);
        if($amsg = $this->getActionMsg($msg_format)) {
            $tpl->tplAssign('msg', $amsg . '<br/>');
        }
        
        $this->parseHeader($tpl, $manager);
        

        $registered_only = ($manager->getSetting('kb_register_access'));
        if($this->view_id == 'login') {
            if(!$registered_only || $manager->is_registered) {
                $tpl->tplAssign('back_link', $this->getLink('index'));
                $tpl->tplSetNeeded('/back_link');
            }
        } else {
            $link = ($registered_only && !$manager->is_registered) ? $this->getLink('login') : $this->getLink('index');
            $tpl->tplAssign('back_link', $link);
            $tpl->tplSetNeeded('/back_link');
        }
        
        $tpl->tplAssign('modal_title', $this->nav_title);
        $tpl->tplAssign('content', $content);
        $tpl->tplAssign('kb_path', $this->controller->kb_path);
        $tpl->tplAssign($this->msg);

        if(!AppPlugin::isPlugin('copyright')) {
            $str = $this->getCopyrightInfoMsg();
            $tpl->tplAssign('copyright_info', $str);
        }
        
        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }


    function getMsgFormat($msg_id) {
        $error_format = array('_error', '_failed');
        $error_match = (str_replace($error_format, '', $msg_id) != $msg_id);
        $msg_format = ($error_match) ? 'error' : $this->action_msg_format;
        return $msg_format;
    }


    function parseHeader(&$tpl, $manager) {
        
        $tpl->tplAssign('header_title', $this->stripVars($manager->getSetting('header_title')));
        $tpl->tplAssign('header_title_link', $this->getLink());

        if($manager->getSetting('view_header')) {
            $header_class = '';
            $tpl->tplSetNeeded('/header');

            $logo = $manager->getSetting('header_logo');
            if ($logo) {
                $tpl->tplSetNeeded('/logo');
                $tpl->tplAssign('image_data', $logo);
            }
            
            $logo_mobile = $manager->getSetting('header_logo_mobile');
            if ($logo_mobile) {
                $tpl->tplSetNeeded('/logo_mobile');
                $tpl->tplAssign('image_data_mobile', $logo_mobile);
            }

        } else {
            $header_class = 'show-for-small-only';
            $tpl->tplSetNeeded('/no_header');
        }
        
        $tpl->tplAssign('header_class', $header_class);
    }
    
    
    function parseCustomPageIn(&$tpl, $manager) {
        return false;
    }


    function getCopyrightInfoMsg() {
        $str = '<a href="%s" title="%s" target="_blank">%s %s</a> <span>(%s)</span>';
        return sprintf($str, $this->conf['product_www'], $this->conf['product_name'],
                             $this->msg['powered_by_msg'], $this->conf['product_name'], 
                             $this->msg['powered_desc_msg']);
    }


    function parseForm($tpl, $manager, $parse_select = false) {

        // files
        if(!$this->controller->mod_rewrite && ($this->view_id == 'files')) {
            $arr = array($this->controller->getRequestKey('view') => 'files');
            $hidden_category = http_build_hidden($arr, true);
            $tpl->tplAssign('hidden_category', $hidden_category);
        }

        // search
        $tpl->tplAssign('category_id', (!empty($this->category_id)) ? $this->category_id : 0);
        $tpl->tplSetNeededGlobal('form');
    }


    function getSearchTopBlock($manager, $format = false) {
    
        $tmpl = $this->getTemplate('block_search_top.html');
        
        $tpl = new tplTemplatez($tmpl);

        $sp = $this->_getSearchFormParams();
        $default_in = $manager->getSetting('search_default');

        $view_format = $this->controller->getSetting('view_format');

        $submit_icon = ($view_format == 'default') ? 'search_submit' : 'search_submit2';
        $tpl->tplAssign('submit_icon', $submit_icon);

        $msg = AppMsg::getMsg('ranges_msg.ini', 'public', 'search_in_range');

        // main
        $keys = array('article');

        if($manager->isModule('file')) {
            $keys[] = 'file';
        }

        if($manager->isModule('news')) {
            $keys[] = 'news';
        }

        // categories
        $views = array('index', 'entry', 'files');
        if (in_array($this->view_id, $views) && $this->category_id) {
            array_unshift($keys, 'category');
        }

        array_unshift($keys, 'all');

        $last_key = end($keys);
        foreach ($keys as $key) {
            $v['filter_id'] = $key;

            $v['filter_key'] = $key;
            if ($key == 'category') {
                if (in_array($this->view_id, $this->files_views)) {
                    $v['filter_key'] = 'file';

                } else {
                    $v['filter_key'] = 'article';
                }
            }

            $v['filter_title'] = $msg[$key];

            $v['filter_options'] = '';
            if ($key == 'category') {
                $v['filter_options'] = sprintf('data-category="%s"', $this->category_id);
            }

            $v['class'] = '';
            if (empty($_GET['in'])) {
                if ($key == $default_in) {
                    $v['filter_options'] .= ' checked';
                    $v['class'] = 'selected';
                }

            } elseif ($_GET['in'] == $key) {
                $v['filter_options'] .= ' checked';
                $v['class'] = 'selected';
            }

            $tpl->tplParse($v, 'filter_row');
        }

        $tpl->tplAssign('advanced_search_link',
            $this->getLink('search', $this->category_id));

        if($manager->getSetting('search_suggest')) {
            $tpl->tplSetNeeded('/search_suggest');
            $tpl->tplAssign('suggest_link', AppController::getAjaxLinkToFile('suggest'));
        }

        $tpl->tplAssign('form_search_action', $this->getLink('search', $this->category_id));
        $tpl->tplAssign('hidden_search', $sp['hidden_search']);
        $tpl->tplAssign('q', ''); // we always has advanced from after submit
		$tpl->tplAssign('alert_empty_search', addslashes($this->msg['alert_empty_search_msg']));

        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }
    

    function getCookieConsentBlock() {
        $tpl = new tplTemplatez($this->getTemplate('block_cookie_consent.html'));
        $tpl->tplAssign('cc_link', 'https://cookiesandyou.com');
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function _getCategorySelect($manager, $top_category_id) {

        $range_ = $manager->getCategorySelectRange($manager->categories, $top_category_id);

        $range = array();
        if(isset($manager->categories[$top_category_id])) {
            $range[$top_category_id] = $manager->categories[$top_category_id]['name'];
        }

        if (!empty($range_)) {
            foreach (array_keys($range_) as $cat_id) {
                $range[$cat_id] = '-- '. $range_[$cat_id];
            }
        }

        $select = new FormSelect();
        $select->select_tag = false;
        $select->setRange($range);

        return array($select->select($this->category_id), empty($range_));
    }


    function _getCategoryFilterSelect($manager, $top_category_id) {

        $range = array();
        foreach(array_keys($manager->categories) as $cat_id) {
            if ($manager->categories[$cat_id]['parent_id'] == 0) {
                $range[$cat_id] = $manager->categories[$cat_id]['name'];
            }
        }

        $select = new FormSelect();
        $select->select_tag = false;
        $select->setRange($range, array('all' => '__'));

        return $select->select($top_category_id);
    }


    function _getSearchFormParams() {

        $search_str = '';
        if(isset($_GET['q'])) {
            $q = $this->stripVars(trim($_GET['q']), array(), 'asdasdasda');
            $sign = ($this->controller->mod_rewrite) ? '?' : '&';
            $search_str = sprintf('%sq=%s', $sign, $q);
        }

        $hidden_search = '';
        if(!$this->controller->mod_rewrite) {
            $arr = array($this->controller->getRequestKey('view') => 'search');
            $hidden_search = http_build_hidden($arr, true);
        }

        return array('search_str' => $search_str, 'hidden_search' => $hidden_search);
    }


    function &getTopMenuData($manager) {
        
        $menu =  $manager->getSetting('menu_main');
        $menu = unserialize($menu);
        
        $this->msg['menu_title_msg'] =  $this->msg['menu_article_msg'];
        if(in_array($this->view_id, $this->files_views)) {
            $this->msg['menu_title_msg'] =  $this->msg['menu_file_msg'];
        }
        
        $data = array();
        
        if(count($menu['active']) == 1) {
            return $data;
        }
        
        $pluginable = AppPlugin::getPluginsFiltered('menu_id', true);
        
        $menu = SettingData::getMainMenu($menu);
        foreach ($menu['active'] as $num => $item) {
            
            // built in items
            if (!empty($item['id'])) {
                
                // for logged only, ask a question for example
                if (!empty($main_menu[$item['id']]['registered_only']) && !$manager->is_registered) {
                    continue;
                }
                
                // skip plugins
                if(isset($pluginable[$item['id']])) {
                    if(!AppPlugin::isPlugin($pluginable[$item['id']])) {
                        continue;
                    }
                }
            }
            
            // custom for logged only
            if (!empty($item['logged']) && !$manager->is_registered) {
                continue;
            }
            
            $title = (!empty($item['title'])) ? $item['title'] : $this->msg[sprintf('menu_%s_msg', $item['id'])];
            $options = (!empty($item['options'])) ? trim($item['options']) : '';
            $more = (!empty($item['dropdown'])) ? true : false;
            $new_window = (!empty($item['target'])) ? true : false;
            
            if (!empty($item['id'])) {
                $views = SettingData::$main_menu[$item['id']]['views'];
                $view_key = current($views);
                $link = $this->getLink($view_key);
                
            } else {
                $views = array();
                $link = $item['link'];
            }
            
            $data[] = array(
                'item_key' => (!empty($item['id'])) ? $item['id'] : 'custom_' . $num,
                'item_menu' => $title,
                'item_link' => $link,
                'views' => $views,
                'options' => $options,
                'more' => $more,
                'new_window' => $new_window
            );
        }
        
        return $data;
    }


    function getActionMenu($items) {

		if(!$items) {
			return '';
		}

        $html = array();
        $html[] = '<ul class="jq-dropdown-menu">';
        $item_str = '<li><a href="%s" rel="nofollow" %s>%s</a><li class="jq-dropdown-divider"></li>';
        $disabled_item_str = '<li style="padding: 2px 10px;color: #aaaaaa;">%s</li>';

        foreach ($items as $item) {
            if ($item[1]) {
                $str = 'onclick="confirm2(\'%s\', \'%s\');return false;"';
                $confirmation = (!empty($item[2])) ? sprintf($str, $this->msg['sure_msg'], $item[1]) : '';
                $html[] = sprintf($item_str, $item[1], $confirmation, $item[0]);

            } else {
                $html[] = sprintf($disabled_item_str, $item[0]);
            }
        }
        $html[] = '</ul>';

        return implode('', $html);
    }


    function &getManageMenuData($manager) {

        $manage = array();
        $menu = array();

        // edit mode
        /*
        $allowed = AuthPriv::getPrivAllowed('kb_entry');
        if(in_array('update', $allowed)) {// full priv to update

            $allowed_ = array('kb_entry' => $allowed);
            if(!AuthPriv::isPrivOptionalStatic('update', 'draft', 'kb_entry', $allowed_)) {
                $emode = (!empty($_COOKIE['kb_emode_']));
                $msg_key = ($emode) ? 'edit_mode_off_msg' : 'edit_mode_on_msg';
                $js_action = ($emode) ? 'deleteCookie(\'kb_emode_\', \'/\');' : 'createCookie(\'kb_emode_\', true);';
                $manage['edit_mode'] = array(
                    'item_link' => sprintf('javascript:location.reload();%s', $js_action),
                    'item_menu' => $this->msg[$msg_key]
                );

                $manage[] = 'delim';
            }
        }*/


        // add article here new
        if($manager->isEntryAddingAllowedByUser()) {
            $more = array('mode' => 'client_new');
            $link = $this->controller->getAdminRefLink('knowledgebase', 'kb_entry', false, 'category', $more);
            $options = sprintf('onclick="PopupManager.create(\'%s\',\'\',\'\',\'\',770,400);"', $link);
            $link = '#';

            if((in_array($this->view_id, array('index', 'entry', 'entry_add'))
                    && $this->category_id)) {
                if($manager->isEntryAddingAllowedByUser($this->category_id)) {
                    $link = $this->controller->getLink('entry_add', $this->category_id);
                    $options = false;
                }
            }

            $manage['article_here'] = array(
                'item_menu' => $this->msg['menu_add_article_here_msg'],
                'item_link' => $link,
                'item_options' => $options
            );

            $manage[] = 'delim';
        }


        // actions to admin area
        $views = array('index', 'entry', 'comment');
        $menu[1]['article'] = array($views, 'knowledgebase', 'kb_entry', 'menu_add_article_msg', 1);
        $menu[1]['article_draft'] = array($views, 'knowledgebase', 'kb_draft', 'menu_add_article_draft_msg', 1);
        $menu[1]['article_c'] = array(array(), 'knowledgebase', 'kb_category', 'menu_add_article_cat_msg');

        $views = array('files');
        $menu[2]['file'] = array($views, 'file', 'file_entry', 'menu_add_file_msg', 1);
        $menu[2]['file_draft'] = array($views, 'file', 'file_draft', 'menu_add_file_draft_msg', 1);
        $menu[2]['file_c'] = array(array(), 'file', 'file_category', 'menu_add_file_cat_msg');

        $menu[3]['news']      = array(array(), 'news', 'news_entry', 'menu_add_news_msg');

        $delim = array();
        $drafts = array('kb_entry', 'file_entry');

        foreach($menu as $group_key => $group) {

            foreach($group as $k => $v) {
                // if($v == 'delim') {
                //    $manage[] = $v;
                //    continue;
                // }

                $allowed = AuthPriv::getPrivAllowed($v[2]);
                if(in_array('insert', $allowed)) {
                    $more = array();
                    $more['referer'] = 'client';
                    if(in_array($this->view_id, $v[0]) && $this->category_id) {
                        $more['filter[c]'] = $this->category_id;
                    }

                    $link = $this->controller->getAdminRefLink($v[1], $v[2], false, 'insert', $more, false);
                    $manage[$k]['item_link'] = $link;
                    $manage[$k]['item_menu'] = $this->msg[$v[3]];

                    // only drafts allowed
                    if(in_array($v[2], $drafts)) {
                        $allowed_ = array($v[2] => $allowed);
                        if(AuthPriv::isPrivOptionalStatic('insert', 'draft', $v[2], $allowed_)) {
                            unset($manage[$k]);
                        }
                    }
                }

                if(!empty($manage[$k])) {
                    $delim[$group_key] = true;
                }
            }


            if(!empty($delim[$group_key])) {
                $manage[] = 'delim';
            }
        }


        return $manage;
    }


    function &getTopMenuBlock($top_menu, $mobile = false) {
        
        $tmpl = ($mobile) ? 'block_menu_top_mobile.html' : 'block_menu_top.html';
        $tpl = new tplTemplatez($this->getTemplate($tmpl));

        $view_format = $this->controller->getSetting('view_format');
        $more_button = ($view_format != 'fixed');
        $more_items = array();

        foreach($top_menu as $k => $v) {
            if ($more_button && !empty($v['more'])) {
                $more_items[] = $v;
                continue;
            }

            $v['class'] = (in_array($this->view_id, $v['views'])) ? 'menuItemSelected' : 'menuItem';
            $v['options'] = (!empty($v['options'])) ? ' ' . $v['options'] : '';
            $v['target'] = (!empty($v['new_window'])) ? '_blank' : '_self';
            
            $tpl->tplParse($v, 'row');
        }

        if (!empty($more_items)) {
            $tpl->tplSetNeeded('/more_button');

            foreach ($more_items as $v) {
                $v['target'] = (!empty($v['new_window'])) ? '_blank' : '_self';
                $tpl->tplParse($v, 'more_row');
            }
        }

        if($this->parse_form) {
            if($view_format == 'left') {
                $sidebar_action_title = $this->msg['hide_sidebar_msg'];
                $next_action_title = $this->msg['show_sidebar_msg'];
                if(isset($_COOKIE['kb_sidebar_width_']) && $_COOKIE['kb_sidebar_width_'] == 0) {
                    $sidebar_action_title = $this->msg['show_sidebar_msg'];
                    $next_action_title = $this->msg['hide_sidebar_msg'];
                }

                $tpl->tplAssign('action_title', $sidebar_action_title);
                $tpl->tplAssign('next_action_title', $next_action_title);
            }
        }

        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }


    function getLoginBlock($manager, $manage_menu, $mobile = false) {

		$tmpl = ($mobile) ? 'block_login_mobile.html' : 'block_login.html';
        $tpl = new tplTemplatez($this->getTemplate($tmpl));

        $msg['logged_msg'] = $this->msg['logged_msg'];
        $msg['logout_msg'] = $this->msg['logout_msg'];
		$msg['admin_view_msg'] = $this->msg['admin_view_msg'];
        $msg['register_msg'] = $this->msg['register_msg'];
        $msg['login_msg'] = $this->msg['login_msg'];

        if($manager->is_registered) {

            $tpl->tplAssign('username', AuthPriv::getUsername());
            $tpl->tplAssign('logout_link', $this->getLink('logout'));
            $tpl->tplAssign('username_link', $this->getLink('account'));
            $user_icon_margin = '00px';

            if($manager->user_priv_id) {
                $user_icon_margin = '40px';
                $tpl->tplAssign('admin_link', APP_ADMIN_PATH . 'index.php');
                $tpl->tplAssign('admin_path', APP_ADMIN_PATH . 'index.php');
                $tpl->tplSetNeeded('/admin_view');

                if($manage_menu) {

                    $html = array();
                    $action_item_str = '<li><a href="%s" %s>%s</a></li>';
                    $disabled_item_str = '<li style="padding: 2px 10px;color: #aaaaaa;">%s</li>';
                    $divider_str = '<li class="jq-dropdown-divider"></li>';

                    foreach($manage_menu as $k => $v) {

                        if ($v == 'delim') {
                            $html[] = $divider_str;

                        } else {
                            if ($v['item_link']) {
                                $options = (!empty($v['item_options'])) ? $v['item_options'] : '';
                                $html[] = sprintf($action_item_str, $v['item_link'], $options, $v['item_menu']);

                            } else {
                                $html[] = sprintf($disabled_item_str, $v['item_menu']);
                            }
                        }
                    }

                    $tpl->tplAssign('manage_menu', implode("\n", $html));
					$tpl->tplSetNeeded('/manage');
                }
            }

            // account
            $tpl->tplSetNeeded('/account');

            $tpl->tplAssign('user_icon_margin', $user_icon_margin);
            $tpl->tplSetNeeded('/logged');

        } else {
            
            $tpl->tplSetNeeded('/not_logged');
            $tpl->tplAssign('user_icon_margin', '0px');

            $n = 0;
            if($manager->getSetting('register_policy')) {
                $n++;
                $tpl->tplAssign('register_link', $this->getLink('register'));
                $tpl->tplSetNeeded('/register_link');
            }

            //should check more to redirect correct after login
            if($manager->getSetting('login_policy') == 1) {
                $n++;
                if($this->view_id == 'index' && $this->category_id) {
                    $link = $this->getLink('login', $this->category_id, $this->category_id, '_' . 'category');

                } elseif(in_array($this->view_id, array('files')) && $this->category_id) {
                    $link = $this->getLink('login', $this->category_id, $this->category_id, '_' . $this->view_id);

                } else {
                    $view_id = ($this->view_id == '404') ? '' : '_' . $this->view_id;
                    $link = $this->getLink('login', $this->category_id, $this->entry_id, $view_id);
                }

                $tpl->tplAssign('login_link', $link);
                $tpl->tplSetNeeded('/login_link');
            }
        }

        // pool
        if($manager->getSetting('show_pool_link')) {
            $tpl->tplSetNeeded('/pool_link');
            $tpl->tplAssign('pool_link', $this->getLink('pool'));

            $display_pool = 'none';
            $display_pool_mobile = 'none';
            $ids = $this->getUserPool('pool');
            if (count($ids) > 0) {
                $display_pool = ($manager->is_registered) ? 'block' : 'inline';
                $display_pool_mobile = 'block';
                $tpl->tplAssign('pool_num', count($ids));
            }

            $tpl->tplAssign('display_pool', $display_pool);
            $tpl->tplAssign('display_pool_mobile', $display_pool_mobile);

            $pool_title = sprintf('%s: %d', $this->msg['pinned_items_msg'], count($ids));
            $tpl->tplAssign('pool_title', $pool_title);
        }
        
        // notification block
        $ajax = $this->getAjax('preview');
        $xajax = &$ajax->getAjax($manager);
        
        if ($manager->is_registered) {
            $notification_block = NotificationView_common::getBlock($this->controller, $xajax);
            $tpl->tplAssign('notification_block', $notification_block);

            if ($mobile) {
                $n_manager = new NotificationModel;
                $num = $n_manager->getUserNotificationsCount();

                if ($num) {
                    $tpl->tplSetNeeded('/notification_block');
                }

            } else {
                $tpl->tplSetNeeded('/notification_block');
            }
        }
        
        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }


    function getUserPool($name) {
        $cookie_name = sprintf('kb_%s_', $name);
        $ids = array();
        if (isset($_COOKIE[$cookie_name])) {
            if (!preg_match('#^\[\d+(,\d+)*]$#', $_COOKIE[$cookie_name])) {
                $_COOKIE[$cookie_name] = array();
                unset($_COOKIE[$cookie_name]);
                setcookie($cookie_name, '', time() - 3600, '/');

            } else {
                $ids = substr($_COOKIE[$cookie_name], 1, -1);
                $ids = explode(',', $ids);
            }
        }

        return $ids;
    }

    
    function isEntryPinnedByUser($entry_id) {
        return (in_array($entry_id, $this->getUserPool('pool')));
    }


    function _getFormatedNavigation(&$manager, $article_name = false) {

        // skip category generate on some vies or if no categories (one only)
        if($this->category_nav_generate && $this->display_categories) {
            $arr = TreeHelperUtil::getParentsById($manager->categories, $this->category_id, 'name');
            $arr = $this->stripVars($arr);
            $manager->categories_parent = $arr;
        } else {
            $arr = array();
            if($this->category_id) {
                $this->home_link = true;
            }
        }

        $view = 'index';
        $arr1 = array();
        $files = in_array($this->view_id, $this->files_views);
        $str = '<a href="%s" class="navigation">%s</a>';

        // extra
        $nav_extra = $this->controller->getSetting('nav_extra');
        if($nav_extra) {
            foreach(explode('||', $nav_extra) as $v) {
                list($title, $link) = explode('|', $v);
                $arr1[] = sprintf($str, trim($link), $this->stripVars(trim($title)));
            }
        }

        $nav_title = $this->stripVars($this->controller->getSetting('nav_title'));
        if($arr || $this->home_link || $files) {
            $arr1[] = sprintf($str, $this->getLink(), $nav_title);
            if($files) {
                $view = 'files';
                if($this->view_id == 'fsearch' || $this->category_id) {
                    $arr1[] = sprintf($str, $this->getLink('files'), $this->msg['menu_file_msg']);
                } elseif($this->page_id) { // dinanmic files lists
                    $arr1[] = sprintf($str, $this->getLink('files'), $this->msg['menu_file_msg']);
                } else {
                    $arr1[] = $this->msg['menu_file_msg'];
                }
            }

        } else {
            $arr1[] = $nav_title;
        }


        // add article title if have it
        if($article_name !== false) {
            $article_name = (is_array($article_name)) ? $article_name : array($article_name);
            foreach($article_name as $k => $v) {
                $arr[$k] = $v;
            }
        }


        $num = count($arr);
        $i = 1;
        foreach($arr as $k => $v) {
            if($num != $i) {
                //$k = (is_numeric($k)) ? $this->getLink($view, $k, false) : $k; // link to category
                if(is_numeric($k)) { // link to category
                    $cat_id = $this->controller->getEntryLinkParams($k, $v);
                    $k = $this->getLink($view, $cat_id); 
                }
                
                $arr1[] = sprintf($str, $k, $v);
            } else {
                $arr1[] = $v;
            }

            $i++;
        }

        $span1 = '<span class="navigation">';
        $span2 = '</span>';
        $delim = $span2. $this->nav_delim . $span1;

        return $span1 . implode($delim, $arr1) . $span2;
    }


    function getRatingBlock($manager, $data) {

        $ratingable = $this->isRatingable($manager, $data['ratingable']);
        $comentable = ($manager->getSetting('allow_rating_comment'));
        $rating_text = ($manager->getSetting('rating_type') == 1);
        
        if(!$ratingable && !$comentable) {
            return;
        }

        $tpl = new tplTemplatez($this->getTemplate('block_rating.html'));

        if($manager->isUserVoted($data['id']) === false || @$_POST['xajax'] == 'doRateFeedback') {

            if($ratingable) {
                $tpl->tplSetNeededGlobal('show_rating_option');
            }

            // if($manager->getSetting('allow_rating_comment')) {
            if($comentable) {
                $class = ($ratingable) ? 'fright' : 'fleft';
                $tpl->tplAssign('rating_comment_class', $class);
                $tpl->tplSetNeededGlobal('show_rating_comment');
            }

            if($rating_text) {
                $row_block = 'rating_row';
                $range = AppMsg::getMsg('ranges_msg.ini', 'public', 'rating');
            } else {
                $row_block = 'rating_row2';
                $range = AppMsg::getMsg('ranges_msg.ini', 'public', 'rating2');
                $range = array_reverse($range, true);
            }

            $cr = count($range); $i = 1;
            foreach($range as $k => $v) {
                $a['rate_value'] = $k;
                $a['rate_item'] = $v;
                $a['delim'] = ($cr > $i++) ? ' | ' : '';
                $tpl->tplParse($a, $row_block);
            }

            //xajax
            $ajax = &$this->getAjax('entry');
            $ajax->atoken = Auth::getCsfrTokenCookie();
            $xajax = &$ajax->getAjax($manager);
            $xajax->registerFunction(array('doRate', $ajax, 'doRateResponse'));
            $xajax->registerFunction(array('doRateFeedback', $ajax, 'doRateFeedbackResponse'));

            $tpl->tplAssign('form_action', $this->getLink('all'));

        } else {

            // $tpl->tplAssign('rating_percent', ceil($data['rating'] * 10) . '%');
            // $tpl->tplAssign('rating', $this->_getRating($data['rating']));
            // $tpl->tplAssign('votes', ($data['votes']) ? $data['votes'] : 0);

			// current raiting
                //             if($data['rating'] && $rating_text) {
                //
                // if($data['rating']/$data['votes'] != 1) { // all rates "not usefull" no or "1" star = 1
                //     $raiting = ceil($data['rating'] * 10) . '%';
                //     $current_rating = AppMsg::replaceParse($this->msg['current_rating_msg'], array('percent'=>$raiting));
                //     $tpl->tplAssign('current_rating', $current_rating);
                // }
                //             }

            $tpl->tplSetNeeded('/show_rating');
        }


        $title = $this->msg['add_rate2_msg'];
        $tpl->tplAssign('title', $title);
        $tpl->tplAssign('rate_atoken', Auth::getCsfrTokenCookie());

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    // is entry private read
    // $category may have multiple values and if at least ??
    // one is not private read the mark as not private 0 not private, 2 private write ??
    function isPrivateEntry($entry, $category) {
        if(!AppPlugin::isPlugin('private')) {
            return false;
        }
        
        // return (in_array($entry, array(1,3)) || in_array($category, array(1,3)));
        return (PrivatePlugin::isPrivateRead($entry) || PrivatePlugin::isPrivateRead($category));
    }


    // if user logged in we know his roles so we show private without
    // roles and hide with not his roles
    // does not depends on setting hide/show private entry
    function isPrivateEntryLocked($registered, $private) {
        if(!AppPlugin::isPlugin('private')) {
            return false;
        }
        
        return ($private && !$registered) ? true : false;
    }


    function &getAjax($view) {
        $ajax = &KBClientAjax::factory($view);
        $ajax->view = $this;
        $this->ajax_call = true;
        return $ajax;
    }


    function runAjax() {
        if($this->ajax_call) {
            return KBClientAjax::processRequests(false, $this->ajax_post_action);
        }
    }


    // CKEditor // --------------------

    function getEditor($value, $cfile, $fname = 'body', $cconfig = array()) {

        require_once APP_ADMIN_DIR . 'tools/ckeditor_custom/ckeditor.php';

        $admin_path = $this->controller->getAdminJsLink();

        $config_file = array(
          'article' => 'ckconfig_article.js'
        );

        $CKEditor = new CKEditor();
        $CKEditor->returnOutput = true;
        $CKEditor->basePath = $admin_path . 'tools/ckeditor/';

        $config = array();
        $config['customConfig'] = $admin_path . 'tools/ckeditor_custom/' . $config_file[$cfile];

        foreach($cconfig as $k => $v) {
            $config[$k] = $v;
        }

        $events = array();
        // $events['instanceReady'] = 'function (ev) {
        //     alert("Loaded: " + ev.editor.name);
        // }';

        return $CKEditor->editor($fname, $value, $config, $events);
    }


    // Custom fields // ---------------

    function getCustomData($rows, $bottom_as_inline = false) {

        $data = array(1 => array(), 2 => array(), 3 => array());
        
        if(!AppPlugin::isPlugin('fields')) {
            $rows = [];
        }
        
        if(!$rows) {
            return $data;
        }

        $custom_data = array();
        foreach($rows as $field_id => $v) {
            $custom_data[$field_id] = $v['data'];
        }

        $ch_value = $this->getCustomDataCheckboxValue();
        $cf_manager = new CommonCustomFieldModel();
        $custom = CommonCustomFieldView::getCustomData($custom_data, $cf_manager, $ch_value);

        foreach ($custom as $field_id => $v) {
            $row = $rows[$field_id];

            if($row['display'] == 3 && !$bottom_as_inline) {
                $template = array('title' => $v['title'], 'value' => $v['value']);

            } else {
                if(empty($row['html_template'])) {
                    $row['html_template'] = '{title}: {value}';
                }

                $r = array('{title}' => $v['title'], '{value}' => $v['value']);
                $template = strtr($row['html_template'], $r);
            }

            $data[$row['display']][$field_id] = $template;
        }

        return $data;
    }


    function getCustomDataCheckboxValue() {
        return 'checkbox';
    }
    
    
    function parseCustomData($data, $position) {
        $ret = '';
        if($data) {
            $data = DocumentParser::parseCurlyBracesSimple($data);

            $html = array();
            foreach ($data as $custom_id => $str) {
                $html[] = sprintf('<div id="custom_block_%s" class="customFieldItem">%s</div>', $custom_id, $str);
            }

            return implode('', $html);
        }

        return $ret;
    }

    
    function getTagsArray($tags, $search_in = 'all') {
        $rows = array();
        foreach ($tags as $tag_id => $title) {
            $more = array('s' => 1, 'q' => $title, 'in' => $search_in, 'by' => 'keyword');
            $rows[$tag_id]['link'] = $this->getLink('search', false, false, false, $more);
            $rows[$tag_id]['title'] = $title;
        }

        return $rows;
    }


    function getEntryActionsMorePopup($items, $manager/*, $dynamic_actions = array()*/) {
        
        $tpl = new tplTemplatez($this->getTemplate('block_action_more_popup.html'));
        
        $items = array_filter($items);
        foreach(array_keys($items) as $group_key) {
            
            $tpl->tplSetNeeded(sprintf('/%s_block', $group_key));
            
            foreach ($items[$group_key] as $key => $item) {
                $v = $item;
                $v['key'] = $key;
                $v['title'] = $this->stripVars(trim($v['title']));
                $v['icon'] = trim($v['icon']);
                $v['icon'] = (!empty($v['icon'])) ? $v['icon'] : '{client_href}images/icons/article_panel/share.svg';
                $v['badge_class'] = '';
                
                // save
                if ($key == 'save') {
            
                    if($manager->is_registered && $manager->isEntrySubscribedByUser($this->entry_id, 1) ) {
                        $v['title'] = $this->msg['remove_from_list_msg'];
                        $v['link'] = "#remove";
                        $v['attr'] = 'onclick="subscribeToEntry(0);"';
                        $v['badge_class'] = 'badgeIcon';
                
                    } else {
                        $v['link'] = "#save";
                        $v['attr'] = 'onclick="subscribeToEntry(1);"';
                    }
                
                // pin
                } elseif ($key == 'stick') {
                    
                    if($this->isEntryPinnedByUser($this->entry_id)) {
                        $v['title'] = $this->msg['unpin_msg'];
                        $v['attr'] = sprintf('onclick="PoolManager.remove(%d);"', $this->entry_id);
                        $v['link'] = "#unpin";
                        $v['badge_class'] = 'badgeIcon';
                    } else {
                        $v['link'] = "#pin";
                        $v['attr'] = sprintf('onclick="PoolManager.add(%d);"', $this->entry_id);
                    }
                }
                
                $tpl->tplParse($v, $group_key . '_item');
            }
        }

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEntryActionsFloatPanel($items, $data, $manager) {
        
        $tpl = new tplTemplatez($this->getTemplate('block_action_float_panel.html'));
        
        $items = SettingData::getFloatPanelItems($manager, $this, $data);
        
        // if($manager->getSetting('show_share_link') && $manager->getSetting('item_share_link')) {
            $share_link_full = $this->controller->getLinkNoRewrite('entry', false, $data['id']);
            $share_link_full = urlencode($share_link_full);
        // }
        
        $share_link = $this->controller->getRedirectLink($this->view_id, false, $data['id']);
        $share_link = urlencode($share_link);
        $share_title = urlencode($data['title']);
        
        $social_items = $manager->getSetting('item_share_link');
        $social_items = unserialize($social_items);
        
        //xajax
        $ajax = &$this->getAjax('entry');
        $ajax->view = &$this;
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('doSubscribe', $ajax, 'doSubscribeArticleResponse'));
        
        
        foreach ($items['active'] as $key => $item) {
            $v = $item;
            $v['key'] = $key;
            $v['badge_class'] = '';
            
            $group_key = 'active';
            
            // save
            if ($key == 'save') {
                
                if($manager->is_registered && $manager->isEntrySubscribedByUser($data['id'], 1) ) {
                    $v['title'] = $this->msg['remove_from_list_msg'];
                    $v['link'] = "#remove";
                    $v['attr'] = 'onclick="subscribeToEntry(0);"';
                    $v['badge_class'] = 'badgeIcon';
                    
                } else {
                    $v['link'] = "#save";
                    $v['attr'] = 'onclick="subscribeToEntry(1);"';
                }
                
            // pin
            } elseif ($key == 'stick') {
                
                if($this->isEntryPinnedByUser($data['id'])) {
                    $v['title'] = $this->msg['unpin_msg'];
                    $v['link'] = '#unpin';
                    $v['attr'] = sprintf('onclick="PoolManager.remove(%d);"', $data['id']);
                    $v['badge_class'] = 'badgeIcon';
                } else {
                    $v['link'] = '#pin';
                    $v['attr'] = sprintf('onclick="PoolManager.add(%d);"', $data['id']);
                }
            
            
            // built-in social
            } elseif (!empty(SettingData::$sharing_sites[$key])) {

                $replace = array(
                    '[url]' => $share_link,
                    '[title]' => $share_title
                );
                                
                $v['attr'] = str_replace(array_keys($replace), $replace, $v['attr']);
                $v['url'] = str_replace(array_keys($replace), $replace, $v['url']);
                
                $items['active'][$key] = str_replace(array_keys($replace), $replace, $items['active'][$key]);
                
                
            // custom social
            } elseif(substr($key, 0, 7) == 'custom_') {
            
                $custom_id = substr($key, 7);
                foreach ($social_items['active'] as $v1) {
                    if (is_array($v1) && $v1['id'] == $custom_id) {
                        extract($v1);
                    }
                }
                
                $url = str_replace('[url]', $share_link, $url);
                $url = str_replace('[title]', urlencode($data['title']), $url);
                $v['attr'] = sprintf('onclick="shareArticle(\'%s\');"', $url);
                
                $icon = trim($icon);
                $v['icon'] = (!empty($icon)) ? sprintf('data:image/svg+xml;base64,%s', base64_encode($icon)) : '{client_href}images/icons/article_panel/share.svg';
                $v['link'] = '#';
            }
            
            $tpl->tplParse($v, 'active_item');
        }
        
        // comment form 
        if($this->view_id == 'entry') {
            if(!$manager->getSetting('comments_entry_page') ||
                        !$manager->getCommentListCount($data['id'])) {
                            
                $c = new KBClientView_comment;
                $cfrom = $c->getFormPopup($manager/*, $data, $title*/);
                $tpl->tplAssign('comment_form_block', $cfrom);
            }
        }
        
        $dropdown_items = array();
        foreach (array_merge($items['active'], $items['inactive']) as $k => $v) {
            if (!empty(SettingData::$panel_items[$k]) && $k != 'send') {
                $dropdown_items['action'][$k] = $v;
                
            } else {
                $dropdown_items['share'][$k] = $v;
            }
        }
        
        $dropdown_block = $this->getEntryActionsMorePopup($dropdown_items, $manager);
        $dropdown_block = str_replace('[full_url]', urldecode($share_link_full), $dropdown_block);
        $tpl->tplAssign('dropdown_block', $dropdown_block);
        
        if (!empty($this->action_menu)) {            
            $menu = $this->getActionMenu($this->action_menu);
            $tpl->tplAssign('action_menu', $menu);
            $tpl->tplSetNeededGlobal('admin_block_menu');
        }
        
        $tpl->tplParse($data);
        return $tpl->tplPrint(1);
    }


    function getEntryActionsFloatPanelMinHeight($items) {
        $top = 20;
        $item_height = 41;
        $min_height = ((count($items) + 1) * $item_height) + $top;
        
        if (!empty($this->action_menu)) {
            $min_height += $item_height + 20;
        }
        
        return $min_height;
    }


    // validation

    function getValidate($values) {
        $ret = array();
        $ret['func'] = array($this, 'validate');
        $ret['options'] = array($values);

        $fct = new ReflectionMethod(get_class($this), 'validate');
        if($fct->getNumberOfParameters() > 1) {
            $ret['options'][] = 'manager';
        }

        // return AppView::ajaxValidateForm($func, $options);
        return $ret;
    }
    
    
    // design
    function getGrid($key, $manager) {
        
		$pd_manager = new PageDesignModel;
        
        $layout = SettingModel::getQuick(11, 'page_design_' . $key . '_html');
        $pattern = '#<div data-block_id="(\w+)"[^>]*>#';
		preg_match_all($pattern, $layout, $matches);
        // echo '<pre>';var_dump($matches);
        
        // <div data-block_id="search">
        //     <div class="tdTitle">Search test</div>[block_search]
        // </div>
        
        $search_placeholder = '';
        $pattern = '#<div class="tdTitle">(.*?)</div>(\[block_search\])#';
        preg_match_all($pattern, $layout, $matches2, PREG_SET_ORDER);
        if(!empty($matches2[0])) {
            $layout = str_replace($matches2[0][0], $matches2[0][2], $layout);
            $search_placeholder = $matches2[0][1];
        }
        
		$blocks = array(
		    '[block_white_space]' => '<div class="grid_empty_cell"></div>'
		);
		
		foreach ($matches[1] as $k => $block_id) {
			$placeholder = sprintf('[block_%s]', $block_id);
			
			if (substr($block_id, 0, 6) == 'custom') { // custom block
                $custom_block_id = substr($block_id, 7); 
                $custom_block = $pd_manager->getById($custom_block_id);
                $options = unserialize($custom_block['data_string']);
                
                $tpl = new tplTemplatez($this->getTemplate('custom_block.html'));
                $tpl->tplAssign($options);
                $tpl->tplParse();
				
                $blocks[$placeholder] = $tpl->tplPrint(1);
            
            } elseif ($block_id == 'search') {
                $options = [
                    'advanced_search' => false,
                    'special_search' => false,
                    'placeholder' => $search_placeholder
                ];
                $blocks[$placeholder] = $this->getSearchFormInput($manager, $options);
            
            } else {
				
            	$method = sprintf('get%sList', ucwords(str_replace('_', '', $block_id)));
                
                if (method_exists($this, $method)) {
                	$settings = array();
                    
                    $setting_keys = array('num_entries', 'num_columns');
					$pattern_str = '/data\-%s=\"([0-9]+)\"/';
                    
                    foreach ($setting_keys as $setting_key) {
                        $pattern = sprintf($pattern_str, $setting_key);
                        preg_match($pattern, $matches[0][$k], $matches2);
                    
                        if (!empty($matches2)) {
                            $settings[$setting_key] = $matches2[1];
                        } else {
                            $settings[$setting_key] = ($block_id == 'news') ? 1 : 5; // just in case to avoid sql error
                        }
                    }
					
                    $blocks[$placeholder] = $this->{$method}($manager, $settings);
                    
                    // to hide empty blocks, js works in page.html
                    if(empty($blocks[$placeholder])) {
                        $search = sprintf('data-block_id="%s"', $block_id);
                        $replace = sprintf('%s class="hidenPadeDesignBlock" ', $search);
                        $layout = str_replace($search, $replace, $layout);
                    }
                }
            }
		}
        
		$grid = str_replace(array_keys($blocks), $blocks, $layout);
		return $grid;
    }


    // function getSearchFormInput($manager, $advanced_search = true, $special_search = false) {
    function getSearchFormInput($manager, $options = []) {
        
        $advanced_search = (isset($options['advanced_search'])) ? $options['advanced_search'] : true;
        $special_search = (isset($options['special_search'])) ? $options['special_search'] : false; 
        $placeholder = (isset($options['placeholder'])) ? $options['placeholder'] : $this->msg['search_msg']; 
        
        $tpl = new tplTemplatez($this->getTemplate('search_input_block.html'));
                
        if($manager->getSetting('search_suggest')) {
            $tpl->tplSetNeeded('/search_suggest');
            $tpl->tplAssign('suggest_link', AppController::getAjaxLinkToFile('suggest'));
        }
        
        if($special_search) {
            $tpl->tplSetNeeded('/is_special_search');
        }
        
        $params = array();
        if(!$this->controller->mod_rewrite) {
            $params['View'] = 'search';
        }

        $tpl->tplAssign('hidden', http_build_hidden($params, true));
        $tpl->tplAssign('action_link', $this->controller->getLink('search'));
        $tpl->tplAssign('placeholder', $placeholder);

        // change to original if suggest
        if(!empty($this->spell_mistake)) {
            $tpl->tplAssign('q', $this->stripVars($this->spell_mistake, array()));
        }
        
        $sphinx = SphinxModel::isSphinxOn();
        $engine = ($sphinx) ? 'sphinx' : 'mysql';
        
        $msg['body'] = AppMsg::getMsgMutliIni('text_msg.ini', 'public', 'search_help_' . $engine);
        
        // in 6.0 wrong translate tool need to convert to &gt; and &lt;
		// <b>+apple +(&gt;turnover &lt;strudel)</b><br />
		// <b>+apple +(>turnover <strudel)</b><br />
        $msg['body'] = preg_replace('#\+\(>(\w+)\s<(\w+)\)#', '+(&gt;$1 &lt;$2)', $msg['body']);
        $tpl->tplAssign('search_help_block', $msg['body']);
        
        $tpl->tplAssign('advanced_search', (int) $advanced_search);
        $tpl->tplAssign('close_from_tag', ($advanced_search) ? '' : '</form>');
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

    
    function getExternalList($view_id, $method, $manager, $block_settings) {
    	$this->controller->loadClass($view_id);
        $setting = $manager->setting;
        $manager = &KBClientLoader::getManager($setting, $this->controller, $view_id);
        $class = 'KBClientView_' . $view_id;
        $view = new $class;
        
        return $view->$method($manager, $block_settings);
    }

}
?>