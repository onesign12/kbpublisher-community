<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007-2021 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+

 
class AppPlugin
{
    
    // menu => 1 admin, 2 public, 3 both - whether plugin is used in menu
    // pages => [] pages to hide
    // automation => [] automation action to diable
    // settings => [] settings to hide
    // setting_id => '' to hide whole setting tab from search result 
    // column => [] hide item in list view 
    // ssearch => ''  hide item in special search filter 
    // tabs => [] view or action tabs in entry view and acrions in list in admin
    // currently used in module files and disabled in common  
    // cron => [] scheduled tasks to skip
    // mailpool => [] skip filters in mail pool list screen 
    // loginlog => [] skip auth filters in login log screen
    // views => [] check Views in public area
    // activity => [] skip user activity check
    // portlet => [] to hide portlrts on home and stat page 
    // setup = [] to hide/skip setup test 
    // articles => [] articles ids in KB
    // letter => [] letter templates to hide
    // list => [] hide in lists 
    
    // should keep unique values for parameters
    // getPluginsFiltered with revert = true dispaly singe result only
    // wrong: 
    // 'news' => [
    //     'cron' => ['processNewsSubscription']
    // ],
    // 'report' => [
    //     'cron' => ['processNewsSubscription']
    
    
    static $plugins = [
        'auth' => [
            'menu' => 1,
            'pages' => ['auth_setting'],
            'setting_id' => [160, 162, 163, 164],
            'loginlog' => [8,7,2,9]
        ],
        'automation' => [
            'menu' => 1,
            'pages' => ['automation'],
            'cron' => ['executeAutomations', 'executeEmailAutomations'],
            'mailpool' => [20],
            'activity' => ['getDependentAutomations']
        ],
        // 'copyright' => [
        // 
        // ],
        'draft' => [
            'menu' => 1,
            'pages' => ['kb_draft', 'file_draft', 'workflow'],
            'mailpool' => [21],
            'cron' => ['deleteWorkflowHistoryNoEntry'],
            'automation' => 'create_draft',
            'activity' => ['getAssignedDrafts', 'getDependentWorkflows'],
            'portlet' => ['draft_file', 'draft_article', 'approval'],
            'tabs' => ['draft', 'move_to_draft'],
            'letters' => ['draft_approval_request', 'draft_rejection', 'draft_rejection_to_approver', 'draft_publication'],
            'tables' => ['entry_draft', 'entry_draft_to_category', 'entry_draft_workflow', 'entry_draft_workflow_history', 'entry_draft_workflow_to_assignee'],
            'sphinx' => ['ArticleDraft', 'FileDraft'],
        ],
        'export' => [
            'menu' => 1,
            'pages' => ['export_setting'],
            // 'pages_update' => ['plugin_setting' => ['setting', 'plugin_setting', 'sphinx_setting']],
            'settings' => ['show_pdf_link'/*, 'plugin_htmldoc_path', 'plugin_wkhtmltopdf_path', 'plugin_wkhtmltopdf_dpi', 'plugin_wkhtmltopdf_margin_top', 'plugin_wkhtmltopdf_margin_bottom', 'show_pdf_category_link', 'show_pdf_link_entry_info', 'htmldoc_bodyfont', 'htmldoc_fontsize', 'plugin_export_cover', 'plugin_export_header', 'plugin_export_footer'*/],
            'setting_id' => [140],
            // 'func' => ['BaseModel', 'getExportTool'],
            'views' => ['pdf'], //in public area
            'setup' => 'export',
            'tables' => ['export', 'export_data']
        ],
        'frules' => [
            'menu' => 1,
            'settings' => ['aws_s3_allow2'],
            'pages' => ['file_rule'],
            'cron' => ['spyDirectoryFiles'],
            'activity' => ['getUserDirectoryRules']
        ],
        'news' => [
            'menu' => 3, // 1 admin, 2 public, 3 both
            'menu_id' => 'news', // in public
            'pages' => ['rs_news', 'ft_news'],
            'automation' => 'create_news', // automation action to diable
            'settings' => ['allow_subscribe_news', 'subscribe_news_interval', 'subscribe_news_time'], // settings to hide
            'cron' => ['processNewsSubscription'],
            'letters' => ['subscription_news'],
            'tables' => ['news'],
            'sphinx' => ['News'],
        ],
        // 'feedback' => [
        //     'menu' => 1, // 1 admin, 2 public, 3 both
        //     // 'menu_id' => 'contact_us', // in public
        //     // 'views' => ['contact'], // in public
        //     'pages' => ['kb_rate', 'kb_comment'],
        //     'related' => ['feedback' => 'feedback'],
        //     'settings' => [/*'allow_contact', 'contact_attachment', 'contact_attachment_email', 'contact_attachment_ext', 'contact_quick_responce', 'contact_captcha',*/ 'allow_rating_comment', 'allow_comments', 'comment_captcha', 'comment_policy', 'num_comments_per_page', 'num_comments_entry_page', 'comments_entry_page', 'comments_author_format', 'allow_subscribe_comment', 'show_comments', 'preview_show_comments', 'allow_rating', 'rating_type', 'preview_show_rating'],
        //     'column' => ['comment_num', 'rcomment_num', 'votes_num', 'rating'],
        //     'portlet' => ['article_feedback', 'comment'],
        //     'lists' => [/*feedback_subj',*/ 'rate_status'],
        //     // 'cron' => [],
        //     'mailpool' => [14],
        //     'letters' => [/*'contact', 'answer_to_user',*/ 'comment_approve_to_admin', 'rating_comment_added', 'subscription_comment'],
        //     'tables' => ['kb_comment', 'kb_rating_feedback'],
        //     'sphinx' => ['Comment', 'RatingFeedback'],
        // ],
        'mustread' => [
            'menu' => 1,
            'pages' => ['account_mustread', 'report_mustread'],
            'column' => ['mustread'], 
            'ssearch' => 'mustread', 
            'cron' => ['populateUsersToMustread', 'appendUsersToMustread', 'notifyUsersAboutMustread', 'remindUsersAboutMustread', 'disactivateExpiredMustreads'],
            'mailpool' => [5],
            'letters' => ['mustread_entry'],
            'tables' => ['entry_mustread','entry_mustread_to_rule','entry_mustread_to_user']
        ],
        'history' => [
            'tabs' => ['history'], 
            'settings' => ['entry_history_max', 'file_history_max'], 
            'cron' => ['upgradeHistory', 'deleteArticleHistoryNoEntry', 'deleteFileHistoryNoEntry'],
            'tables' => ['file_entry_history', 'kb_entry_history']
        ],
        'private' => [
            'menu' => 1,
            // 'pages' => ['role'],
            'settings' => ['private_policy', 'show_private_block'],
            'column' => ['private'],
            'ssearch' => 'private',
            'cron' => '[inheritCategoryPrivateAttributes]',
            'articles' => [234]
        ],
        'report' => [
            'menu' => 1,
            'pages' => ['report_usage', 'report_entry', 'report_user', 'report_stat'],
            'related' => ['mustread' => 'report_mustread'],
            'settings' => ['user_activity_time'],
            'cron' => ['updateReportEntry', 'updateReportSummary', 'updateSearchReport', 'syncUserActivityReport'],
            'tables' => ['report_entry','report_summary', 'user_activity', 'report_search']
        ],
        'fields' => [
            'menu' => 1,
            'pages' => ['field_tool'],
            'ssearch' => 'custom_id',
            'tables' => ['custom_field', 'custom_field_range', 'custom_field_range_value', 'custom_field_to_category', 'news_custom_data', 'feedback_custom_data', 'file_custom_data', 'kb_custom_data'],
        ],
    ];
    
    
    // use this to hide some modules, functionality
    // now always return false if never installed or expired 
    // wwe may need some more like 0 - disabled maybe
    static function isPlugin($plugin, $custom_args = []) {
        static $plugins = [];
        if(!isset($plugins[$plugin])) {
            $plugins_on = self::getPluginsOn();
            $plugins[$plugin] = in_array($plugin, $plugins_on);
            
            // to call some custom functions like getExportTool
            // maybe no nedded this complications
            // if($plugins[$module]) {
            //     if($custom_args && $custom_func = self::getPluginData($module, 'func')) {
            //         $plugins[$module] = call_user_func_array($custom_func, $custom_args);
            //     }
            // }
        }
        
        return $plugins[$plugin];
    }
    
    
    static function getPluginData($plugin, $key = false) {
        if($key) {
            return isset(self::$plugins[$plugin][$key]) ? self::$plugins[$plugin][$key] : false;
        } else {
            return self::$plugins[$plugin];
        }
    }
    
    
    static function getPluginsOn() {
        static $plugins;
        if($plugins === null) {
            $plugins = include APP_PLUGIN_DIR . 'plugins.php';
        }
            
        return $plugins;
    }    
    
    static function getPluginsAll() {
        return array_keys(self::$plugins);
    }
    
    
    static function getPluginsFiltered($key, $revert = false) {
        $filtered = array_filter(self::$plugins, function ($var) use ($key) {
            return (isset($var[$key]));
        });
        
        if($revert) {
            $filtered = self::revertFiltered($filtered, $key);
        }
        
        return $filtered;
    }
    
    
    static function getPluginsFilteredOff($key, $revert = false) {
        $filtered = self::getPluginsFiltered($key);
        $plugins_off = array_diff_key($filtered, array_flip(self::getPluginsOn()));
        
        if($revert) {
            $plugins_off = self::revertFiltered($plugins_off, $key);
        }
        
        return $plugins_off;
    }
    
    
    private static function revertFiltered($filtered, $key) {
        $reverted = [];
        foreach($filtered as $plugin => $v) {
            if(is_array($v[$key])) {
                foreach($v[$key] as $k) {
                    $reverted[$k] = $plugin;
                }
            } else {
                $reverted[$v[$key]] = $plugin;
            }
        }
        
        return $reverted; 
    }
    
    
    // get plugins with menu
    static function getModules($menu_type = 3) {
        $modules = self::getPluginsFiltered('menu');
        $modules = array_filter($modules, function ($var) use ($menu_type) {
            return ($var['menu'] & $menu_type);
        });
        
        return $modules;
    }
    
    
    // to hide/skip settings related to plugin
    static function getPluginHideSettings() {
        $plugins_off = self::getPluginsFilteredOff('settings');
        
        $hide = [];
        foreach($plugins_off as $v) {
            $hide = array_merge($hide, $v['settings']);
        }
        
        return $hide;
    }
    
    
    // to hide tabs related to plugin
    static function getModulesPages($module) {
        return @self::$plugins[$module]['pages'] ?: array();
    }
    
    
    // to change menu link if default is hidden
    static function getModulesUpdateNav($module) {
        return @$pages[$module]['pages_update'] ?: array();
    }
    
    
    // to not hide tab if inside we have allowed module
    static function isModuleRelated($module) {
        $ret = array();
        if(isset(self::$plugins[$module]['related'])) {
            foreach(self::$plugins[$module]['related'] as $k => $epage) {
                if($k == $module) {
                    $ret['epage'] = $epage;
                } else {
                    if(self::isPlugin($k)) {
                        $ret = array('epage' => $epage);
                        break;
                    }
                }
            }
        }
        
        return $ret;
    }
    
}
?>