<?php
$app_dir = str_replace(array('\\'), '/', getcwd() . '/');    // trying to guess kb directory
//$app_dir = $_SERVER['DOCUMENT_ROOT'] . '/kb/';            // set it manually

$admin_dir  = $app_dir . 'admin/';
$font_dir = $app_dir . 'client/fonts/';


/* DO NOT MODIFY */
require_once $admin_dir . 'config.inc.php';
require_once $admin_dir . 'config_more.inc.php';
require_once 'autoload/autoload.php';

@session_name($conf['session_name']);
session_start();

header('Content-Type: image/jpeg');

// 'VeraBI.ttf', , 'VeraMoBI.ttf'
$fonts = array('Vera.ttf', 'VeraIt.ttf', 'VeraMono.ttf', 'VeraMoIt.ttf', 'VeraSe.ttf');
shuffle($fonts);
$font = current($fonts);

$captcha = new CaptchaImage();
$captcha->setFont($font_dir . $font);

$_SESSION['kb_captcha_'] = $captcha->getCode();
$_SESSION['kb_captchaip_'] = WebUtil::getIP();
$captcha->getImage();
?>