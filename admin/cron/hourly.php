<?php

require_once 'cron_common.php';

$cron = new Cron('hourly');
$cron->add('scripts/push_mail.php', 'periodicMail', 'hourly');
$cron->add('scripts/scheduled_entry.php', 'processScheduledRecords');
$cron->add('scripts/subscription.php', 'processCommentSubscription');
$cron->add('scripts/subscription.php', 'processNewsSubscription');
$cron->add('scripts/subscription.php', 'processEntriesSubscription');
$cron->add('scripts/mustread_entry.php', 'populateUsersToMustread');
$cron->add('scripts/mustread_entry.php', 'notifyUsersAboutMustread');
$cron->add('scripts/maintain_entry.php', 'unlockEntries', 60*60*3);
$cron->add('scripts/maintain_entry.php', 'freshEntryAutosave', 60*60*48);
$cron->run();

?>