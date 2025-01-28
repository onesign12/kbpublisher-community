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


class KBClientMenu
{

    var $utf_replace = true; // replace bad sign to ? to avoid error with ajax
    var $entry_menu_max_len = 50; // nums signs to leave in menu items, set to 0 to disable cut off
    var $display_parent_entry = false; 


    function __construct(&$view) {

        $this->view =& $view;

        $this->view_id = $view->view_id;
        $this->entry_id = $view->entry_id;
        $this->category_id = $view->category_id;

        $this->kb_path = $view->controller->kb_path;

        $this->loadUtf8Lib();
    }


    function parseTopCategyJsMenu($categories, $parse_dropdown = true) {

        $view_id = $this->getViewIdIndex();
        $top_cats = array_filter($categories, array($this, 'array_filter_callback_top_cats'));

        $tpl = new tplTemplatez($this->view->getTemplate('block_top_cat_menu.html'));

        if($parse_dropdown) {
            $tpl->tplSetNeeded('/dropdown');
        }

        foreach(array_keys($top_cats) as $cat_id) {
            $v = array();
            $v['name'] = $top_cats[$cat_id]['name'];

            $cat_id_params = $this->view->controller->getEntryLinkParams($cat_id, $v['name']);
            $v['link'] = $this->view->getLink($view_id, $cat_id_params);

            @$i++;
            if($i > 15) {
                $v['name'] = '...';
                $v['link'] = $this->view->getLink('index');
                $tpl->tplParse($v, 'row');
                break;
            }

            $tpl->tplParse($v, 'row');
        }

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function array_filter_callback_top_cats($v) {
        return $v['parent_id'] == 0;
    }

    // UTILS // ----------------

    function getViewIdIndex() {
        $view_id = (in_array($this->view_id, $this->view->files_views)) ? 'files' : 'index';
        $view_id = ($this->view_id == 'news') ? 'news' : $view_id;
        return $view_id;
    }

    function getViewIdEntry() {
        $view_id = (in_array($this->view_id, $this->view->files_views)) ? 'files' : 'entry';
        $view_id = ($this->view_id == 'news') ? 'news' : $view_id;
        return $view_id;
    }


    // to parse single items, mostly category entry
    function stripVarStatic($str) {
        if($this->utf_replace) {
            $str = $this->replaceBadUtf8String($str);
        }
        
        return $this->view->stripVars($str);
    }


    function loadUtf8Lib() {

        if(strtolower($this->view->encoding) != 'utf-8') {
            $this->utf_replace = false;
        }

        // only in ajax
        if(empty($_GET['ajax'])) {
            $this->utf_replace = false;
        }

        if($this->utf_replace) {
            require_once 'utf8/utils/validation.php';
            require_once 'utf8/utils/bad.php';
        }
    }


    function replaceBadUtf8(&$arr, $parse_keys = array()) {
        if($this->utf_replace) {
            foreach(array_keys($arr) as $k) {
                if(is_array($arr[$k])) {
                    $this->replaceBadUtf8($arr[$k], $parse_keys);
                } else {
                    if(in_array($k, $parse_keys)) {
                        $arr[$k] = $this->replaceBadUtf8String($arr[$k]);
                    }
                }
            }
        }
    }


    function replaceBadUtf8String($str) {
        if(!utf8_compliant($str)) {
            $str = utf8_bad_replace($str, '?');
        }

        return $str;
    }


    function getIcon($name, $attributes = '') {
        
        $alt = '';
        if(strpos($name, 'expanded') !== false) {
            $alt = 'menu collapse';
        } elseif(strpos($name, 'collapsed') !== false) {
            $alt = 'menu expand';
        } elseif(strpos($name, 'entry') !== false) {
            $alt = 'menu item';
        }
        
        $str = '<img src="%sclient/images/icons/%s.svg" alt="%s" %s />';
        return sprintf($str, $this->kb_path, $name, $alt, $attributes);
    }
}

?>