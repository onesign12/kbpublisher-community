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

class SetupController
{
    var $query = array('view'  => 'step','msg' => 'msg', 'skip' => 'skip');
    
    var $step_map_install = array(
          1 => 'index',
                'setup',
                'license',
                'library',
                'initial',
                'user',
                'db',
                //'licencekey',
                'install',
                'config',
                'success'
            );
                                
    var $step_map_upgrade = array(
          1 => 'index',
                'setup',
                'license',
                'library',
                'initial',
                'db',
                //'licencekey',
                'update', // update email, we can add more actions here later
                'upgrade',
                'config',
                'success'
            );
    
    
    var $map;
    var $home_path;
    var $working_dir;
    var $working_path;
    
    var $mod_rewrite = false;
    var $arg_separator = '&amp;';
    
    
    function __construct() {
        $this->setUrlVars();
        $this->setModRewrite();
        $this->map = $this->getStepMap();
        //echo "<pre>"; print_r($this->getNextStep()); echo "</pre>";
        //echo "<pre>"; print_r($this->getPrevStep()); echo "</pre>";
    }
    
    
    function getStepMap() {
        
        // disable license option for community
        if(file_exists(APP_PLUGIN_DIR . 'community.php')) {
            foreach(['step_map_install', 'step_map_upgrade'] as $v) {
                $key = array_search('license', $this->$v);
                unset($this->$v[$key]);
                $this->$v = array_values($this->$v);
                array_unshift($this->$v,"");
                unset($this->$v[0]);
            }
        }
        
        $map = $this->step_map_install;
        if(isset($_SESSION['setup_']['setup_type'])) {
            $map = ($_SESSION['setup_']['setup_type'] == 'install') ? $this->step_map_install : $this->step_map_upgrade;
        }
        
        return $map;
    }
    
    
    function getStepKey($view_id, $return) {
        return (isset($this->map[$view_id])) ? $this->map[$view_id] : $return;
    }
    
    
    function getNextStep() {
        $step = $this->view_id + 1;
        return (isset($this->map[$step])) ? $step : false;
    }
    
    
    function getPrevStep() {
        $step = $this->view_id - 1;
        return (isset($this->map[$step])) ? $step : false;
    }
        
        
    function getCurrentStep() {
        $step = $this->view_id;
        return (isset($this->map[$step])) ? $step : false;
    }
        
    
    function setUrlVars() {
        $view_id = $this->getRequestVar('view');
        $this->view_id = ($view_id) ? $view_id : 1;
        $this->msg_id = $this->getRequestVar('msg');
    }
    
    
    function setModRewrite($var = true) {
        if($var === false) {
            $this->mod_rewrite = false;
        } else {
            $this->mod_rewrite = (!empty($_SERVER['KB_REWRITE'])) ? 1 : false;
        }
    }
    
    
    function getRequestVar($var) {
        // return @$_GET[$this->query[$var]];
        return (isset($_GET[$this->query[$var]])) ? $_GET[$this->query[$var]] : null;
    }
    
    
    function getRequestKey($key) {
        return @$this->query[$key];
    }
    
    
    function go($view = false, $msg_key = false) {
        
        $this->arg_separator = '&';
        ini_set('arg_separator.output', '&');    
        
        $url = $this->getLink($view, $msg_key);
        header("Location: " . $url);
        exit();
    }    
    
    
    function getLink($view = false, $category_id = false, $entry_id = false, $msg_key = false) {
        if($view == 'all') {
            return $this->_getLink($this->view_id,
                                   $this->msg_id);     
        } else {
            return $this->_getLink($view, $msg_key);
        }
    }
    
    
    private function _getLink($view = 'index', $msg_key = false) {
        
        $link = array();
        //$view = ($view == '1') ? false : $view;
        
        if(!$this->mod_rewrite) {
        
            if($view)        { $link[1] = sprintf('%s=%s', $this->getRequestKey('view'), $view); }
            if($msg_key)     { $link[4] = sprintf('%s=%s', $this->getRequestKey('msg'), $msg_key); }
            
            $link = (!$link) ? $this->home_path . 'index.php'
                             : $this->home_path . 'index.php?' . implode($this->arg_separator, $link);    
        
        } else {
        
            if($view)        { $link[1] = sprintf('%s', $view); }
            if($msg_key)     { $link[4] = sprintf('%s', $msg_key); }
            
            $link = (@!$link) ? $this->home_path : $this->home_path . implode('/', $link);
        }
        
        return $link;
    }
    
    
    function getView($view_id = false) {
        
        if(!$view_id){
            $view_id = $this->view_id;
        }
        
        $view_id = (is_numeric($view_id)) ? $this->getStepKey($view_id, 'index') : $view_id;
        
        $class = 'SetupView_'. $view_id;
        // $file = $this->working_dir . 'inc/view/' . $class . '.php';
        
        // require_once $file;
        return new $class;
     }
    
    
    //could be called in some action to replace current action
    function getAction($view_id = false) {
        
        if(!$view_id){
            $view_id = $this->view_id;
        }
        
        $view_id = (is_numeric($view_id)) ? $this->getStepKey($view_id, 'index') : $view_id;
        
        $class = 'SetupAction_'. $view_id;
        // $file  = $this->working_dir . 'inc/action/' . $class . '.php';
        
        // require_once $file;
        return new $class;
     }
}
?>