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

class System
{
    protected static function updateHostsFile(string $hostname, string $ip): void
    {
        $hosts_file = "/etc/hosts";
        $hosts_content = file_get_contents($hosts_file);

        // Check if entry already exists
        $pattern = '/^\s*' . preg_quote($ip, '/') . '\s+' . preg_quote($hostname, '/') . '\s*$/m';

        if (preg_match($pattern, $hosts_content)) {
            // Entry exists, update it
            $hosts_content = preg_replace($pattern, "{$ip}\t{$hostname}", $hosts_content);
        } else {
            // Add new entry
            $hosts_content .= "{$ip}\t{$hostname}" . PHP_EOL;
        }

        file_put_contents($hosts_file, $hosts_content);
    }
}
