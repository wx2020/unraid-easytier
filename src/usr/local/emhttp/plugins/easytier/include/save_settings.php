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

require_once '/usr/local/php/easytier-utils/bootstrap.php';

use EasyTier\Config;
use EasyTier\System;
use EasyTier\Utils;

$config = new Config();

// Apply settings
System::configureIPForwarding($config);
System::setExtraInterface($config);
System::createEasytierParamsFile($config);

echo "Settings applied successfully. The EasyTier service will restart.";
