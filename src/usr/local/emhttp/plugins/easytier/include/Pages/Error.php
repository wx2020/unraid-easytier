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

$e = $e ?? null;

if (( ! isset($var)) || ( ! isset($display))) {
    echo("Missing required WebGUI variables");
    return;
}
?>

<table class="unraid tablesorter"><thead><tr><td>Error</td></tr></thead></table>

<dl>
    <dt></dt>
    <dd>
        <div class="error">
            <strong>An error occurred:</strong><br>
            <?php if ($e): ?>
                <?= htmlspecialchars($e->getMessage()) ?>
            <?php else: ?>
                Unknown error
            <?php endif; ?>
        </div>
    </dd>
</dl>

<blockquote class='inline_help'>
    Please check the plugin log at /var/log/easytier-utils.log for more details.
</blockquote>
