Here are several files to be run periodically.
You can configure this by Unix 'crontab' command or through Control Panel provided by your web-hoster.

1. By 'crontab' on Unix.

*/5 * * * *     /usr/local/bin/php DOCUMENT_ROOT/admin/cron/freq.php
0 * * * *       /usr/local/bin/php DOCUMENT_ROOT/admin/cron/hourly.php
01 0 * * *      /usr/local/bin/php DOCUMENT_ROOT/admin/cron/daily.php
10 0 * * 0      /usr/local/bin/php DOCUMENT_ROOT/admin/cron/weekly.php
20 0 1 * *      /usr/local/bin/php DOCUMENT_ROOT/admin/cron/monthly.php

Be sure to specify correct path to PHP interpreter (you can get it by running 'which php' on command shell).
Replace DOCUMENT_ROOT by absolute path to your KBPublisher installation.

You can insert these crontab records by executing 'crontab -e' and editing cron jobs manually.

Or you can create a file (kbp_cron.txt) containing these lines and execute 'crontab kbp_cron.txt' from the shell.
WARNING: all existing records in crontab will be removed!

2. By Control Panel.

It depends heavily on your hosting provider. You should consult it's documentation.
NOTES: freq.php should be run every 5 minutes, daily.php - just after midnight.

