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


class PluginView_list extends AppView
{
    
    var $template = 'list.html';
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('setting_msg.ini', 'plugin_setting');
    
    
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $link = $this->getActionLink('insert');
        $button = array($this->msg['add_plugins_msg'] => $link);
        $button = false;
        
        $tpl->tplAssign('header', $this->commonHeaderList('', '', $button));

        // get records
        $plugins = $this->stripVars($manager->getPlugins());
        if(!$plugins) {
            $plugins = [3 => AppPlugin::getPluginsAll()];
        }    
        
        $group_sorting = [1,2,3];
        
        $t_id = 0;
        $i = 1;
        foreach($group_sorting as $group_id) {
            if(!empty($plugins[$group_id])) {
                $tpl->tplSetNeeded('row/group');
                if($i != 1) { $tpl->tplSetNeeded('block/group_delim'); }
                $i++;
            } else {
                continue;
            }

            $key_last = end($plugins[$group_id]);

            foreach($plugins[$group_id] as $plugin_key) {
                
                if ($plugin_key == $key_last) {
                    $tpl->tplAssign('end', '</div>');
                } else {
                    $tpl->tplAssign('end', '');
                }

                $tpl->tplAssign('group_title', $this->msg['group_title'][$group_id]);
                
                $v = [
                    'key' => $plugin_key,
                    'value' => $group_id,
                    'pchecked' => $this->getChecked(($group_id == 1)),
                    'pdisabled' => $this->getDisabled(($group_id === 3)),
                ];
                
                if($group_id != 3) {
                    $tpl->setNeeded('checkbox/');
                }
                
                $tpl->tplAssign($v);
                
                $tpl->tplParse($this->msg[$plugin_key], 'row');
            }
        }
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('setPluginStatus', $this, 'ajaxSetPluginStatus'));
        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function ajaxSetPluginStatus($plugin, $status) {
        
        $objResponse = new xajaxResponse();
        
        $plugins2 = [];
        $plugins = $this->manager->getPlugins();
        
        foreach([1,2] as $group_id) {
            if(isset($plugins[$group_id])) {
                foreach($plugins[$group_id] as $key) {
                    $plugins2[$key] = $group_id;
                }
            }
        }
        
        // echo '<pre>' . print_r($plugins2, 1) . '</pre>';
        // 
        $status_new = ($status == 1) ? 2 : 1;
        $plugins_new = [];
        if(isset($plugins2[$plugin])) {
            $plugins2[$plugin] = $status_new;
            foreach($plugins2 as $key => $group_id) {
                $plugins_new[$group_id][] = $key;
            }
        
            $plugins_new[3] = $plugins[3];
            // echo '<pre>' . print_r($plugins_new, 1) . '</pre>';
            
            $this->manager->savePlugins($plugins_new);
        
            // $msg = AppMsg::getMsg('setting_msg.ini', 'plugin_setting');
            $msg_key = ($status_new == 1) ? 'enabled_msg' : 'disabled_msg';
            // echo '<pre>' . print_r($this->msg, 1) . '</pre>';
            $msg = $this->msg[$msg_key];
        
            $script = sprintf('$("#plugin_%s .action_msg").html("(%s)");', $plugin, $msg);
            $objResponse->script($script);
        }

        $objResponse->addAlert($plugin);

        return $objResponse;
    }
    
}
?>