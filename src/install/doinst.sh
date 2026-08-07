#!/bin/sh

mkdir -p /etc/cron.daily /etc/rc.d

# Older releases used array lifecycle hooks. Remove them during upgrade so
# startup is controlled by plugin loading at system boot instead.
rm -f /usr/local/emhttp/plugins/easytier/event/array_started \
    /usr/local/emhttp/plugins/easytier/event/stopped
rmdir /usr/local/emhttp/plugins/easytier/event 2>/dev/null || true

# Unraid caches parsed plugin translations in a .dot file and does not
# rebuild it when the corresponding .txt file is replaced during an upgrade.
rm -f /usr/local/emhttp/languages/zh_CN/easytier.dot

ln -sfn /usr/local/php/easytier-utils/daily.sh /etc/cron.daily/easytier-daily
ln -sfn /usr/local/etc/rc.d/rc.easytier /etc/rc.d/rc.easytier

chmod 0755 /usr/local/etc/rc.d/rc.easytier \
    /usr/local/emhttp/plugins/easytier/restart.sh \
    /usr/local/emhttp/plugins/easytier/easytier-watcher.php \
    /usr/local/php/easytier-utils/pre-startup.php \
    /usr/local/php/easytier-utils/daily.php \
    /usr/local/php/easytier-utils/daily.sh \
    /usr/local/php/easytier-utils/log.sh

chmod 0644 /etc/logrotate.d/easytier
chown root:root /etc/logrotate.d/easytier
