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

require_once dirname(__FILE__) . '/common.php';

use EasyTier\Utils;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(translate('Method not allowed'));
}

requireCsrfToken($_POST['csrf_token'] ?? $_REQUEST['csrf_token'] ?? null);
// P1 W-03: async trigger to avoid blocking HTTP (wait_for_network up to 60s)
exec("nohup /usr/local/emhttp/plugins/easytier/restart.sh >/dev/null 2>&1 &");
http_response_code(202);

echo translate('EasyTier service restart scheduled.');
