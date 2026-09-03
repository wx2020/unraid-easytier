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

class Utils extends \EDACerton\PluginUtils\Utils
{
    public function setPHPDebug(): void
    {
        $debug = file_exists("/boot/config/plugins/easytier/debug");

        if ($debug && ! defined("PLUGIN_DEBUG")) {
            error_reporting(E_ALL);
            define("PLUGIN_DEBUG", true);
        }
    }

    private static ?Utils $sharedInstance = null;

    public static function logwrap(string $message, bool $debug = false, bool $rateLimit = false): void
    {
        if ( ! defined(__NAMESPACE__ . "\PLUGIN_NAME")) {
            throw new \RuntimeException("PLUGIN_NAME is not defined.");
        }
        // P2 C-04: singleton to avoid per-call allocation in watcher/daily
        if (self::$sharedInstance === null) {
            self::$sharedInstance = new Utils(PLUGIN_NAME);
        }
        self::$sharedInstance->logmsg($message, $debug, $rateLimit);
    }

    /**
     * @return array<string>
     */
    public static function runwrap(string $command, bool $alwaysShow = false, bool $show = true): array
    {
        if ( ! defined(__NAMESPACE__ . "\PLUGIN_NAME")) {
            throw new \RuntimeException("PLUGIN_NAME is not defined.");
        }
        $utils = new Utils(PLUGIN_NAME);
        return $utils->run_command($command, $alwaysShow, $show);
    }

    /**
     * Format file size in human-readable format
     */
    public static function size_formatted(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);

        if ($bytes === 0) {
            return '0 B';
        }

        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
