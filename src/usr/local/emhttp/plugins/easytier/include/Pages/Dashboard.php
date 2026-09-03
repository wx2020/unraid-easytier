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
// P2 W-04: structured parse for peer count / IP if JSON available
$peer_count = null;
$virtual_ip = null;
if ($node_output !== []) {
    $joined = implode("\n", $node_output);
    $json = json_decode($joined, true);
    if (is_array($json)) {
        $peer_count = $json['peer_count'] ?? $json['peers'] ?? null;
        if (is_array($peer_count)) $peer_count = count($peer_count);
        $virtual_ip = $json['virtual_ip'] ?? $json['ipv4'] ?? null;
    }
    if ($peer_count === null) {
        // fallback: count peer lines in logs page? keep raw
        $peer_count = null;
    }
}

?>

<link type="text/css" rel="stylesheet" href="/plugins/easytier/styles/dashboard.css">

<table class="unraid tablesorter"><thead><tr><td><?= translate('EasyTier Node Status') ?></td></tr></thead></table>

<?php if ($virtual_ip !== null || $peer_count !== null): ?>
    <div class="node-info-summary">
        <?php if ($virtual_ip !== null): ?><span><?= translate('Virtual IP') ?>: <code><?= htmlspecialchars((string)$virtual_ip) ?></code></span><?php endif; ?>
        <?php if ($peer_count !== null): ?><span style="margin-left:12px;"><?= translate('Peers') ?>: <?= htmlspecialchars((string)$peer_count) ?></span><?php endif; ?>
    </div>
<?php endif; ?>

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
