<?php
// full path to kb directory
$app_dir = str_replace(array('\\'), '/', getcwd() . '/');    // trying to guess kb directory
//$app_dir = '/path_to/kb/';                                // set it manually 


$admin_dir  = $app_dir . 'admin/';
$client_dir = $app_dir . 'client/inc/';


/* DO NOT MODIFY */
require_once $admin_dir . 'config.inc.php';
require_once $admin_dir . 'config_more.inc.php';
require_once $client_dir . 'common.inc.php';
require_once 'Cache/Lite.php';


$category_id = (!empty($_GET['c'])) ? intval(($_GET['c'])) : false;
$type = 'article';
$cache_id = 'public_rss' . $category_id ;
$lifetime = 3600*1; // cache valid for 1 hour


if(isset($_GET['t']) && $_GET['t'] == 'n') {
    $type = 'news';
    $cache_id = 'public_rss_news';
    
} elseif(isset($_GET['e'])) {
    $type = 'comment';
    $category_id = intval($_GET['e']);
    $cache_id = 'public_rss_comment' . $category_id;   
}

if(isset($_GET['force_refresh'])) {
    $lifetime = 1;
}


$cache = new Cache_Lite();
// $cache->setOption('caching', false);
$cache->setOption('cacheDir', APP_CACHE_DIR);
$cache->setOption('lifeTime', $lifetime);

$cache_id = $cache_id;
$cache_gr = 'rss';

$cache_data = $cache->get($cache_id, $cache_gr);
if($cache_data !== false) {
	$rss = new RSSCreator();
	$rss->setEncoding($conf['lang']['meta_charset']);
    $rss->sendXMLHeader();
    echo $cache_data;
    exit;
}


require_once $client_dir . 'KBClientController.php';
require_once $client_dir . 'KBClientBaseModel.php';
require_once $client_dir . 'KBClientRSSModel.php';
require_once $client_dir . 'DocumentParser.php';


$manager = KBClientRSSModel::factory($type);
$manager->setEntryPublishedStatus();
$settings = $manager->getSettings(2);

if($settings['rss_generate'] == 'none') {
    exit;

} elseif($type == 'article' && $settings['rss_generate'] == 'one') {
    $category_id = false;
}

$categories = array();
if($type == 'article' && $settings['rss_generate'] == 'top') {
    $categories = $manager->getCategories();
    
    // exit if it not top category
    // if($category_id && (!isset($categories[$category_id]) || $categories[$category_id]['parent_id'] != 0)) {
    //     exit;
    // }
}


$controller = new KBClientController();
$controller->setDirVars($settings);
$controller->setModRewrite($settings['mod_rewrite']);

$channel_data = $manager->getChannelData($category_id, $categories, $controller, $settings);
$entries = &$manager->getEntries($category_id, $categories, $controller, 15, 250);

$rss = new RSSCreator();
$rss->setEncoding($conf['lang']['meta_charset']);
$rss->setChannel('title', $channel_data['title']);
$rss->setChannel('link', $channel_data['link']);
$rss->setChannel('description', $channel_data['description']);

foreach($entries as $k => $v) {
    $rss->setItem($v['title'], $v['link'], $v);
}


$data = $rss->getXML(false);
$cache->save($data);

$rss->sendXMLHeader();
echo $data;
die;
?>