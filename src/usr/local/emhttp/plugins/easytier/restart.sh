#!/bin/bash
# P1 R-08: flock to avoid concurrent restarts
exec 9>/var/lock/easytier-restart.lock 2>/dev/null || exec 9>/tmp/easytier-restart.lock
if ! flock -n 9 2>/dev/null; then
    . /usr/local/php/easytier-utils/log.sh
    log "Restart already in progress, skipping."
    exit 0
fi

. /usr/local/php/easytier-utils/log.sh

log "Scheduling EasyTier restart in 5 seconds"
nohup /bin/sh -c 'sleep 5; exec /etc/rc.d/rc.easytier restart' \
  >/dev/null 2>&1 &
