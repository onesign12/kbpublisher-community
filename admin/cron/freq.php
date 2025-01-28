<?php

/* It should be executed every 5 minutes or you must change FREQ_FREQUENCY value. */
define('FREQ_FREQUENCY', 5);

require_once 'cron_common.php';

$cron = new Cron('freq');
$cron->add('scripts/push_mail.php', 'dbMail', FREQ_FREQUENCY);
$cron->add('scripts/push_mail.php', 'periodicMail', 'freq');
$cron->add('scripts/indexes.php', 'sphinxTasks');
$cron->add('scripts/indexes.php', 'sphinxIndex', true);
$cron->run();

?>