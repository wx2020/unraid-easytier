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

$node_running = System::isRunning();
$node_output = $node_running ? System::getNodeOutput() : [];

?>

<link type="text/css" rel="stylesheet" href="/plugins/easytier/styles/dashboard.css">

<table class="unraid tablesorter"><thead><tr><td><?= translate('EasyTier Node Status') ?></td></tr></thead></table>

<?php if ($node_output !== []): ?>
    <pre id="nodeOutput" class="node-output"><?= htmlspecialchars(implode(PHP_EOL, $node_output)) ?></pre>
<?php elseif (!$node_running): ?>
    <div class="node-info-empty">
        <?= translate('EasyTier is not running. Start the service from the Settings tab.') ?>
    </div>
<?php else: ?>
    <div class="node-info-empty">
        <?= translate('EasyTier is running, but no node information is available yet.') ?>
    </div>
<?php endif; ?>

<p class="node-info-source"><?= translate('Source:') ?> <code>easytier-cli node</code></p>
