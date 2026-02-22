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

if (( ! isset($var)) || ( ! isset($display))) {
    echo("Missing required WebGUI variables");
    return;
}

$config = $config ?? new Config();

if (!$config->Enable) {
    ?>
    <table class="unraid tablesorter"><thead><tr><td>EasyTier Status</td></tr></thead></table>
    <dl>
        <dt></dt>
        <dd>
            <div class="warning">
                <strong>EasyTier is disabled</strong><br>
                Enable EasyTier in Settings to use this feature.
            </div>
        </dd>
    </dl>
    <?php
    return;
}

// Get EasyTier status information
// Note: This will need to be adjusted based on EasyTier's actual CLI/API
$status = @json_decode(shell_exec('/usr/local/sbin/easytier-cli status --json 2>/dev/null'), true);
$peers = $status['peers'] ?? [];
$virtual_ip = $status['virtual_ip'] ?? 'Not assigned';
$hostname = $status['hostname'] ?? gethostname();
?>

<table class="unraid tablesorter"><thead><tr><td>EasyTier Dashboard</td></tr></thead></table>

<dl>
    <dt>Status</dt>
    <dd>
        <?php if ($status): ?>
            <span class="green-text">● Running</span>
        <?php else: ?>
            <span class="red-text">● Not Running</span>
        <?php endif; ?>
    </dd>
</dl>

<dl>
    <dt>Hostname</dt>
    <dd><?= htmlspecialchars($hostname) ?></dd>
</dl>

<dl>
    <dt>Virtual IP</dt>
    <dd><?= htmlspecialchars($virtual_ip) ?></dd>
</dl>

<dl>
    <dt>Network Name</dt>
    <dd><?= htmlspecialchars($config->NetworkName ?: 'Default') ?></dd>
</dl>

<?php if (!empty($peers)): ?>
    <table class="unraid tablesorter"><thead><tr><td>Connected Peers (<?= count($peers) ?>)</td></tr></thead></table>

    <table class="tablesorter unraid">
        <thead>
            <tr>
                <th>Hostname</th>
                <th>Virtual IP</th>
                <th>Connection Type</th>
                <th>Latency</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($peers as $peer): ?>
                <tr>
                    <td><?= htmlspecialchars($peer['hostname'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($peer['virtual_ip'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($peer['connection_type'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($peer['latency'] ?? 'N/A') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <dl>
        <dt></dt>
        <dd>
            <div class="info">
                <strong>No peers connected</strong><br>
                Peers will appear here when they connect to the EasyTier network.
            </div>
        </dd>
    </dl>
<?php endif; ?>

<script>
// Auto-refresh every 30 seconds
setTimeout(function() {
    location.reload();
}, 30000);
</script>
