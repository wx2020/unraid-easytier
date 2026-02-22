#!/bin/bash
# Logging utility for EasyTier plugin

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> /var/log/easytier-utils.log
}
