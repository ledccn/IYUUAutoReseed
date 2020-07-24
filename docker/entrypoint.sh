#!/bin/bash
DEFAULT_CRON="0 */1 * * *"
cron=${cron:-$DEFAULT_CRON}
set -e

echo "$cron /usr/local/bin/php /IYUU/iyuu.php" | crontab -

/usr/local/bin/php /IYUU/iyuu.php

/usr/sbin/crond -f
