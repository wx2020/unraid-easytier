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
    protected const HOSTS_START = '# BEGIN EASYTIER MANAGED';
    protected const HOSTS_END = '# END EASYTIER MANAGED';

    protected static function replaceHostsEntries(array $entries): void
    {
        $hosts_file = "/etc/hosts";
        $content = file_exists($hosts_file) ? (string) file_get_contents($hosts_file) : '';
        $pattern = '/\R?' . preg_quote(self::HOSTS_START, '/') . '.*?' .
            preg_quote(self::HOSTS_END, '/') . '\R?/s';
        $content = preg_replace($pattern, PHP_EOL, $content) ?? $content;
        $content = rtrim($content) . PHP_EOL;

        if ($entries !== []) {
            $content .= self::HOSTS_START . PHP_EOL;
            foreach ($entries as $hostname => $ip) {
                $content .= "{$ip}\t{$hostname}" . PHP_EOL;
            }
            $content .= self::HOSTS_END . PHP_EOL;
        }

        file_put_contents($hosts_file, $content, LOCK_EX);
    }
}
