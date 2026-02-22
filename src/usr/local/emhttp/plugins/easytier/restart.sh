#!/bin/bash

. /usr/local/php/easytier-utils/log.sh

log "Restarting EasyTier in 5 seconds"
echo "sleep 5 ; /etc/rc.d/rc.easytier restart" | at now 2>/dev/null
