( cd etc/cron.daily ; rm -rf easytier-daily )
( cd etc/cron.daily ; ln -sf /usr/local/php/easytier-utils/daily.sh easytier-daily )
( cd usr/local/emhttp/plugins/easytier/event ; rm -rf array_started )
( cd usr/local/emhttp/plugins/easytier/event ; ln -sf ../restart.sh array_started )
( cd usr/local/emhttp/plugins/easytier/event ; rm -rf stopped )
( cd usr/local/emhttp/plugins/easytier/event ; ln -sf ../restart.sh stopped )

chmod 0644 /etc/logrotate.d/easytier
chown root:root /etc/logrotate.d/easytier
