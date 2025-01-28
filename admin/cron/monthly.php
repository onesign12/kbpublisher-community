<?php

require_once 'cron_common.php';

$cron = new Cron('monthly');
$cron->add('scripts/push_mail.php', 'periodicMail', 'monthly');
$cron->add('scripts/kbp_tech_functions.php', 'setupTest', array(array('weekly'), 1));
$cron->add('scripts/kbp_tech_functions.php', 'setupValidate');
$cron->add('scripts/kbp_tech_functions.php', 'getNotUsedHtmlEditorFiles');
$cron->run();

?>