<?php
/**
 * MailPool enables message pooling for futher sending.
 *
 * Pooling:
 *    - subdirs for each time period (e.g. hourly, daily) are created
 *    - content files are created inside period subdir
 * Sending:
 *    - files from period subdir are read and mail headers are parsed
 *    - messages are sent and file are deleted.
 */
class MailPool
{    
    var $_tpl;
    var $_fmode;
    var $_mail_sender;


    /**
     * @param string $dir Writable directory for creating period subdirs (with trailing slash!)
     * @param string $sub_tpl sprintf() template for subdirs names
     * @param int $fmode File permissions for subdirs and messages creation
     */
    function __construct($dir, $sub_tpl = 'cron_mail_%s/', $fmode = 0777) {
        $this->_fmode = $fmode;
        $this->_tpl = $dir . $sub_tpl;
    }


    /**
     * If period subdir does not exist it is created.
     *
     * @return mixed FALSE on error, string on success
     */
    function getPeriodDir($period) {
        $dir = sprintf($this->_tpl, $period);
        if (!is_dir($dir) || !is_writable($dir)) { // it can be writable and not a directory
            // use chmod() because umask would be applied by mkdir()
            if (!mkdir($dir, $this->_fmode, true) || !chmod($dir, $this->_fmode)) {
                trigger_error('Cannot create dir: '. $dir);
                return false;
            }
        }
        return $dir;
    }


    /**
     * Add message to the period pool.
     *
     * @return boolean FALSE on error
     */
    function add($period, $to, $subj, $body) {
        $dir = $this->getPeriodDir($period);
        if (!$dir) {
            return false;
        }

        do {
            $fname = $dir .'msg_'. md5($period . $to . time());
        } while (file_exists($fname));

        return $this->createFile($fname, $to, $subj, $body);
    }


    /**
     * Send messages by mail.
     *
     * @param int $limit Maximum number of messages to be sent (0|NULL: no limit)
     */
    function sendMail($period, $exitcode, $limit = 0) {
        $sent = 0;
        $dir = $this->getPeriodDir($period);
        if (!$dir) {
            return $sent;    // 0
        }

        $files = glob($dir . '*');
        if ($files && count($files) > 0) {
            $this->_mail_sender = new AppMailSender();
            if ($limit == 0) {
                $limit = count($files);
            }
            
            foreach ($files as $fname) {
                if ($sent >= $limit) {
                    break;
                }
                
                if ($this->_processFile($fname)) {
                    if (!@unlink($fname)) {
                        $exitcode = 0;
                        trigger_error('Cannot delete message file: '. $fname);
                    }
                    $sent += 1;
                    
                } else {
                    $exitcode = 0;
                    trigger_error('Cannot process message file: '. $fname);
                }
            }
        }

        return array($sent, $exitcode);
    }


    /**
     * Creates message file with headers.
     *
     * @return boolean FALSE on error
     */
    function createFile($fname, $to, $subj, $body) {
        $msg = sprintf("To: %s\nSubject: %s\n\n%s", $to, $subj, $body);
        return FileUtil::write($fname, $msg);
    }


    /**
     * Parse and send message from file.
     */
    function _processFile($fname) {
        $sent = false;

        $f = @fopen($fname, 'r');
        if ($f) {
            $s_to = @fgets($f);
            $s_subj = @fgets($f);
            @fgets($f); // skip empty line (\n\n)
            $s_body = @file_get_contents($fname, 0, NULL, ftell($f));
            if ($s_to && strpos($s_to, 'To: ') == 0 &&
                $s_subj && strpos($s_subj, 'Subject: ') == 0 && $s_body) {

                $s_to = substr($s_to, 4);
                $s_subj = substr($s_subj, 9);
                $s_body = trim($s_body);

                $sent = $this->_mail_sender->sendPlain($s_to, $s_subj, $s_body);
                
                // notifications
                // $user_id = $this->_mail_sender->model->getUserIdByEmail(trim($s_to), false);
                $user_id = 0;// disable notifications for cron tasks
                
                if($user_id) {
                    $vars = array(
                        'title' => $s_subj
                    );
                    
                    $options = array(
                        'letter_key'  => 'cron',
                        'ntf_key'     => 'cron',
                        'ntf_type'    => (strpos($fname, 'critical') !== false) ? 'error' : 'info',
                        'ntf_message' => BaseView::getSubstringSign($s_body, 5000),
                        'ntf_users'   => $user_id
                    );

                    $ntf_sent = AppNotificationSender::send($options, $vars);
                }                
                
            } // else : very bad...
            fclose($f);
        }

        return $sent;
    }

}

?>