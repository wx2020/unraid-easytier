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

define("PLUGIN_NAME", "easytier");

require_once('/usr/local/emhttp/plugins/easytier/include/easytier-utils/Config.php');
require_once('/usr/local/emhttp/plugins/easytier/include/easytier-utils/System.php');
require_once('/usr/local/emhttp/plugins/easytier/include/easytier-utils/Utils.php');

use EasyTier\Config;
use EasyTier\System;
use EasyTier\Utils;

$config = new Config();

if (!$config->Enable) {
    exit(0);
}

// Perform daily maintenance tasks
// For example: log rotation, cleanup, health checks
Utils::logwrap("Running daily maintenance tasks");

exit(0);
