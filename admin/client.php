<?php
require_once 'config.inc.php';
require_once 'config_more.inc.php';

$page = APP_CLIENT_PATH . '?View=logout';
if(!empty($_GET['page'])) {
    $page = WebUtil::unserialize_url($_GET['page']);
}

header("Location: " . $page);
exit;
?>