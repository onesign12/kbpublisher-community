<?php

class SphinxTaskHelper
{
    
    public $settings;
    public $indexes;
    protected $logs = array();
    
    protected $custom_pre_query = 'SET NAMES utf8';
    
    protected $stemmer_strings = array(
        'en' => 'stem_en',
        'ru' => 'stem_ru',
        'cz' => 'stem_cz',
        'ar' => 'stem_ar',
        'da' => 'libstemmer_da',
        'nl' => 'libstemmer_nl',
        'fi' => 'libstemmer_fi',
        'fr' => 'libstemmer_fr',
        'de' => 'libstemmer_de',
        'hu' => 'libstemmer_hu',
        'it' => 'libstemmer_it',
        'no' => 'libstemmer_no',
        'pt_BR' => 'libstemmer_pt',
        'ro' => 'libstemmer_ro',
        'es' => 'libstemmer_es',
        'sv' => 'libstemmer_sv',
        'tr' => 'libstemmer_tr',
        'pl' => 'none'
    );
    
    
    function __construct($settings) {
        $this->settings = $settings;
    }
    
    
    static function factory($settings) {
        $class = (substr(PHP_OS, 0, 3) == 'WIN') ? 'win' : 'unix';
        $class = 'SphinxTaskHelper_' . ucfirst($class);
        $obj = new $class($settings);
        return $obj;
    }
    
    
    function getLogs() {
        $logs = implode("\n", $this->logs);
        $this->logs = array();
        
        return $logs;
    }
    
    
    function generateStructure() {
        $dir = $this->settings['sphinx_data_path'];
        
        try {
            $this->createDirs($dir);
            
            $this->preserveOldConfig($dir);
            
            $lang_options = $this->parseLangOptions($dir);
            $config_data = $this->getConfigData($lang_options);
            
            $this->writeConfig($dir, $config_data);
            $this->writeOriginalConfig($dir, $config_data);
            
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return true;
    }
    
    
    protected function createDirs($dir) {
        $dirs = array($dir);
        $subdirs = array('log', 'index');
        
        foreach ($subdirs as $subdir) {
            $dirs[] = $dir . $subdir;
        }
        
        foreach ($dirs as $dir) {
            if(!is_dir($dir)) {
                $oldumask = umask(0);
                $r = mkdir($dir, 0755, true);
                umask($oldumask);
                
                if ($r) {
                    $this->logs[] = 'Successfully created directory ' . $dir;
                    
                } else {
                    $msg = sprintf('Cannot create directory: %s', $dir);
                    throw new Exception($msg);
                }
                
            } else {
                $this->logs[] = 'Directory already exists: ' . $dir;
            }
            
            if(!is_dir($dir) || !is_writeable($dir)) {
                $msg = sprintf('Directory (%s) does not exist or it is not writable', $dir);
                throw new Exception($msg);
            }
        }
    }
    
    
    protected function preserveOldConfig($dir) {
        $config_path = $dir . 'sphinx.conf';
        $original_config_path = $dir . 'sphinx_original.conf';
        if (file_exists($config_path) && (md5_file($original_config_path) != md5_file($config_path))) {
            $renamed_config_path = sprintf('%ssphinx_%s.conf', $dir, date('Y-m-d_H-i-s'));
            $ret = rename($config_path, $renamed_config_path);
            
            if(!$ret) {
                $msg = 'Cannot rename file: ' . $config_path;
                throw new Exception($msg);
            }
        }
    }
    
    
    protected function writeStopwordsFile($path, $data) {
        $ret = FileUtil::write($path, $data);
        if($ret) {
            $this->logs[] = 'Successfully created stopwords file ' . $path;
            
        } else {
            $msg = 'Cannot write file: ' . $path;
            throw new Exception($msg);
        }
    }
    
    
    protected function writeConfig($dir, $data) {
        $config_path = $dir . 'sphinx.conf';
        $ret = FileUtil::write($config_path, $data);
        
        if($ret) {
             $this->logs[] = 'Successfully created configuration file ' . $config_path;
             
        } else {
            $msg = 'Cannot write file: ' . $config_path;
            throw new Exception($msg);
        }
    }
    
    
    protected function writeOriginalConfig($dir, $data) {
        $original_config_path = $dir . 'sphinx_original.conf';
        $ret = FileUtil::write($original_config_path, $data);
        
        if(!$ret) {
            $msg = 'Cannot write file: ' . $original_config_path;
            throw new Exception($msg);
        }

    }
    
    
    protected function parseLangOptions($dir) {
        $lang_options = array(
            'charset_tables' => array(),
            'custom_options' => array(),
            'stopwords' => array()
        );
        
        if (!is_array($this->settings['sphinx_lang'])) {
            $this->settings['sphinx_lang'] = array($this->settings['sphinx_lang']);
        }
        
        $default_lang_key = array_search($this->settings['lang'], $this->settings['sphinx_lang']);
        if ($default_lang_key !== false) { // site language goes 1st
            $temp = array(0 => $this->settings['lang']);
            unset($this->settings['sphinx_lang'][$default_lang_key]);
            $this->settings['sphinx_lang'] = array_merge($temp, $this->settings['sphinx_lang']);
        }
        
        foreach ($this->settings['sphinx_lang'] as $lang) {
            $lang_conf = sprintf('%s%s/sphinx.conf', APP_MSG_DIR, $lang);
            if (file_exists($lang_conf)) {
                //$_lang_options = FileUtil::read($lang_conf);
                $_lang_options = AppMsg::parseMsgsMultiIni($lang_conf);
                
                $charset_table_lines = preg_split('/$\R?^/m', trim($_lang_options['charset_table']));
                $charset_table_lines = array_map('trim', $charset_table_lines);
                $lang_options['charset_tables'] = array_merge($lang_options['charset_tables'], $charset_table_lines);
                
                if (!empty($_lang_options['custom_options'])) {
                    $lang_options['custom_options'][] = $_lang_options['custom_options'];
                }
            }
            
            $lang_stopwords_path = sprintf('%sstopwords_%s.txt', $dir, $lang);
            $lang_stopwords_data = (!empty($_lang_options['stopwords'])) ? trim($_lang_options['stopwords']) : '';
            
            $lang_options['stopwords'][] = $lang_stopwords_path;
            
            $this->writeStopwordsFile($lang_stopwords_path, $lang_stopwords_data);
        }
        
        return $lang_options;
    }
    
    
    protected function getConfigData($lang_options, $conf = false) {
		
        $file = (AppPlugin::isPlugin('fields')) ? 'sphinx.conf' : 'sphinx_no_custom.conf';
        
        $tpl = new tplTemplatez(APP_MODULE_DIR . 'setting/setting/template/' . $file);
        $tpl->strip_vars = true;
		
        $tpl->tplAssign('custom_pre_query', $this->custom_pre_query);
		
		// sphinx type
		$prefix = ($p = SphinxModel::getSphinxPrefix()) ? $p : '';
		$tpl->tplAssign('idx_pref', $prefix);
		if(!SphinxModel::isSphinxSingleInstance()) {
			$tpl->tplSetNeeded('/system');
		} 
        
        if (empty($conf)) {
            $reg =& Registry::instance();
            $conf =& $reg->getEntry('conf');
        }
        
        $tpl->tplAssign($conf);
        
        // password
        if (strpos($conf['db_pass'], '#') !== false) {
            $escaped_pass = str_replace('#', '\#', $conf['db_pass']);
            $tpl->tplAssign('db_pass', $escaped_pass);
        }
        
        // port
        $params = parse_url($conf['db_host']);
        $port = (!empty($params['port'])) ? $params['port'] : '3306';
        $tpl->tplAssign('mysql_port', $port);
        
        $dir = $this->settings['sphinx_data_path'];
        $tpl->tplAssign('dir', $dir);
        $tpl->tplAssign('index_dir', $dir . 'index');
        $tpl->tplAssign('log_dir', $dir . 'log');
        
        // lang
        
        // morphology
        foreach ($this->settings['sphinx_lang'] as $lang) {
            if (!empty($this->stemmer_strings[$lang])) {
                $stemmers[] = $this->stemmer_strings[$lang];
            }
        }
        
        if (!empty($lang_options['charset_tables'])) {
            $lang_options['charset_tables'] = array_unique($lang_options['charset_tables']);
            $lang_data = 'charset_table = ' . implode(", \\\n ", $lang_options['charset_tables']);
            
            if (!empty($lang_options['custom_options'])) {
                $lang_data .= "\n" . implode("\n", $lang_options['custom_options']);
            }
            
            $tpl->tplAssign('lang_options', $lang_data);
            
        } else {
            $tpl->tplAssign('lang_options', '');
        }
        
        // plugins
        $pluginable = array_keys(AppPlugin::getPluginsFiltered('sphinx'));
        foreach($pluginable as $plugin) {
            if(AppPlugin::isPlugin($plugin)) {
                $tpl->tplSetNeededGlobal($plugin);
            }
        }
        
        // kill lists
        if ($this->settings['sphinx_version'] >= 3) {
            $tpl->tplSetNeededGlobal('ver_30');
        
         } else {
            $tpl->tplSetNeededGlobal('ver_22');
        }
        
        $tpl->tplAssign('stemmer', (empty($stemmers)) ? 'none' : implode(' ', $stemmers));
        $tpl->tplAssign('stopwords_files', implode(' ', $lang_options['stopwords']));
        $tpl->tplAssign('sphinx_port', $this->settings['sphinx_port']);
            
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    protected function getRealTimeConfigData($lang_options, $conf = false) {
        
        $tpl = new tplTemplatez(APP_MODULE_DIR . 'setting/setting/template/sphinx_rt.conf');
        
        // sphinx type
        if(SphinxModel::isSphinxSingleInstance()) {
            $tpl->tplAssign('idx_pref', SphinxModel::getSphinxPrefix());
        } else {
            $tpl->tplSetNeeded('/system');
        }
        
        if (empty($conf)) {
            $reg =& Registry::instance();
            $conf =& $reg->getEntry('conf');
        }
        
        $tpl->tplAssign($conf);
        
        $dir = $this->settings['sphinx_data_path'];
        $tpl->tplAssign('dir', $dir);
        $tpl->tplAssign('index_dir', $dir . 'index');
        $tpl->tplAssign('log_dir', $dir . 'log');
        
        // lang
        
        // morphology
        foreach ($this->settings['sphinx_lang'] as $lang) {
            if (!empty($this->stemmer_strings[$lang])) {
                $stemmers[] = $this->stemmer_strings[$lang];
            }
        }
        
        if (!empty($lang_options['charset_tables'])) {
            $lang_options['charset_tables'] = array_unique($lang_options['charset_tables']);
            $lang_data = 'charset_table = ' . implode(", \\\n ", $lang_options['charset_tables']);
            
            if (!empty($lang_options['custom_options'])) {
                $lang_data .= "\n" . implode("\n", $lang_options['custom_options']);
            }
            
            $tpl->tplAssign('lang_options', $lang_data);
            
        } else {
            $tpl->tplAssign('lang_options', '');
        }
        
        $tpl->tplAssign('stemmer', (empty($stemmers)) ? 'none' : implode(' ', $stemmers));
        $tpl->tplAssign('stopwords_files', implode(' ', $lang_options['stopwords']));
        $tpl->tplAssign('sphinx_port', $this->settings['sphinx_port']);
            
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
	
	function getMainConfigPath() {
        $config_path = $this->settings['sphinx_data_path'] . 'sphinx.conf';
		if(SphinxModel::isSphinxSingleInstance()) {
        	$config_path = $this->settings['sphinx_main_config'];
        }
		
		return $config_path;
	}
	
    
    function index($index_type, $entry_type) {
        $this->indexes = $this->getIndexList($index_type, $entry_type);
		
        $cmd_str = '%sindexer --rotate --config "%s" %s';
		$config_path = $this->getMainConfigPath();
        $cmd = sprintf($cmd_str, $this->settings['sphinx_bin_path'], $config_path, implode(' ', $this->indexes));
        
        list($stdout, $stderr, $return) = $this->execute($cmd);
        
        if ($return == 0) {
            $this->logs[] = $stdout;
            
        } else {
            return $stderr . $stdout;
        }
        
        return true;
    }
    
    
    protected function getIndexList($index_type, $entry_type) {
        $entry_types = array(
            'Article', 'File', 'News', 'Feedback',
            'User', 'ArticleDraft', 'FileDraft',
            'Tag', 'Glossary', 'Comment', 'RatingFeedback', 'Attachment'
        );
        
        // plugins
        $pluginable = AppPlugin::getPluginsFiltered('sphinx');
        foreach($pluginable as $plugin => $v) {
            if(!AppPlugin::isPlugin($plugin)) {
                $entry_types = array_diff($entry_types, $v['sphinx']);
            }
        }
            
        if ($entry_type) {
            $index_type = 'main';
            $index = ucwords($entry_type);
            $index_names = array($index);
            
        } else { // all
            $index_names = $entry_types;
        }
        
		$prefix = SphinxModel::getSphinxPrefix();
		
        $indexes = array();
        foreach ($index_names as $index_name) {
            $indexes[] = sprintf('%skbp%sIndex_%s', $prefix, $index_name, $index_type);
        }
		
        return $indexes;
    }
    
    
    protected function execute($cmd) {
        $cmd = escapeshellcmd($cmd);
        $process = proc_open($cmd, array(
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        ), $pipes);
        
        if (is_resource($process)) {
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            
            $return = proc_close($process);
            return array($stdout, $stderr, $return);
            
        } else {
            return false;
        }
    }
	
	
	function emptyConfig() {
        $dir = $this->settings['sphinx_data_path'];
        
        try {
            
            $lang_options = $this->parseLangOptions($dir);
            $config_data = $this->getConfigData($lang_options);
            
            $this->writeConfig($dir, '');
            $this->writeOriginalConfig($dir, $config_data);
            
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return true;
	}
	
	
	function cloudRestart() {
		$ret = $this->stop();
		$ret = $this->start();
	}
    
}


class SphinxTaskHelper_win extends SphinxTaskHelper
{
    
    private $service_name = 'kbpSphinxSearch';
    
    
    function start() {
        try {
            $this->installService();
            $this->startService();
            
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return true;
    }
    
    
    function stop() {
        try {
            $this->stopService();
            $this->deleteService();
            
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return true;
    }
    
    
    private function installService() {
        $cmd_str = '"%ssearchd" --install --config "%s" --servicename %s';
        $config_path = $this->getMainConfigPath();
        $cmd = sprintf($cmd_str, $this->settings['sphinx_bin_path'], $config_path, $this->service_name);
        
        list($stdout, $stderr, $return) = $this->execute($cmd);
        
        if ($return == 0) { // installed
            $this->logs[] = $stdout;
            
        } else {
            $msg = 'Cannot install windows service: ' . $stderr . $stdout;
            throw new Exception($msg);
        }
    }
    
    
    private function startService() {
        $cmd = 'net start ' . $this->service_name;
        
        list($stdout, $stderr, $return) = $this->execute($cmd);
        
        if ($return == 0) { // all good
            $this->logs[] = $stdout;
            
        } else {
            $msg = 'Cannot start windows service: ' . $stderr . $stdout;
            throw new Exception($msg);
        }
    }
    
    
    private function stopService() {
        $cmd = 'net stop ' . $this->service_name;
        
        list($stdout, $stderr, $return) = $this->execute($cmd);
        
        if ($return == 0) { // was running
            $this->logs[] = $stdout;
        }
    }
    
    
    private function deleteService() {
        $cmd_str = '"%ssearchd" --delete --servicename %s';
        $cmd = sprintf($cmd_str, $this->settings['sphinx_bin_path'], $this->service_name);
        
        list($stdout, $stderr, $return) = $this->execute($cmd);
        
        if ($return == 0) { // deleted
            $this->logs[] = $stdout;
        }
    }
    
}


class SphinxTaskHelper_unix extends SphinxTaskHelper
{
    
    function start() {
        try {
            $this->startDaemon();
            
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return true;
    }
    
    
    function stop() {
        try {
            $this->stopDaemon();
            
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return true;
    }
    
    
    private function startDaemon() {
        $cmd_str = '%ssearchd --config "%s"';
        $config_path = $this->getMainConfigPath();
        $cmd = sprintf($cmd_str, $this->settings['sphinx_bin_path'], $config_path);
        
        list($stdout, $stderr, $return) = $this->execute($cmd);
        
        if ($return == 0) { // all good
            $this->logs[] = $stdout;
            
        } else {
            $msg = 'Cannot start Sphinx daemon: ' . $stderr . $stdout;
            throw new Exception($msg);
        }
    }
    
    
    private function stopDaemon() {
		$stop = false;
		$config_path = $this->getMainConfigPath();
		
		if(SphinxModel::isSphinxSingleInstance()) {
			$stop = true;
		
		} elseif(!empty($this->settings['old_dir'])) {
		
        	$old_config_path = $this->settings['old_dir'] . 'sphinx.conf';
			if(file_exists($old_config_path)) {
				$stop = true;
			}
		}
		
		if($stop) {
	        $cmd_str = '%ssearchd --stopwait --config "%s"';
	        $cmd = sprintf($cmd_str, $this->settings['sphinx_bin_path'], $old_config_path);

	        list($stdout, $stderr, $return) = $this->execute($cmd);

	        if ($return == 0) { // was running
	            $this->logs[] = $stdout;
	        }
		}
	}
                         
}

?>