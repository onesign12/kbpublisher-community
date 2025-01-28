<?php

/*
 * daily.php Should be executed just after midnight!
 */

require_once 'cron_common.php';

$cron = new Cron('daily');
$cron->add('scripts/push_mail.php', 'periodicMail', 'daily');
$cron->add('scripts/reports.php', 'updateReportEntry'); // should be added before syncHits
$cron->add('scripts/reports.php', 'syncHits');
$cron->add('scripts/reports.php', 'updateReportSummary');
$cron->add('scripts/reports.php', 'updateSearchReport');
$cron->add('scripts/reports.php', 'syncUserActivityReport');
$cron->add('scripts/reports.php', 'freshUserActivityReport');
$cron->add('scripts/maintain_entry.php', 'updateBodyIndex');
$cron->add('scripts/maintain_entry.php', 'updateTagKeywords');
$cron->add('scripts/maintain_entry.php', 'syncTagKeywords');
$cron->add('scripts/kbp_upgrade_tasks.php', 'upgradeHistory');
$cron->add('scripts/db_tech_functions.php', 'freshUserTemp'); // moved from weekly 30-06-2022
$cron->add('scripts/db_tech_functions.php', 'freshUserAuthToken'); // added 30-06-2022
$cron->add('scripts/db_tech_functions.php', 'optimizeTables', 10);
$cron->add('scripts/db_tech_functions.php', 'repairTables', 1);
$cron->add('scripts/kbp_tech_functions.php', 'setupTest', array(array('freq','hourly'), 1));
$cron->add('scripts/directory.php', 'spyDirectoryFiles');
$cron->add('scripts/consistency.php', 'inheritCategoryPrivateAttributes');
$cron->add('scripts/automations.php', 'executeAutomations');
$cron->add('scripts/automations.php', 'executeEmailAutomations');
$cron->add('scripts/mustread_entry.php', 'disactivateExpiredMustreads');
$cron->add('scripts/mustread_entry.php', 'populateUsersToMustread');
$cron->add('scripts/mustread_entry.php', 'remindUsersAboutMustread', array(array(4,10))); // 4 and 10 days after event
$cron->add('scripts/indexes.php', 'sphinxIndex', false);
$cron->run();

?>