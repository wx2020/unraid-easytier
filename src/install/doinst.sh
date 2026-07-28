#!/bin/sh

mkdir -p /etc/cron.daily /etc/rc.d /usr/local/emhttp/plugins/easytier/event
ln -sfn /usr/local/php/easytier-utils/daily.sh /etc/cron.daily/easytier-daily
ln -sfn /usr/local/etc/rc.d/rc.easytier /etc/rc.d/rc.easytier
ln -sfn ../restart.sh /usr/local/emhttp/plugins/easytier/event/array_started
ln -sfn ../restart.sh /usr/local/emhttp/plugins/easytier/event/stopped

chmod 0755 /usr/local/etc/rc.d/rc.easytier \
    /usr/local/emhttp/plugins/easytier/restart.sh \
    /usr/local/emhttp/plugins/easytier/easytier-watcher.php \
    /usr/local/php/easytier-utils/pre-startup.php \
    /usr/local/php/easytier-utils/daily.php \
    /usr/local/php/easytier-utils/daily.sh \
    /usr/local/php/easytier-utils/log.sh

chmod 0644 /etc/logrotate.d/easytier
chown root:root /etc/logrotate.d/easytier
