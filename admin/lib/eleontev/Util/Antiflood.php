<?php

class Antiflood
{
    var $VERSION = "1.00";

    // Ïóòü ê êàòàëîãó, ãäå áóäóò êîïèòñÿ ôàéëû
    // Â ýòîì êàòàëîãå áóäóò ñòåðòû âñå ôàéëû, êðîìå òåõ, ÷òî íà÷èíàþòñÿ
    // ñ "." â èìåíè.
    var $af_path;

    // ñåê  => êîë-âî
    //    íå áîëåå ñêîëüêè çàãðóçîê çà ñêîëüêî ñåêóíä ìîæíî ñäåëàòü,
    //    ÷òîáû íå ïîëó÷èòü èãíîð
    var $af_rules;

    // Prefix for all IP filenames.
    var $af_prefix = "ip_";

    // Ðàç â ñêîëüêî ñåêóíä ïðîâåðÿòü ñòàðûå ôàéëû.
    var $af_cleanupDelta = 1200;

    // ×åðåç ñêîëüêî ñåêóíä ñ÷èòàòü ôàéë ñòàðûì (è óäàëÿòü).
    var $af_cleanupDelDelta = 7200;

    // Creates new antiflood object.
    function Antiflood($path)
    {
        $this->af_path = $path;
        @mkdir($this->af_path, 0770);
        $this->af_rules = array(
            8    => 8,
            10   => 10,      // íå áîëåå 10 çàãðóçîê çà 10 ñåêóíä
            60   => 30,      // íå áîëåå 30 çàãðóçîê çà ìèíóòó
            300  => 50,      // íå áîëåå 50 çàãðóçîê çà 5 ìèíóò
            3600 => 200,     // íå áîëåå 200 çàãðóçîê çà ÷àñ
        );
    }

    // Removes old files if needed.
    function cleanup() {
        $fn = "{$this->af_path}/.last_cleanup";
        if (time()-@filemtime($fn) < $this->af_cleanupDelta) return false;
        @touch($fn);
        $dir = opendir($this->af_path);
        $time = time() - $this->af_cleanupDelDelta;
        while (($e=readdir($dir)) !== false) {
            if ($e[0] == ".") continue;
            $full = "{$this->af_path}/$e";
            if (filemtime($full)<$time && preg_match("!^{$this->af_prefix}!", $e)) {
                @unlink($full);
            }
        }
        closedir($dir);
        return true;
    }

    // Returns 0 if there is no flood.
    // For flooder - returns number of seconds to wait.
    function getTimeout() {
        $this->cleanup();
        $af_fip = $this->af_path."/".$this->af_prefix.str_replace("%2E", ".", urlencode($this->fetchIp()));
        // Read antiflood file.
        @fclose(fopen($af_fip, "a+"));
        $af_f = fopen($af_fip, "r+");
        flock($af_f, LOCK_EX);
        $af_buf = fgets($af_f, 1000);
        if (strlen($af_buf)) {
            $af_buf = explode("|", $af_buf);
        } else {
            $af_buf = array();
            for ($i=0; $i<count($this->af_rules)*2; $i++) $af_buf[] = 0;
        }
        // Check by rules.
        $time = time();
        $af_stopflag = 0;
        $i = 0;
        foreach ($this->af_rules as $af_k=>$af_v) {
            if ($af_buf[$i+1]+$af_k < $time) {
                $af_buf[$i] = 1;
                $af_buf[$i+1] = $time;
            } else {
                if ($af_buf[$i] > $af_v) {
                    $af_stopflag = ($af_buf[$i+1] + $af_k) - $time;
                    $af_buf[$i+1] = $time;
                }
                $af_buf[$i]++;
            }
            $i += 2;
        }
        // Save the file.
        fseek($af_f, 0);
        ftruncate($af_f, 0);
        fwrite($af_f, implode("|", $af_buf));
        fclose($af_f);
        return $af_stopflag;
    }

    // Fetches "real" user IP.
    function fetchIp() {
        // get useful vars:
        $client_ip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : "";
        $x_forwarded_for = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : "";
        $remote_addr = $_SERVER['REMOTE_ADDR'];
        // then the script itself
        if (!empty($client_ip) ) {
            $ip_expl = explode('.', $client_ip);
            $referer = explode('.', $remote_addr);
            if ($referer[0] != $ip_expl[0]) {
                $ip = array_reverse($ip_expl);
                $ret = implode('.',$ip);
            } else {
                $ret = $client_ip;
            }
        } elseif (!empty($x_forwarded_for)) {
            if (strstr($x_forwarded_for, ',')) {
                $ip_expl = explode(',', $x_forwarded_for);
                $ret = end($ip_expl);
            } else {
                $ret = $x_forwarded_for;
            }
        } else {
            $ret = $remote_addr;
        }
        return $ret;
    }
}


/*
$af = new Antiflood("antiflood");
$wait = $af->getTimeout();
if ($wait) {
    echo "Âû ñîçäàåòå ñëèøêîì áîëüøóþ íàãðóçêó íà ñàéò!<br>";
    echo "Ïðèäåòñÿ ïîäîæäàòü $wait ñåêóíä.";
    exit();
}
*/
?>