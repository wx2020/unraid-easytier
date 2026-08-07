#!/bin/bash

. /usr/local/php/easytier-utils/log.sh

log "Scheduling EasyTier restart in 5 seconds"
nohup /bin/sh -c 'sleep 5; exec /etc/rc.d/rc.easytier restart' \
  >/dev/null 2>&1 &
