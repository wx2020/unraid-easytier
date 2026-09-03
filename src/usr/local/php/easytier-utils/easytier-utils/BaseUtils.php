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

namespace EDACerton\PluginUtils;

class Utils
{
    protected string $plugin_name;

    public function __construct(string $plugin_name)
    {
        $this->plugin_name = $plugin_name;
    }

    public function logmsg(string $message, bool $debug = false, bool $rateLimit = false): void
    {
        $log_file = "/var/log/{$this->plugin_name}.log";

        if ($debug && !defined("PLUGIN_DEBUG")) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] {$message}" . PHP_EOL;

        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }

    /**
     * @return array<string>
     */
    public function run_command(string $command, bool $alwaysShow = false, bool $show = true): array
    {
        $output = array();
        $return_var = 0;

        if ($show || $alwaysShow) {
            $this->logmsg("Running: {$command}");
        }

        // P2 C-07: timeout and output limit to avoid PHP-FPM hang on easytier-cli
        $hasTimeout = is_executable('/usr/bin/timeout') || is_executable('/bin/timeout');
        $wrapped = $command . " 2>&1";
        if ($hasTimeout) {
            $wrapped = "timeout 5 " . $wrapped;
        }
        exec($wrapped, $output, $return_var);

        // Limit output to 2000 lines to prevent OOM
        if (count($output) > 2000) {
            $output = array_slice($output, 0, 2000);
            $output[] = '... truncated (2000 lines limit)';
        }

        if ($return_var !== 0 && $show) {
            $this->logmsg("Command failed with return code: {$return_var}");
        }

        return $output;
    }

    public static function make_option(bool $condition, string $value, string $label): string
    {
        $selected = $condition ? ' selected="selected"' : '';
        return "<option value='{$value}'{$selected}>{$label}</option>";
    }

    public static function auto_v(string $path): string
    {
        $version_file = "/var/log/emhttp/version";
        $version = file_exists($version_file) ? trim(file_get_contents($version_file)) : '';
        return $version ? "{$path}?v={$version}" : $path;
    }
}
