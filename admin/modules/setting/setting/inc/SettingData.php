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


class SettingData
{
    
    static $main_menu = array(
        'article' => array(
            'views' => array(
                'index', 'entry', 'comment', 'comments',
                'afile', 'recent', 'popular', 'featured', 'category'
            ),
            'dropdown' => 0 // cuurently no in use from here, for future
        ),
        
        'news' => array(
            'views' => array(
                'news'
            ),
            'setting' => 'module_news',
            'dropdown' => 0
        ),
        
        'file' => array(
            'views' => array(
                'files', 'file', 'download', 'fsearch'
            ),
            'setting' => 'module_file',
            'dropdown' => 0
        ),
        
        'contact_us' => array(
            'views' => array(
                'contact'
            ),
            'setting' =>  'module_contact',
            'dropdown' => 0,
        ),
        
        'glossary' => array(
            'views' => array(
                'glossary'
            ),
            'setting' => 'module_glossary',
            'dropdown' => 1
        ),
        
        'tags' => array(
            'views' => array(
                'tags'
            ),
            'setting' => 'module_tags',
            'dropdown' => 1
        ),
        
        'map' => array(
            'views' => array(
                'map'
            )
        )
    );
    
    
    static function getMenuFiltered($key, $revert = false) {
        $filtered = array_filter(self::$main_menu, function ($var) use ($key) {
            return (isset($var[$key]));
        });
        
        if($revert) {
            $filtered = self::revertFiltered($filtered, $key);
        }
        
        return $filtered;
    }
    
    
    private static function revertFiltered($filtered, $key) {
        $reverted = [];
        foreach($filtered as $item => $v) {
            if(is_array($v[$key])) {
                foreach($v[$key] as $k) {
                    $reverted[$k] = $item;
                }
            } else {
                $reverted[$v[$key]] = $item;
            }
        }
        
        return $reverted; 
    }
    
    
    static function getMainMenu($items) {
        $data = array();
        foreach ($items as $k => $rows) {
            $data[$k] = array();
            foreach ($rows as $k1 => $item) {
                $data[$k][] = $item;
            }
        }
        
        return $data;
    }
    
    
    static $sharing_sites = array(
        'twitter' => array(
            'title' => 'Twitter',
            'url' => 'http://twitter.com/intent/tweet?url=[url]',
            'icon' => '{client_href}images/icons/social/twitter.svg',
            'color' => '#55acee'
         ),
        'facebook' => array(
            'title' => 'Facebook',
            'url' => 'http://facebook.com/sharer.php?u=[url]&title=[title]',
            'icon' => '{client_href}images/icons/social/facebook.svg',
            'color' => '#3b5998'
         ),
        'google' => array(
            'title' => 'Google Plus',
            'url' => 'https://plus.google.com/share?url=[url]',
            'icon' => '{client_href}images/icons/social/google.svg',
            'color' => '#dc4e41'
         ),
        'linkedin' => array(
            'title' => 'LinkedIn',
            'url' => 'https://www.linkedin.com/cws/share?url=[url]&title=[title]',
            'icon' => '{client_href}images/icons/social/linkedin.svg',
            'color' => '#007ab9'
        ),
        'reddit' => array(
            'title' => 'Reddit',
            'url' => 'http://www.reddit.com/submit?url=[url]&title=[title]',
            'icon' => '{client_href}images/icons/social/reddit.svg',
            'color' => '#ff3f18'
        ),
        'digg' => array(
            'title' => 'Digg',
            'url' => 'https://digg.com/submit?url=[url]&title=[title]',
            'icon' => '{client_href}images/icons/social/digg.svg',
            'color' => '#000000'
        ),
        'delicious' => array(
            'title' => 'Delicious',
            'url' => 'https://delicious.com/save?v=5&noui&jump=close&url=[url]&title=[title]',
            'icon' => '{client_href}images/icons/social/delicious.svg',
            'color' => '#000000'
        ),
        'stumpleupon' => array(
            'title' => 'StumpleUpon',
            'url' => 'http://www.stumbleupon.com/submit?url=[url]&title=[title]',
            'icon' => '{client_href}images/icons/social/stumbleupon.svg',
            'color' => '#ef4e23'
        ),
        'vk' => array(
            'title' => 'VK',
            'url' => 'http://vk.com/share.php?url=[url]',
            'icon' => '{client_href}images/icons/social/vk.svg',
            'color' => '#4a76a8'
        ),
    );
    
    
    /*
     * $items is a one-dimensional array
     * e.g. [save, facebook, custom_123]
     * */
    static function getEntryBlockItems($items, $manager, $view, $entry_data = false) {
        $data = array();
        
        if($manager->getSetting('show_share_link')) {
            $social_tems = SettingModel::getQuick(100, 'item_share_link');
            $social_items = unserialize($social_tems);
        } else {
            $social_items = array();
            $social_items['active'] = array();
        }
        
        foreach ($items as $item) {
            if (!empty(SettingData::$sharing_sites[$item])) { // built-in social
                if (!in_array($item, array_values($social_items['active']))) {
                    continue;
                }
                
                $url = SettingData::$sharing_sites[$item]['url'];
                $data[$item] = array(
                    'title' => SettingData::$sharing_sites[$item]['title'],
                    'icon' => SettingData::$sharing_sites[$item]['icon'],
                    'url' => $url,
                    'color' => SettingData::$sharing_sites[$item]['color'],
                    'link' => '#',
                    'attr' => sprintf('onclick="shareArticle(\'%s\');"', $url)
                );
                
            } elseif(substr($item, 0, 7) == 'custom_') { // custom social
                $custom_id = substr($item, 7);
                $item_present = false;
                
                foreach ($social_items['active'] as $v) {
                    if (is_array($v) && $v['id'] == $custom_id) {
                        $item_present = true;
                        break;
                    }
                }
                
                if (!$item_present) {
                    continue;
                }
                
                if (!empty($v['icon'])) {
                    $v['icon'] = sprintf('data:image/svg+xml;base64,%s', base64_encode($v['icon']));
                }
                
                $data[$item] = array(
                    'title' => $v['title'],
                    'icon' => $v['icon'],
                    'url' => $v['url'],
                    'color' => '',
                    'link' => '#',
                    'attr' => sprintf('onclick="shareArticle(\'%s\');"', $v['url'])
                );
                
            } else {
                $method_name = sprintf('getEntry%sItem', ucwords($item));
                if (method_exists(__CLASS__, $method_name)) {
                    $block_data = self::$method_name($manager, $view, $entry_data);
                    if (is_array($block_data)) {
                        $data[$item] = $block_data;
                        
                        $title_key = self::$panel_items[$item]['title'];
                        $data[$item]['title'] = $view->msg[$title_key];
                        
                        $data[$item]['icon'] = self::$panel_items[$item]['icon'];
                        
                        if (!empty(self::$panel_items[$item]['icon2'])) {
                            $data[$item]['icon2'] = self::$panel_items[$item]['icon2'];
                        }
                    }
                    
                } else {
                    $data[$item] = array();
                }
            }
        }
        
        return $data;
    }


    static function getEntryPrintItem($manager, $view, $entry_data) {
        if($manager->getSetting('show_print_link')) {
            $data = array();
            
            if ($entry_data) {
                $view_id = ($view->view_id == 'news') ? 'print-news' : 'print';
                $category_id = ($view->view_id == 'entry') ? $entry_data['category_id'] : false;
                $data['link'] = $view->controller->getLink($view_id, $category_id, $entry_data['id']);
            }
            
            return $data;
        }
    }
    
    
    static function getEntryPdfItem($manager, $view, $entry_data) {
        if($manager->getSetting('show_pdf_link') && $view->view_id == 'entry') {
            
            if(AppPlugin::isPlugin('export') && BaseModel::getExportTool()) {
                $data = array();
                
                if ($entry_data) {
                    $data['link'] = $view->controller->getLink('pdf', $entry_data['category_id'], $entry_data['id']);
                    
                    if ($view->controller->mod_rewrite == 3) {
                        $entry_link = $view->controller->getEntryLinkParams($entry_data['id'], $entry_data['title'], $entry_data['url_title']);
                        $data['link'] = $view->controller->getLink('entry', false, $entry_link);
                        $data['link'] = str_replace('.html', '.pdf', $data['link']);
                    }
                    
                    $data['attr'] = 'onclick="showLoading();"';
                }

                return $data;
            }
        }
    }
    
    
    static function getEntrySaveItem($manager, $view, $entry_data) {
        if($manager->getSetting('show_save_link', $manager) && $view->view_id == 'entry') {
            $data = array();
            $data['link'] = '#';
            
            return $data;
        }
    }


    static function getEntryStickItem($manager, $view, $entry_data) {
        if($manager->getSetting('show_pool_link') && $view->view_id == 'entry') {
            $data = array();
            
            if ($entry_data) {
                $data['link'] = '#';
            }
            
            return $data;
        }
    }
    
    
    static function getEntryShareItem($manager, $view, $entry_data) {
        $data = array();
        $data['link'] = '#';
        
        return $data;
    }
    
    
    static function getEntryCommentItem($manager, $view, $entry_data) {
        $commentable = ($entry_data) ? $entry_data['commentable'] : 1;
        if($view->view_id != 'entry' && $view->isCommentable($manager, $commentable)) {            
            $data = array();
            
            if ($entry_data) {
                $data['link'] = "#add_comment";
                $data['attr'] = 'onclick="showCommentPanel();"';
                if ($view->comment_form) {
                    $data['attr'] = 'onclick="slideToCommentForm();"';
                }
            }
            
            return $data;
        }
    }
    
    
    static function getEntrySendItem($manager, $view, $entry_data) {
        $mailto = true;
        if($manager->getSetting('show_send_link')) {
            if ($view->view_id == 'news' && !$mailto) {
                return;
            }
            
            $data = array();
            
            if ($entry_data) {
                if ($mailto) {
                    $entry_link = $view->controller->getLink($view->view_id, false, $entry_data['id']);
                    $entry_link = $view->controller->_replaceArgSeparator($entry_link);
                    $msg = AppMsg::getMsg('email_setting/template_msg.ini', false, 'send_to_friend');
                    
                    $subject = rawurlencode($msg['subject']);
                    $body = rawurlencode(sprintf("%s\n%s", $msg['subject'], $entry_link));
                    $send_link = "mailto:?subject=%s&body=%s";
                    $data['link'] = sprintf($send_link, $subject, $body);
                    
                } else {
                    $data['link'] = $view->controller->getLink('send', $entry_data['category_id'], $entry_data['id']);
                }
                
            }
            
            return $data;
        }
    }


    static $panel_items = array(
        'stick' => array(
            'title' => 'pin_msg',
            'desc' => '',
            'icon' => '{base_href}client/images/icons/article_panel/stick.svg'
        ),
        
        'save' => array(
            'title' => 'save_to_list_msg',
            'desc' => '',
            'icon' => '{base_href}client/images/icons/article_panel/bookmark.svg'
        ),
        
        'send' => array(
            'title' => 'send_link_msg',
            'desc' => '',
            'icon' => '{base_href}client/images/icons/article_panel/email.svg'
        ),
        
        'print' => array(
            'title' => 'print_msg',
            'desc' => '',
            'icon' => '{base_href}client/images/icons/article_panel/print.svg',
            'icon2' => '{base_href}client/images/icons/print.svg'
        ),
        
        'pdf' => array(
            'title' => 'article_pdf_msg',
            'desc' => '',
            'icon' => '{base_href}client/images/icons/article_panel/pdf.svg',
            'icon2' => '{base_href}client/images/icons/pdf.svg'
        ),
        
        'comment' => array(
            'title' => 'add_comment_msg',
            'desc' => '',
            'icon' => '{base_href}client/images/icons/article_panel/comment.svg',
            'icon2' => '{base_href}client/images/icons/comment.svg'
        ),
        
        'share' => array(
            'title' => 'share_link_msg',
            'desc' => '',
            'icon' => '{base_href}client/images/icons/article_panel/share.svg'
        )
    );


    static function getFloatPanelItems($manager, $view, $entry_data = array()) {
       
        $data = array();
        
        // all active items are in one place
        $visible_items = SettingModel::getQuick(100, 'float_panel');
        $visible_items = ($visible_items) ? explode(',', $visible_items) : array();
        
        $data['active'] = self::getEntryBlockItems($visible_items, $manager, $view, $entry_data);
        
        
        // dropdown items - social websites first
        $data['inactive'] = array();
        $social_items = SettingModel::getQuick(100, 'item_share_link');
        $social_items = unserialize($social_items);
        
        $dropdown_items = array();
        foreach ($social_items['active'] as $item) {
            $key = (is_array($item)) ? 'custom_' . $item['id'] : $item;
        
            if (!in_array($key, $visible_items)) {
                if (is_array($item)) { // custom
                    $dropdown_items[] = 'custom_' . $item['id'];
                
                } else { // built-in
                    $dropdown_items[] = $item;
                }
            }
        }
        
        foreach (self::$panel_items as $k => $v) {
            if (!in_array($k, $visible_items) && $k != 'share') {
                $dropdown_items[] = $k;
            }
        }
        
        $data['inactive'] = self::getEntryBlockItems($dropdown_items, $manager, $view, $entry_data);
        
        return $data;
    }
    
}

?>