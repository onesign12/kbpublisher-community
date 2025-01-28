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

namespace AdminSetting;

use SettingParserModelCommon;
use AppController;
use SphinxIndexModel;



class SettingParserModel extends SettingParserModelCommon
{

	function callOnSave($values, $old_values) {
	    
        if (isset($values['lang'])) {
            $new_lang = $values['lang'];
        
            // lang changed 
            if($new_lang != $old_values['lang']) {
                
                $sphinx = $this->getSettings(141);
                
                if($sphinx['sphinx_enabled']) { // sphinx on
                    
                    $sphinx_lang = (is_array($sphinx['sphinx_lang'])) ? $sphinx['sphinx_lang'] : array($sphinx['sphinx_lang']);
                    // echo '<pre>$sphinx_lang ', print_r($sphinx_lang,1), '<pre>';
                    
                    if(!in_array($new_lang, $sphinx_lang)) { // no new lang in sphinx langs
                        
                        $sphinx_lang_validate = $sphinx_lang;
                        if(($en = array_search('en', $sphinx_lang_validate)) !== false) {
                            unset($sphinx_lang_validate[$en]); // unset en sphinx
                        }
                        
                         // many langs in sphinx or have tasks, redirect to setting to validate and change
                        if(count($sphinx_lang_validate) > 1 || $sphinx['sphinx_enabled'] == 2) {
                            
                            $controller = new AppController();
                            $controller->setWorkingDir();
                            $more = array('show_msg' => 'sphinx_lang_note');
                            $controller->goPage('setting', 'plugin_setting', 'sphinx_setting', false, $more);
                            
                         // one "main" lang in sphinx we can change lang  
                        } else {
                            unset($sphinx_lang[key($sphinx_lang_validate)]); // unset one main lang
                            $langs = array_unique(array_merge($sphinx_lang, array($new_lang)));
                            // echo '<pre>', print_r($langs,1), '<pre>';
                            
                            $setting_id = $this->getSettingIdByKey('sphinx_lang');
                            $this->setSettings(array($setting_id => implode(',', $langs))); // sphinx lang
                            
                            // set tasks                        
                            $sphinx_model = new SphinxIndexModel();
                            $sphinx_model->setSphinxRestartTasks($sphinx['sphinx_data_path']);
                        
                            $setting_id = $this->getSettingIdByKey('sphinx_enabled');
                            $this->setSettings(array($setting_id => 2)); // sphinx busy, has tasks
                        }
                    }   
                }
            }
        }
        
	}

}
?>