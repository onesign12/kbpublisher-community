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


class KBPReportView_default extends AppView
{
        
    
    function execute(&$obj, &$manager, $data) {

        $this->addMsg('kbpsetup_msg.ini');
        
        $this->template_dir = APP_MODULE_DIR . 'setting/kbpreport/template/';
        $tpl = new tplTemplatez($this->template_dir . 'form.html');


        $file_setting_link = $this->controller->getLink('setting', 'admin_setting');
        $admin_setting_link = $this->controller->getLink('setting', 'admin_setting');
        $plugin_setting_link = $this->controller->getLink('setting', 'plugin_setting', 'export_setting');
        $sphinx_setting_link = $this->controller->getLink('setting', 'plugin_setting', 'sphinx_setting');
        $public_setting_link = $this->controller->getLink('setting', 'public_setting', 'kbc_setting');
        
        $setting_links = array(
            'file_dir' => array(
                'link' => $file_setting_link,
                'settings' => array('file_dir')
             ),
            'html_editor_upload_dir' => array(
                'link' => $admin_setting_link,
                'settings' => array('html_editor_upload_dir')
             ),
            'xpdf' => array(
                'link' => $file_setting_link,
                'settings' => array('file_extract', 'file_extract_pdf')
             ),
            'catdoc' => array(
                'link' => $file_setting_link,
                'settings' => array('file_extract', 'file_extract_doc')
             ),
            'antiword' => array(
                'link' => $file_setting_link,
                'settings' => array('file_extract', 'file_extract_doc2')
             ),
            'htmldoc' => array(
                'link' => $plugin_setting_link
             ),
            'wkhtmltopdf' => array(
                'link' => $plugin_setting_link
             ),
            'spell' => array(
                'link' => $public_setting_link,
                'popup' => 'search_spell_suggest'
             ),
            'sphinx' => array(
                'link' => $sphinx_setting_link
             ),
             'cron' => array(
                'link' => $this->controller->getLink('log', 'cron_log'),
                'title' => $this->msg['logs_msg']
             )
        );
        
        $setting_info = array(
            'cache_dir' => '$conf["cache_dir"] in [kbp_dir]/admin/config.inc.php'
        );

        $instruction_links = array(
            'cron' => 'https://www.kbpublisher.com/kb/Setting-up-scheduled-tasks_238.html',
            'zip' => 'http://php.net/manual/en/zip.setup.php',
            'curl' => 'http://php.net/manual/en/book.curl.php',
            'xpdf' => 'https://www.kbpublisher.com/kb/Enable-searching-in-files_224.html',
            'catdoc' => 'https://www.kbpublisher.com/kb/Enable-searching-in-files_224.html',
            'antiword' => 'https://www.kbpublisher.com/kb/Enable-searching-in-files_224.html',
            'htmldoc' => 'https://www.kbpublisher.com/kb/Enable-exporting-to-PDF_303.html',
            'wkhtmltopdf' => 'https://www.kbpublisher.com/kb/Enable-exporting-to-PDF_303.html',
            'spell' => 'https://www.kbpublisher.com/kb/enable-searching-spell-suggest_402.html',
            'sphinx' => 'https://www.kbpublisher.com/kb/how-to-enable-sphinx-search_437.html'
        );

        
        if (empty($data)) {
            $tpl->tplSetNeeded('/no_report');
        } else {
            
            //xajax
            $ajax = &$this->getAjax($obj, $manager);
            $xajax = &$ajax->getAjax();
            
            $xajax->registerFunction(array('retest', $this, 'ajaxRetest'));
            $this->data = $data;
            
            $rows = unserialize($data['data_string']);
            
            foreach ($manager->items as $group => $v) {
                $v['group_name'] = $this->msg['group_title'][$group];
                
                if($group == 'export' && !AppPlugin::isPlugin('export')) {
                    continue;
                }
                
                foreach($v as $item) {
                    if (empty($rows[$item])) {
                        continue;
                    }
                    
                    $val = $rows[$item];
                    
                    $v['title'] = $this->msg[$item]['title'];
                    $v['descr'] = $this->msg[$item]['descr'];
                    
                    $code_msg = $manager->code[$val['code']];
                    $icon = $manager->icon[$val['code']];
                    
                    $sign = ($val['code'] == 0 || $val['msg']) ? $sign = ': ' : '';
                    $status_msg = '%s <div style="overflow: hidden;text-align: left;"><b>%s</b>%s%s</div><div style="clear: both"></div>';
                    $icon = ($icon) ? sprintf('<div style="float: left;width: 50px;"><img src="images/icons/%s.svg" style="vertical-align: middle;margin-right: 5px;" /></div>', $icon) : '';
                    $v['msg'] = sprintf($status_msg, $icon, $this->msg[$code_msg . '_msg'], $sign, $val['msg']);
                    
    
                    $v['setting'] = '';
                    if(isset($setting_links[$item])) {
                        $str = '<a href="#show" onclick="PopupManager.create(\'%s\', false, false, \'%s\', 700, 400, %s);">%s</a>';
                        
                        $url = $setting_links[$item]['link'];
                        $url .= '&tkey=' . $item;
                        
                        $popup = (!empty($setting_links[$item]['popup'])) ? $setting_links[$item]['popup'] : 1;
                        $title = (!empty($setting_links[$item]['title'])) ? $setting_links[$item]['title'] : $this->msg['setting_msg'];
                        $popup_title = (!empty($setting_links[$item]['title'])) ? sprintf("'%s'", $setting_links[$item]['title']) : 0;
                        
                        if (!empty($setting_links[$item]['settings'])) {
                            $params = array();
                            foreach ($setting_links[$item]['settings'] as $setting_key) {
                                $params['sid'][] = $manager->sm->getSettingIdByKey($setting_key);
                            }
                            
                            $url .= '&' . http_build_query($params);
                        }
                        
                        $v['setting'] = sprintf($str, $url, $popup, $popup_title, $title);
                    }
    
                    if(isset($setting_info[$item])) {
                        $v['setting'] = $setting_info[$item];
                    }
    
                    $v['instruction'] = '';
                    if(isset($instruction_links[$item])) {
                        $delim = ($v['setting']) ? ' | ' : '';
                        $str = '%s<a href="%s" target="_blank">%s</a>';
                        $v['instruction'] = sprintf($str, $delim, $instruction_links[$item], $this->msg['instruction_msg']);
                    }
    
                    $tpl->tplAssign($this->getViewListVarsCustom($item, $val['code']));
                    
                    $tpl->tplParse($v, 'group/row');
                }
                
                $tpl->tplSetNested('group/row');
                $tpl->tplParse($v, 'group');
            }
        }

        if($this->priv->isPriv('update') &&
        	!$this->controller->getMoreParam('popup') &&
        	$this->controller->page != 'common_setting') {
            $tpl->tplSetNeeded('/run_test');
        }

        $tpl->tplAssign($this->msg);                              
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getViewListVarsCustom($id, $code) {
        $row = parent::getViewListVarsRow($id, $code);
        
        if($code == 0) {
            $row['style'] = 'color: #b00000;';
        }
        
        $row['style2'] = '';
        if($code == 1) {
            $row['style2'] = 'color: green;';
        }
        
        if($code == 2) {
            $row['style2'] = 'color: #555555;';
        }
        
        return $row;
    }
    
    
    function ajaxRetest($key) {
        $objResponse = new xajaxResponse();
        
        $report = unserialize($this->data['data_string']);
        
        $rq = array();
        $rp = array();
        $action = new KBPReportAction($rq, $rp);
        
        $method = 'check' . str_replace('_', '', ucwords($key));
        $report[$key] = $action->$method($this->manager);
        
        $data_string = serialize($report);
        $this->manager->updateReport($data_string);
        
        $objResponse->script('location.reload();');
        
        return $objResponse;
    }
    
}
?>