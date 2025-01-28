<?php

class LogUtil
{    
    var $_levels;

    /**
     * Initialize all levels.
     * Level specifications:
     *     array('type' => 'file', 'filename' => <logfile>);
     *     array('type' => 'buffer');
     *     TODO array('type' => 'mail', // CAUTION! Immediately sends email! Use MailPool instead.
     *        'to' => <To>,
     *        'from' => <From>,
     *        'template' => <TemplateBody>,
     *        'subj' => <Subject>); // subj is optional
     *     array('type' => 'mysql'); // TODO Not implemented
     *     array('type' => 'echo'); // Echo to standard output
     *     array('type' => 'none'); // Do nothing (>/dev/null)
     *
     * @param array $levels List of level specifications.
     */
    function __construct($levels) {
        
        foreach ($levels as $id => $dst) {
        
            switch ($dst['type']) {
            case 'file':
                if (!isset($dst['filename'])) {
                    trigger_error('LogUtil: filename is not set.');
                    break;
                }
            
                $f = fopen($dst['filename'], 'a');
                if (!$f) {
                    trigger_error("LogUtil: can't open file '{$dst['filename']}' for writing.");
                    break;
                }
                
                fclose($f);
                $this->_levels[$id]['type'] = 'file';
                $this->_levels[$id]['filename'] = $dst['filename'];
                break;
        
            case 'buffer':
                $this->_levels[$id]['type'] = 'buffer';
                $this->_levels[$id]['_output'] = '';
                break;
        
            case 'mail':
                // TODO
                trigger_error('LogUtil: email logging is not implemented.');
                break;

                if (!isset($dst['to'])) {
                    trigger_error('LogUtil: destination email (to) is not set.');
                    break;
                }
            
                if (!isset($dst['from'])) {
                    trigger_error('LogUtil: source email (from) is not set.');
                    break;
                }
            
                if (!isset($dst['template'])) {
                    trigger_error('LogUtil: template for email is not set.');
                    break;
                }
                
                $this->_levels[$id]['type'] = 'mail';
                $this->_levels[$id]['to'] = $dst['to'];
                $this->_levels[$id]['from'] = $dst['from'];
                $this->_levels[$id]['subj'] = $dst['subj'];
                $this->_levels[$id]['template'] = $dst['template'];
                break;
        
            case 'mysql':
                // TODO
                trigger_error('LogUtil: mysql logging is not implemented.');
                break;
        
            case 'echo':
                $this->_levels[$id]['type'] = 'echo';
                break;
        
            case 'none': // do nothing
                $this->_levels[$id]['type'] = 'none';
                break;
        
            default:
                // TODO
                trigger_error('LogUtil: type of logging is incorrect.');
                break;
            }
        }
    }


    /**
     * @param string|array $level Name of level. Can be array of levels - will be used sequentialy.
     * @param string $msg Text of message.
     * @param string $session Possible session identifier (printed within each log record).
     */
    function put($level, $msg, $session = NULL) {
        if (is_array($level)) {
            foreach ($level as $lvl) {
                $this->put($lvl, $msg, $session);
            }
            return;
        }

        if (!isset($this->_levels[$level])) {
            trigger_error("LogUtil: wrong level specified.");
            return;
        }

        $f_msg = sprintf('[%s%s] %s', date('Y-m-d H:i:s'),
            (is_null($session) ? '' : " $session"), $msg);
        
        switch ($this->_levels[$level]['type']) {
        case 'file':
            if (!FileUtil::write($this->_levels[$level]['filename'], $f_msg."\n", false)) {
                trigger_error("LogUtil: can't write to file '{$this->_levels[$level]['filename']}'.");
            }
            break;
    
        case 'buffer':
            $this->_levels[$level]['_output'] .= $f_msg."\n";
            break;
    
        case 'mail':
            break;
    
        case 'mysql':
            // TODO
            trigger_error('LogUtil: mysql logging is not implemented.');
            break;
    
        case 'echo':
            echo $f_msg."\n";
            break;
    
        case 'none': // just skip
            break;
    
        default:
            // TODO
            trigger_error('LogUtil: type of logging is not correct.');
            break;
        }
    }


    function & getBuffer($level, $clear = false) {
        if (!isset($this->_levels[$level])) {
            trigger_error('LogUtil: wrong level specified.');
            return false;
        }
        
        if ($this->_levels[$level]['type'] != 'buffer') {
            trigger_error('LogUtil: level is not a buffer.');
            return false;
        }
        
        $res = $this->_levels[$level]['_output'];
        if ($clear) {
            $this->_levels[$level]['_output'] = '';
        }
        
        return $res;
    }
}
?>