#!/usr/bin/php
<?php

/*
    Copyright (C) 2026  EasyTier Community

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

namespace EasyTier;

define("PLUGIN_NAME", "easytier");

require_once('/usr/local/emhttp/plugins/easytier/include/easytier-utils/Config.php');
require_once('/usr/local/emhttp/plugins/easytier/include/easytier-utils/Utils.php');

use EasyTier\Config;
use EasyTier\Utils;

$logFile = '/var/log/easytier.log';
$checkInterval = 10; // Check every 10 seconds
$maxRestarts = 3; // Maximum restarts within time window
$restartWindow = 300; // 5 minutes
$restartHistory = [];

function logMessage(string $message): void
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] [watcher] {$message}\n", FILE_APPEND);
}

function isEasytierRunning(): bool
{
    $output = shell_exec('/usr/bin/pgrep --ns $$ --euid root -f "^/usr/local/sbin/easytier-core" 2>/dev/null');
    return !empty($output);
}

function canRestart(array &$history, int $window, int $max): bool
{
    $now = time();

    // Remove old restart attempts outside the time window
    $history = array_filter($history, function($timestamp) use ($now, $window) {
        return ($now - $timestamp) < $window;
    });

    return count($history) < $max;
}

function restartEasytier(): bool
{
    global $restartHistory;

    if (!canRestart($restartHistory, $restartWindow, $maxRestarts)) {
        logMessage("ERROR: Too many restart attempts ({$maxRestarts}) within {$restartWindow} seconds. Giving up.");
        return false;
    }

    $restartHistory[] = time();

    logMessage("Attempting to restart easytier-core...");

    // Stop the service
    shell_exec('/etc/rc.d/rc.easytier stop 2>/dev/null');
    sleep(2);

    // Start the service
    shell_exec('/etc/rc.d/rc.easytier start 2>/dev/null');
    sleep(2);

    if (isEasytierRunning()) {
        logMessage("Successfully restarted easytier-core");
        return true;
    } else {
        logMessage("ERROR: Failed to restart easytier-core");
        return false;
    }
}

// Main watcher loop
logMessage("EasyTier watcher started. Monitoring easytier-core process...");

$restartCount = 0;

while (true) {
    if (!isEasytierRunning()) {
        $config = new Config();

        if ($config->Enable) {
            logMessage("WARNING: easytier-core is not running but service is enabled.");
            $restartCount++;

            if (restartEasytier()) {
                $restartCount = 0; // Reset counter on successful restart
            } else {
                logMessage("ERROR: Failed to restart easytier-core after {$restartCount} attempts.");
                // Don't exit, keep trying but with longer interval
                sleep(60);
                continue;
            }
        } else {
            logMessage("easytier-core is not running and service is disabled. Nothing to do.");
            // Exit gracefully if service is disabled
            break;
        }
    } else {
        $restartCount = 0; // Reset counter when process is running
    }

    sleep($checkInterval);
}

logMessage("EasyTier watcher stopped.");
