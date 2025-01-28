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

require_once 'config.inc.php';
require_once 'config_more.inc.php';


session_name($conf['session_name']);
session_start();


foreach($_GET as $k => $v) {
    $k = str_replace('amp;', '', $k);
    $_GET[$k] = $v;
}


$delay_time = $_GET['delay_time'];
$msg = $_GET['msg'];
$url = WebUtil::unserialize_url($_GET['page_to_return']);
$url = str_replace(array('{','}'), '', $url); // fix

if(strpos($url, 'client.php?page=') !== false) {
    $page = str_replace('client.php?page=', '', $url);
    $url = 'client.php?page=' . WebUtil::serialize_url($page);
}

//echo "<pre>"; print_r($url); echo "</pre>";
//exit;


$lang = (!empty($_SESSION['kbp_lang_'])) ? $_SESSION['kbp_lang_'] : 'en';
require_once APP_MSG_DIR . $lang . '/config_lang.php';
if(!defined('APP_LANG')) {
    define('APP_LANG', $lang);
}
$meta_charset = $conf['lang']['meta_charset'];


//echo "<pre>"; print_r($_GET); echo "</pre>";
//echo "<pre>"; print_r($url); echo "</pre>";
//exit;


$msg = AppMsg::getMsg('after_action_msg.ini', false, $msg);
$msg['body'] .= '<br>Redirecting ... <br> 
Click the link <a href="{url}">go back</a> if nothing happened';
$msg =  BoxMsg::factory('success', $msg, ['url', $url]);

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
    <title>Untitled</title>
    <meta http-equiv="content-type" content="text/html; charset=<?php echo $meta_charset; ?>">
    <link rel="STYLESHEET" type="text/css" href="css/style.css">
    <script>
    <!--
    function redirect() {
        document.location.replace('<?php echo $url; ?>');
    }
    //-->
    </script>
</head>

<!-- setTimeout( function() { redirect(counter); }, 1000 ); -->
<body onLoad="setTimeout(redirect, <?php echo $delay_time; ?>)">

<table width="70%" height="100%" align="center">
    <tr><td height="50%" valign="middle">
    
    <?php echo $msg; ?>
    
    </td></tr>
    <tr><td></td></tr>
</table>


</body>
</html>