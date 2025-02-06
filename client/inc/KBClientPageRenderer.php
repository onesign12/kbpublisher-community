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

class KBClientPageRenderer
{

    //var $site_path;
    var $title_delim = ' - ';
    var $vars = array();
    var $view;
    var $manager;


    function __construct(&$view, &$manager) {
        $this->setCommonVars($view, $manager);
    }


    function setCommonVars(&$view, &$manager) {
        $this->view = &$view;
        $this->manager = &$manager;
        $this->vars['base_href'] = $this->view->controller->kb_path;
        $this->vars['client_href'] = $this->view->controller->client_path;
        $this->vars['admin_href'] = $this->view->controller->admin_path;
        $this->vars['pvhash'] = $this->view->conf['product_hash'];
    }


    function getTemplateVars() {

        $data = array();
        $data['kbp_user_id'] = (int) $this->manager->user_id;
        $data['kbp_user_priv_id'] = (int) $this->manager->user_priv_id;
        $data['kbp_user_role_id'] = ($r = $this->manager->user_role_id) ? implode(',', $r) : 0;

        $data['kbp_view'] = $this->view->view_id;
        $data['kbp_article_id'] = 0;
        $data['kbp_file_id'] = 0;
        $data['kbp_news_id'] = 0;

        $class = get_class($this->manager);
        if($this->view->entry_id) {
            if(strpos($class, 'NewsModel') !== false) {
                $data['kbp_news_id'] = $this->view->entry_id;
            } elseif(strpos($class, 'FileModel') !== false) {
                $data['kbp_file_id'] = $this->view->entry_id;
            } else {
                $data['kbp_article_id'] = $this->view->entry_id;
            }
        }
        
        // if we need top category id variable 
        // $data['kbp_category_id'] = 0;
        // $data['kbp_top_category_id'] = 0;
        // if($this->view->category_id && $this->manager->categories) {
        //     $data['kbp_category_id'] = $this->view->category_id;
        //     $data['kbp_top_category_id'] = TreeHelperUtil::getTopParent($this->manager->categories, $this->view->category_id);
        // }

        // echo '<pre>', print_r($data, 1), '</pre>';
        return $data;
    }


    function _render() {

        // printing has different renderer
        if($this->view->page_print) {
            return $this->renderPrint();
        }
        
        if($this->view->page_popup) {
            return $this->renderPopup();
        }
        

        $page_to_load = $this->manager->setting['page_to_load'];
        // $page_to_load_mobile = $this->manager->setting['page_to_load_mobile'];

        $view_format = $this->view->controller->setting['view_format'];
        $view_func = ($this->view->page_modal) ? 'getPageInModal' : 'getPageIn'; 
        $container_full = ($view_format == 'fixed' && $view_func != 'getPageInModal');
    
        $container_width = ($container_full) ? 1 : $this->manager->getSetting('container_width');
        $container_width_class = sprintf('container_width_%s', $container_width);
        $this->vars['container_width_class'] = $container_width_class;
        

        if($page_to_load == strtolower('html')) {
            $tpl = new tplTemplatezString($this->getHtmlTemplate($this->manager->setting));            
            
            $tmpl = explode('--delim--', $this->manager->setting['page_to_load_tmpl']);
            $footer = (isset($tmpl[1])) ? $tmpl[1] : '';
            $this->vars['custom_footer'] = str_replace('{container_width_class}', $container_width_class, $footer);

        } else {
            $tpl = new tplTemplatez($this->getTemplate($page_to_load));
        }

        $tpl->strip_vars = true;
        $tpl->strip_double = true;

        $func = ($this->view->page_modal) ? 'getPageInModal' : 'getPageIn'; 

        // $data['content'] = &$tpl->tplParseString($this->view->getPageIn($this->manager), $this->vars);
        $data['content'] = &$tpl->tplParseString($this->view->$func($this->manager), $this->vars);
        $data['content'] .= "\n" . $this->view->runAjax();

        $data['meta_title'] = $this->getMetaTitle();
        $data['meta_keywords'] = $this->getMetaKeywords();
        $data['meta_description'] = $this->getMetaDescription();
        $data['meta_charset'] = $this->getMetaCharset();
        $data['meta_content_lang'] = $this->getMetaContentLang();
        $data['meta_robots'] = $this->getMetaRobots($this->view->view_id, $this->view->meta_robots);

        //echo "<pre>"; print_r($data['meta_title']); echo "</pre>";
        //echo "<pre>"; print_r($data['meta_keywords']); echo "</pre>";
        //echo "<pre>"; print_r($data['meta_description']); echo "</pre>";

        $str = '<link rel="stylesheet" type="text/css" href="%s" />';
        foreach($this->view->style_css as $v) {
            $css[] = sprintf($str, $v . '?v=' . $this->vars['pvhash']);
        }

        $data['style_css_links'] = implode("\n\t", $css) . "\n";
        $data['rss_head_links'] = $this->view->getRssHeadLinks($this->manager);

        $tpl->tplAssign($this->view->css);
        $tpl->tplAssign($this->getTemplateVars());

        $_tmsg = ['ok_msg', 'cancel_msg', 'open_new_window_msg', 'download_msg', 'or_msg'];
        $_tmsg = array_intersect_key(RequestDataUtil::stripVars($this->view->msg), array_flip($_tmsg));
        $_tmsg['or_msg'] = _strtolower($_tmsg['or_msg']);
        $tpl->tplAssign($_tmsg);
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        
        $debug_enabled = (empty($conf['debug_info'])) ? 0 : 1;
        $tpl->tplAssign('debug', $debug_enabled);
        
        $debug = DebugUtil::getDebugInfo();
        $tpl->tplAssign('kbp_debug_info', $debug);

        // msg in growl
        @$msg_key = $_SESSION['success_msg_'];
        if($msg_key) {
            @list($msg_key, $msg_format) = explode('-', $msg_key);
            $msg = AppMsg::getMsg('after_action_msg.ini', 'public', $msg_key);
            @$tpl->tplAssign('growl_title', $msg['title']);
            @$tpl->tplAssign('growl_body', $msg['body']);
            $tpl->tplAssign('growl_show', 1);

            if($msg_format == 'error') {
                $tpl->tplAssign('growl_fxed', 1);
                $tpl->tplAssign('growl_style', 'error');
            }

            $_SESSION['success_msg_'] = false;
        }

        // trying to get adodb debug  
        // $debug = ob_get_contents();
        // if(strlen($debug)) {
        //     ob_clean();
        //     echo sprintf('<div id="debug">%s</div>', $debug);
        // }

        $tpl->tplParse(array_merge($this->vars, $data));
        return $tpl->tplPrint(1);
    }


    function renderPrint($is_base_href = false) {

        $tpl = new tplTemplatez($this->view->getTemplate('page_print.html'));
        $tpl->strip_vars = true;
        $tpl->strip_double = true;

        $data['content'] = &$tpl->tplParseString($this->view->execute($this->manager), $this->vars);
        $data['meta_title'] = $this->view->meta_title;
        $data['meta_charset'] = $this->getMetaCharset();
        $data['meta_content_lang'] = $this->getMetaContentLang();
        $data['meta_robots'] = 'none';

        if($is_base_href) { // saved, attached to email
            $tpl->tplSetNeeded('/base_href');
            $tpl->tplSetNeeded('/anchor_js');
            $tpl->tplAssign('http_host', $this->getHttpHost());
        } else {
            $tpl->tplSetNeeded('/print_js');
        }

        $tpl->tplAssign($this->view->css);
        $tpl->tplAssign($this->getTemplateVars());

        $tpl->tplParse(array_merge($this->vars, $data));
        return $tpl->tplPrint(1);
    }


    function renderPopup() {
        
        $tpl = new tplTemplatez(APP_TMPL_DIR . 'page_popup_client.html');
        $tpl->strip_vars = true;
        $tpl->strip_double = true;

        $data['content'] = &$tpl->tplParseString($this->view->execute($this->manager), $this->vars);
        $data['meta_title'] = $this->view->meta_title;
        $data['meta_charset'] = $this->getMetaCharset();
        $data['meta_content_lang'] = $this->getMetaContentLang();

        $tpl->tplAssign($this->view->css);
        $tpl->tplAssign($this->getTemplateVars());

        if($ajax2 = AppAjax::processRequests()) {
            $tpl->tplAssign('xajax_js', $ajax2);
        }

        $tpl->tplParse(array_merge($this->vars, $data));
        return $tpl->tplPrint(1);
    }
    

    // will required for attached article to email to correctly find images path
    function getHttpHost() {
        $http = (!empty($this->view->conf['ssl_client'])) ? 'https://' : 'http://';
        $path = (!empty($_SERVER['HTTP_HOST'])) ? $http . $_SERVER['HTTP_HOST'] : '';
        return $path;
    }


    function assign($var, $value) {
        $this->vars[$var] = $value;
    }


    function getHtmlTemplate($setting) {

        $page_to_load = $setting['page_to_load_tmpl'];
        $tmpl = explode('--delim--', $page_to_load);
        
        $header = $tmpl[0];
        // $footer = (isset($tmpl[1])) ? $tmpl[1] : '';
        $head_code = (isset($tmpl[2])) ? $tmpl[2] : '';
        $submit_btn_str = 'input.button[type=submit].button:not(.secondary), input.button.primary, button.primary';

        $setting_to_css = array(
            'header_background' => '#header_div {background-color: %s !important;}',
            'menu_background' => '.menuBlock {background-color: %s;}',
            'footer_background' => '.footer_info {background-color: %s;}',
            'menu_item_background' => '.menuItem {background-color: %s;}',
            'menu_item_background_hover' => '.menuItem:hover, .menuItem > a:hover {background-color: %s !important;}',
            'menu_item_background_selected' => '.menuItemSelected {background-color: %s;}',
            'header_color' => 'a.header {color: %s !important;} svg.header {fill: %s}',
            'menu_item_color' => '.menuItem a {color: %s !important;}',
            'menu_item_color_selected' => '.menuItemSelected a {color: %s !important;}',
            'menu_item_bordercolor' => '.menuItem {border-color: %s;}',
            'menu_item_bordercolor_selected' => '.menuItemSelected {border-color: %s;}',
            'action_icon_background_hover' => '.round_icon_hover {background-color: %s;}',
            'login_btn_background' => '.button.login2.normal {background-color: %s !important;}',
            'login_btn_color' => '.button.login2.normal {color: %s !important;}',
            'submit_btn_background' => $submit_btn_str . ' {background-color: %s;}',
            // 'submit_btn_color' => $submit_btn_str . ' {color: %s;}',
            // 'submit_btn_bordercolor' => $submit_btn_str . ' {border-color: %s;}',
            'login_color' => 'div.login, a.login, #login_button {color: %s !important;} div.login svg, svg.login { fill: %s; } svg.login line { stroke: %s; } #login_button {border: 1px solid %s;}',
            'left_menu_width' => '#menu_content, #sidebar {width: %spx;}',
        );

        $css = array();
        foreach ($setting_to_css as $k => $v) {
            if (!empty($setting[$k])) {
                if ($k == 'left_menu_width') {
                    $css[] = sprintf($v, $setting[$k]);
					
				} elseif ($k == 'login_color') {
				    list($r, $g, $b) = sscanf($setting[$k], '#%02x%02x%02x');
				    $border_str = sprintf('rgb(%d, %d, %d, 0.3)', $r, $g, $b);
                    
                    $css[] = sprintf($v, $setting[$k], $setting[$k], $setting[$k], $border_str);
					
                } elseif ($k == 'header_color') {
                    $css[] = sprintf($v, $setting[$k], $setting[$k]);
					
                }  else {
                    $css[] = sprintf($v, $setting[$k]);
                }

            } else {
                if ($k == 'menu_item_background_hover' && (!empty($setting['menu_item_background']))) {
                    $css[] = sprintf($v, $setting['menu_item_background']);
                }
            }
        }
        
        $css = '<style type="text/css" media="screen">' . "\n" . implode("\n", $css) . "\n" . '</style>';
        $head_code .= "\n" . $css;

        $page_tmpl = $this->view->getTemplate($this->view->page_template);
        $page = explode('{content}', file_get_contents($page_tmpl));

        $head = str_replace('{custom_template_head}', $head_code, $page[0]);
        $header = sprintf('<div id="custom_header">%s</div>', $header);
        
        $page = sprintf("%s\n\n%s\n\n{content}\n\n%s", $head, $header, $page[1]);
        
        return $page;
    }


    function getTemplate($page_to_load) {
        if(strpos($page_to_load, '[file]') !== false) {
            $page = trim(str_replace('[file]', '', $page_to_load));
            
            $login_status = ($this->manager->is_registered) ? 1 : 0;
            $page = str_replace('[login_status]', $login_status, $page);
            
        } else {
            $page = $this->view->getTemplate($this->view->page_template);
        }

        return $page;
    }


    // set title of page
    function getMetaTitle() {

        $data = array();
        
        if($this->view->meta_title) {
            $data[] = $this->view->meta_title;
        }
        
        if($this->manager->getSetting('site_title')) {
            $data[] = $this->manager->getSetting('site_title');
        }

        return implode($this->title_delim, $data);
    }

    
    private function getMeta($var) {
        if(empty($var)) {
            return $var;
        }
        
        return str_replace(array("\r\n", "\n", "\r"), ' ', $var);
    }


    function getMetaKeywords() {
        return $this->getMeta($this->view->meta_keywords);
    }


    function getMetaDescription() {
        return $this->getMeta($this->view->meta_description);
    }


    function getMetaRobots($view, $view_meta_robots) {

        // array with view keys for what we use
        //<meta name="robots" content="all">
        $all_arr = array('index', 'entry', 'glossary', 'rssfeed', 'files', 'comment', 'news');
        $meta_robots = 'NONE';

        if(in_array($view, $all_arr)) {
            $meta_robots = 'ALL';
        }

        if(!$view_meta_robots) {
            $meta_robots = 'NONE';
        }

        return $meta_robots;
    }


    function getMetaCharset() {
        return $this->view->conf['lang']['meta_charset'];
    }


    function getMetaContentLang() {
        return $this->view->conf['lang']['meta_content'];
    }


    function getCache() {

        $cache = new ResultCache();
        //$cache->refresh = true;
        
         // time in minute after that cache will expiered, 0 = never expiered
        $cache->exp_time = 60*24; // 24 hours 
        $cache->cache_dir = APP_CACHE_DIR;
        $cache->setUserFuncVars(array($this, '_render'));

        $roles = '';
        if($roles = AuthPriv::getRoleId()) {
            sort($roles);
            $roles = implode(',', $roles);
        }

        $md5 = md5($_SERVER['PHP_SELF'] . $_SERVER['QUERY_STRING'] . AuthPriv::getPrivId() . $roles);
        $cache_id = '.ht_cache_' . $md5;
        
        return $cache->getData($cache_id);
    }

    
    function render() {
        return $this->_render();
        
        // timestart("cache");
        // $ret = $this->getCache();
        // // $ret = $this->_render();
        // timestop("cache");
        // return $ret;
    }


    function display() {
        echo $this->render();
    }
}
?>