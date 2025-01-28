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

namespace PublicSetting;

use Validator;
use SpellSuggest_bing;



class SettingValidator
{
     
    function validate($values) {
        
        $required = array();

        // recaptcha
        if(isset($values['captcha_type'])) { // to check for setup wizard
            if(in_array($values['captcha_type'], array(2,3,4))) {
                $required[] = 'recaptcha_site_key'; 
                $required[] = 'recaptcha_site_secret';
            }
        }

        $v = new Validator($values, true);

        $v->required('required_msg', $required);
        if($v->getErrors()) {
            return $v->getErrors();
        }
    
        if(!empty($values['page_to_load'])) {
            
            $val = $values['page_to_load'];
            
            // default
            if(strtolower($val) == 'default') {
            
            // template
            } elseif(strtolower($val) == 'html') {

            // file path
            } elseif(strpos($val, '[file]') !== false) {
            
                $val = trim(str_replace('[file]', '', $val));
                if(@!fopen(trim($val), "rb")) {
                    $v->setError('page_not_exists_msg', 'page_to_load');
                }
                
            } else {
                $v->setError('page_wrong_msg', 'page_to_load');
            }
        }
        
        return $v->getErrors();
    }
    
    
    static function validatePspell($setting) {

        $is_ext = extension_loaded('pspell');
        if(!$is_ext) {
            return array('code' => 0, 'code_message' => 'extension is not loaded');
        }

        // this error will be skipped in settings popup
        // but trigered as code = 0 (failed) in setup tests
        @$dictionary = pspell_new($setting['search_spell_pspell_dic']);
        if (!$dictionary) {
            return array('code' => 1, 'code_message' => "could not open the dictionary");
        }

        return true;
    }

    
    static function validateEnchant($setting) {

        $is_ext = extension_loaded('enchant');
        if(!$is_ext) {
            return array('code' => 0, 'code_message' => 'extension is not loaded');
        }

        // this error will be skipped in settings popup
        // but trigered as code = 0 (failed) in setup tests
        $r = enchant_broker_init();
        $dictionaries = enchant_broker_list_dicts($r);
        if (!$dictionaries) {
            return array('code' => 1, 'code_message' => "could not open or find a dictionary");
        }

        return true;
    }


    static function validateBing($url, $key) {

        $url = str_replace('[search_query]', 'test', $url);

        list($body, $code) = SpellSuggest_bing::request($url, $key);
        if($code != 200) {
            return false;
        }

        return true;
    }
    
}
?>