<?php

use AdminSetting\SettingValidator;



class KBPReportAction extends AppAction
{

    function runTest($manager) {
        $a = array();
        
        $items = array();
        foreach($manager->items as $k => $v) {
            foreach($v as $k2 => $v2) {
                $items[] = $v2;
            }
        }
        
        foreach ($items as $key) {
            $method = 'check' . str_replace('_', '', ucwords($key));
            $a[$key] = $this->$method($manager);
        }
        
        $data_string = serialize($a);
        $manager->saveReport($data_string);
    }


    function checkFileDir($manager) {
        $dir = $manager->setting['file_dir'];
        return $this->checkDir($dir);
    }
    
    
    function checkCacheDir($manager) {
        $dir = APP_CACHE_DIR;
        return $this->checkDir($dir);
    }
    
    
    function checkHtmlEditorUploadDir($manager) {
        $dir = $manager->setting['html_editor_upload_dir'];
        return $this->checkDir($dir);
    }

    
    function checkDir($dir) {
        $writeable = is_writable($dir);

        $code = ($writeable) ? 1 : 0;
        $msg = ($writeable) ? '' : sprintf('Directory %s is not writeable or does not exist', $dir);
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkXpdf($manager) {
        $path = $manager->setting['file_extract_pdf'];
        
        $msg = '';
        $code = 1;
        
        if(strtolower($path != 'off')) {
            $ret = SettingValidator::validateXPDF($path);
            
            if(is_array($ret)) {
                $code = 0;
                $msg = 'XPDF - ' . $ret['code_message']; 
            }

        } else {
            $code = 2;
        }
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkCatdoc($manager) {
        $path = $manager->setting['file_extract_doc']; 
        
        $msg = '';
        $code = 1;
        
        if(strtolower($path != 'off')) {
            $ret = SettingValidator::validateDoc($path, 'doc');  
            
            if(is_array($ret)) {
                $code = 0;
                $msg = 'Catdoc - ' . $ret['code_message']; 
            }

        } else {
            $code = 2;
        }
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkAntiword($manager) {
        $path = $manager->setting['file_extract_doc2']; 
        
        $msg = '';
        $code = 1;
        
        if(strtolower($path != 'off')) {
            $ret = SettingValidator::validateDoc($path, 'doc2');  
            
            if(is_array($ret)) {
                $code = 0;
                $msg = 'Antiword - ' . $ret['code_message']; 
            }

        } else {
            $code = 2;
        }
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkZip($manager) {
        $is_ext = extension_loaded('zip');
        
        $code = ($is_ext) ? 1 : 2;
        $msg = ($is_ext) ? '' : 'Zip extension is not loaded';
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkSpell($manager) {
        
        $setting = $manager->setting['search_spell_suggest'];
        
        $msg = '';
        $code = 1;
        
        if($setting) {
            
            switch ($setting) {
                case 'enchant':
                    $ret = PublicSetting\SettingValidator::validateEnchant($manager->setting);
                    if(is_array($ret)) {
                        $code = 0;
                        $msg = 'Enchant - ' . $ret['code_message']; 
                    }
                    break;
                
                case 'pspell':
                    $ret = PublicSetting\SettingValidator::validatePspell($manager->setting);
                    if(is_array($ret)) {
                        $code = 0;
                        $msg = 'Pspell - ' . $ret['code_message']; 
                    }
                    break;
                    
                case 'bing':
                    $url = $manager->setting['search_spell_bing_spell_check_url'];
                    $key = $manager->setting['search_spell_bing_spell_check_key'];
                    $ret = PublicSetting\SettingValidator::validateBing($url, $key);
                    
                    if (!$ret) {
                        $code = 0;
                        $msg = "Bing - Connection could not be established";
                    } 
                    break;
            }
            

        } else {
            $code = 2;
        }
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkHtmldoc($manager) {
        $path = $manager->setting['plugin_htmldoc_path'];  
        
        $msg = '';
        $code = 1;
        
        if(strtolower($path != 'off')) {

            $export = KBExport::factory('pdf');
            $ret = $export->validate($path, 'license_key'); // second arg to check only key 

            if(is_array($ret)) {
                $code = 0;
                $msg = 'Htmldoc - ' . $ret['code_message']; 
            }

        } else {
            $code = 2;
        }
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkWkHtmlToPdf($manager) {
        $path = $manager->setting['plugin_wkhtmltopdf_path'];  
        
        $msg = '';
        $code = 1;
        
        if(strtolower($path != 'off')) {
            
            $export = new KBExport2_pdf;
            $ret = $export->validate($path, 'license_key');  // second arg to check only key 
            
            if(is_array($ret)) {
                $code = 0;
                $msg = 'WKHTMLTOPDF - ' . $ret['code_message']; 
            }

        } else {
            $code = 2;
        }
        
        return array('code' => $code, 'msg' => $msg);
    }
    
    
    function checkSphinx($manager) {
        $setting = $manager->setting['sphinx_enabled'];
        
        $msg = '';
        $code = 1;
        
        if($setting == 1) {
            $ret = SphinxSetting\SettingValidator::validateConnection($manager->setting);
            if($ret !== true) {
                $code = 0;
                $msg = $ret['body']; 
            }

        } else {
            $code = 2;
        }
        
        return array('code' => $code, 'msg' => $msg);
    }
    

    function getMagicValues($timestamp, $run_magic = array()) {
        
        $diff_ts = time() - $timestamp;
        $diff_minutes = $diff_ts/60;
        $magic_minutes = array(
            'freq'       => 5+11,             // 16 minutes - 3 executions
            'hourly'     => 60+70,            // 2 hour and 10 minutes - 2 executions
            'daily'      => 24*60+120,        // 1 day and 2 hour  
            'weekly'     => 7*24*60+24*60,    // 1 week and 1 day
            'monthly'    => 31*24*60+48*60    // 1 months (31 days) and 2 days
        );
        
        $cron = new CronModel();
        unset($cron->magic_to_number['_test_']);
        unset($cron->magic_to_number['_run_']);
        
        $magic = array();
        foreach($cron->magic_to_number as $k => $v) {
            if($run_magic && !in_array($k, $run_magic)) {
                continue;
            }
            
            if($diff_minutes <= $magic_minutes[$k]) {
                continue;
            }
            
            $magic[$k]['num'] = $v;
            $magic[$k]['word'] = $k;
            $magic[$k]['minutes'] = $magic_minutes[$k];
        }
        
        // echo '<pre>First Cron: ', print_r(date('Y-m-d', $timestamp), 1), '</pre>';
        // echo '<pre>Now: ', print_r(date('Y-m-d'), 1), '</pre>';
        // echo '<pre>diff_minutes: ', print_r($diff_minutes, 1), '</pre>';
        // echo '<pre>', print_r($magic_minutes, 1), '</pre>';
        // echo '<pre>', print_r($magic, 1), '</pre>';
        // exit;
                
        return $magic;
    }


    function checkCronSet($manager, $timestamp, $run_magic = array()) {

        $str = 'scheduled tasks are not configured properly or latest tasks execution skipped by some reason';
        $str2 = 'DB error determing if scheduled tasks configuired:';
        
        $code = $msg = $log = array();
        $magic = $this->getMagicValues($timestamp, $run_magic);
                        
        foreach($magic as $v) {
            $result = $manager->isCronExecuted($v['minutes'], $v['num']);
            
            if($result === false) {
                $str = $m2 . $this->db->ErrorMsg();
                $code[$v['num']] = 0;
                $msg[$v['num']] = $str;
            } else {
                $code[$v['num']] = 1;
                if(!$result) {
                    $code[$v['num']] = 0;
                    $msg[$v['num']] = $v['word'];
                }
            }            
        }
        
        $ret = array();
        $ret['code'] = (in_array(0, $code)) ? 0 : 1;
        $ret['msg'] = (in_array(0, $code)) ? sprintf('%s %s', implode(', ', $msg), $str) : '';
        
        return $ret;
    }


    function checkCron($manager) {
        $ret = array();
        
        $ret['code'] = 3;
        $ret['msg'] = 'No one schedule task log found, after setting scheduled task wait at least 5 min for first execution';
        
        $ts = $manager->getFirstCronExecution();
        if($ts) {
            $ret = $this->checkCronSet($manager, $ts);
        }
        
        return $ret;
    }
    
    
    function checkCurl($manager) {
        $is_ext = extension_loaded('curl');
        
        $code = ($is_ext) ? 1 : 0;
        $msg = ($is_ext) ? '' : 'cURL extension is not loaded';
        
        return array('code' => $code, 'msg' => $msg);
    }

}

?>