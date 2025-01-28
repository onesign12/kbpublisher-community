<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005-2023 Evgeny Leontev                                    |
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


class AppLoader
{
    
    var $path;
    var $map_dir;
    var $map_file = 'classmap_%s.php';
    var $map;    
    
    
    function __construct($options = array()) {
        $this->path = (@$options['path']) ?: dirname(dirname(dirname(__DIR__))) . '/';
        $this->map_dir = (@$options['map_dir']) ?: __DIR__ . '/';
    }
    
    
    static function register(array $types) {
        foreach($types as $type) {
            $loader = new AppLoader();
            $loader->setMap($type);
            spl_autoload_register(array($loader, 'setLoader'), true, false);
        }
    }
    
    
    function setMap($type) {
        $this->map = require $this->map_dir . sprintf($this->map_file, $type);
    }
    
    
    function setLoader($class) {
        if($path = self::findFile($class)) {
            $this->loadFile($this->path . $path);
        }
    }
    
    
    function findFile($class) {
        if (isset($this->map[$class])) {
            return $this->map[$class];
        }
        
        return null;
    }
    
    
    function loadFile($file) {
        require_once $file;
    }

    
    function generateMap($type, $configs) {
        
        $d = new MyDir;
        $d->one_level = false;
        $d->full_path = true;
        $d->setAllowedExtension('php');
        $d->setSkipDirs(@$configs['skip']['dir']);
        $d->setSkipRegex(@$configs['skip']['regex']);
        $d->setSkipRegex('/^[a-z_]+\.php$/'); //lower case files
    
        $files = [];
        foreach($configs['dirs'] as $dir) {
            $files[] = $d->getFilesDirs($dir);
        }
        $files = ExtFunc::multiArrayToOne($files);
        // echo '<pre>' . print_r($files, 1) . '</pre>';
        // exit;
        
        $map = [];

        foreach($files as $fpath) {
            $path = pathinfo($fpath);
            $content = file_get_contents($fpath);
            preg_match("/^namespace\s+([\w\\\]+);/m", $content, $match);
            $namespace = (isset($match[1])) ? str_replace("\\", "\\\\", $match[1]) . '\\\\' : '';
            
            $file = str_replace($this->path, '', $fpath);
            $class = $namespace . $path['filename'];

            if(isset($map[$class])) {
                echo "Filename exists: " . $class . " - " .  $fpath, "\n";
            }
            
            $map[$class] = sprintf("'%s' => '%s'", $class, $file);
        }

        // echo '<pre>' . print_r($b, 1) . '</pre>';
        // echo '<pre>' . print_r($b, 1) . '</pre>';
        $str = "<?php\nreturn [%s];\n?>";
        $content = sprintf($str, "\n\t" . implode(",\n\t", $map) . "\n\t");

        $file = $this->map_dir . sprintf($this->map_file, $type);
        file_put_contents($file, $content);
    }    
}

?>