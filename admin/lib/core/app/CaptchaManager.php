<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KnowledgebasePublisher package                   |
// | KnowledgebasePublisher - web based knowledgebase publishing tool          |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class CaptchaManager 
{

    // var $model;
    //
    // function __construct($model) {
    //     $this->model = $model;
    // }
    
    static $section;
    
    // allowed attemps without captcha
    static $attempts_default = 3;
    static $attempts = array(
        'auth' => 2
    );

    
    // interval in seconds
    static $interval_default = 600;
    static $interval = array(
        'auth' => 600
    );
    
    
    static function factory($type) {
        
        $class = 'CaptchaManager_' . $type;
        $file = $class . '.php';
        
        $captcha = new $class;        
        return $captcha;
    }
    
    
    // if captcha enabled for section and we have all required lib
    function useCaptcha($manager, $section, $username = false) {

        $ret = false;
        if(!$manager->getSetting('captcha_type')) { // July 26, 2021
            return $ret;
        }
        
        $captcha_val = $section . '_captcha';
        $captcha_set = $manager->getSetting($captcha_val);

        if($captcha_set == 'yes') {
            $ret = true;
        } elseif($captcha_set == 'yes_no_reg' && !$manager->is_registered) {
            $ret = true;
        }

        if(!$this->isRequredLib()) {
            $ret = false;
        }
        
        if($ret && $section == 'auth') {
            $ret = $this->isUseCaptcha($section, $username);
        }
  
        return $ret;
    }
    
    
    // used to show or not captcha on first load ... 
    function isUseCaptcha($section, $username = false) {
         
        $ret = false; 
        $ses_key = sprintf('kb_captcha_%s_', $section);
        self::$section = $section;
        
        $interval = (isset(self::$interval[$section])) ? self::$interval[$section] : self::$interval_default;
        
        // unset($_SESSION[$ses_key]);

        // if we have session
        if(isset($_SESSION[$ses_key])) {
            $exp_time = $_SESSION[$ses_key]+$interval+60; // one min more than in db
            $cur_time = time();
            $ret = ($cur_time < $exp_time);
        }

        // after form submission, check it against database for login only
        // if hacker and no session we found in db that we need cattch and run validate it
        if(!$ret && $username) {
            $allowed = (isset(self::$attempts[$section])) ? self::$attempts[$section] : self::$attempts_default;
            
            $manager = new CaptchaManagerModel;
            $done = $manager->getLoginAttemtps($username, $allowed, $interval);
            
            $ret = ($done >= $allowed);
            if($done+1 >= $allowed) {
                $_SESSION[$ses_key] = time();
            }
        }

        return $ret;
    }
    
    
    static function resetCaptchaValues() {
        
        if(isset($_SESSION['kb_captcha_'])) {
            unset($_SESSION['kb_captcha_']);
        }

        if(isset($_SESSION['kb_captchaip_'])) {
            unset($_SESSION['kb_captchaip_']);
        }
    }


    function getCaptchaBlock($section, $placeholder) {
    
        $tpl = new tplTemplatez(APP_CLIENT_DIR . 'client/skin/view_default/default/' . $this->captcha_tmpl);
        
        $block = (empty($placeholder)) ? '/default' : '/' . $placeholder;
        $tpl->tplSetNeeded($block);
        
        $tpl->tplAssign('captcha_src', self::getCaptchaSrc());
        $tpl->tplAssign('action', $section);
        
        if(isset($this->keys['site_key'])) {
            $tpl->tplAssign('site_key', $this->keys['site_key']);
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    static function getCaptchaSrc() {
        return APP_CLIENT_PATH . 'captcha.php';
    }

}


class CaptchaManager_builtin extends CaptchaManager  
{
    
    var $captcha_tmpl = 'block_captcha.html';
    
    
    function isRequredLib() {
        return (CaptchaImage::isRequredLib());
    }
    
    
    function isCaptchaValid($manager, $values, $unset = false) {

        $captcha = (isset($values['captcha'])) ? $values['captcha'] : 1;
        if($captcha && !isset($_SESSION['kb_captcha_'])) {
            return false;
        }

        $ret = true;
        $ip = WebUtil::getIP();
        $ip = ($ip == 'UNKNOWN') ? mt_rand(5, 15) : $ip;

        $kb_captcha_ = (isset($_SESSION['kb_captcha_'])) ? $_SESSION['kb_captcha_'] : 2;
        $kb_captchaip_ = (isset($_SESSION['kb_captchaip_'])) ? $_SESSION['kb_captchaip_'] : mt_rand(5, 15);

        $s_captcha = strtolower($kb_captcha_);
        $u_captcha = strtolower($captcha);

        if($s_captcha != $u_captcha || $kb_captchaip_ != $ip) {
            $ret = false;
        }

        if($ret && $unset) {
            self::resetCaptchaValues();
        }
        
        return $ret;
    }
    
}


class CaptchaManagerRecaptcha extends CaptchaManager  
{
    
    var $keys = array();
    
    
    function __construct() {
        $setting = SettingModel::getQuick(2);
        $this->keys['site_key'] = $setting['recaptcha_site_key'];
        $this->keys['secret_key'] = $setting['recaptcha_site_secret'];
    }
    
    
    function isRequredLib() {    
        return true;
    }
    
    
    function isCaptchaValid($manager, $values, $unset = false) {
        
        $ret = false;
    
        // no validate on ajax as we use disable submit btn          
        if(!$unset) { // this is ajax call 
            return true;
        }

        if(!empty($values["g-recaptcha-response"])) {
            
            $url = "https://www.google.com/recaptcha/api/siteverify";
            $query = array(
                "secret" => $this->keys['secret_key'],
                "response" => $values["g-recaptcha-response"],
                "remoteip" => WebUtil::getIP()
            );
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

            $data = json_decode(curl_exec($ch), $assoc=true);
            curl_close($ch);
            
            $ret = $this->isResponceValid($data);
        }
        
        // echo '<pre>', print_r($values,1), '</pre>';
        // echo '<pre>', print_r($data,1), '</pre>';
        // exit;
        
        return $ret;
    }

}




class CaptchaManager_recaptcha2 extends CaptchaManagerRecaptcha  
{
 
    var $captcha_tmpl = 'block_captcha_recaptcha.html';
    
    
    function isResponceValid($data) {
        return (!empty($data['success']));
    }
}


class CaptchaManager_recaptcha3 extends CaptchaManagerRecaptcha  
{
 
    var $captcha_tmpl = 'block_captcha_recaptcha3.html';


    function isResponceValid($responce) {
        return (!empty($responce['success']) && $responce["score"] >= 0.5);
    }
    
}


class CaptchaManagerModel extends BaseModel
{
    
    var $tbl_pref_custom = '';
    var $tables = array('log_login');
    
    
    function getLoginAttemtps($username, $allowed_attempts, $interval_min) {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->log_login} 
        WHERE username = '%s' 
        AND date_login > DATE_SUB(NOW(), INTERVAL %d SECOND)
        AND exitcode != 1";
        $sql = sprintf($sql, addslashes(stripslashes($username)), $interval_min);
        
        $result = $this->db->SelectLimit($sql, $allowed_attempts, 0) or die(db_error($sql));
        return $result->Fields('num');
    }    

}
?>