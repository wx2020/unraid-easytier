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

if ( ! defined(__NAMESPACE__ . '\PLUGIN_ROOT') || ! defined(__NAMESPACE__ . '\PLUGIN_NAME')) {
    throw new \RuntimeException("Common file not loaded.");
}

$config = $config ?? new Config();

if (( ! isset($var)) || ( ! isset($display))) {
    echo("Missing required WebGUI variables");
    return;
}

// Define log file
$log_file = '/var/log/easytier.log';

// Get number of lines to display
$lines = intval($_GET['lines'] ?? 100);
if ($lines < 10) $lines = 10;
if ($lines > 1000) $lines = 1000;

// Read log file
$log_content = '';

if (file_exists($log_file)) {
    // Read last N lines from log file
    $file = new \SplFileObject($log_file, 'r');
    $file->seek(PHP_INT_MAX);
    $total_lines = $file->key();

    $start_line = max(0, $total_lines - $lines);
    $file->seek($start_line);

    $log_lines = [];
    while (!$file->eof()) {
        $log_lines[] = $file->fgets();
    }
    $log_content = implode('', array_slice($log_lines, 0, -1));
} else {
    $log_content = "# Log file does not exist: {$log_file}\n# Logs will appear here once EasyTier is running.";
}

// Get file size if exists
$file_size = file_exists($log_file) ? size_formatted(filesize($log_file)) : 'N/A';
$file_modified = file_exists($log_file) ? date('Y-m-d H:i:s', filemtime($log_file)) : 'N/A';

?>

<link type="text/css" rel="stylesheet" href="/plugins/easytier/styles/logs.css">

<table class="unraid tablesorter"><thead><tr><td>EasyTier Logs</td></tr></thead></table>

<!-- Log File Controls -->
<div class="log-controls">
    <div class="log-info">
        <strong>File:</strong> <?= htmlspecialchars($log_file) ?>
        <span class="separator">|</span>
        <strong>Size:</strong> <?= htmlspecialchars($file_size) ?>
        <span class="separator">|</span>
        <strong>Modified:</strong> <?= htmlspecialchars($file_modified) ?>
    </div>

    <div class="line-selector">
        <label for="lineCount">Lines:</label>
        <select id="lineCount" onchange="changeLineCount(this.value)">
            <?= Utils::make_option($lines === 50, '50', '50') ?>
            <?= Utils::make_option($lines === 100, '100', '100') ?>
            <?= Utils::make_option($lines === 200, '200', '200') ?>
            <?= Utils::make_option($lines === 500, '500', '500') ?>
            <?= Utils::make_option($lines === 1000, '1000', '1000') ?>
        </select>
    </div>

    <div class="log-actions">
        <button type="button" onclick="refreshLogs()">Refresh</button>
        <button type="button" onclick="clearLogs()">Clear Log</button>
        <button type="button" onclick="downloadLog()">Download</button>
        <button type="button" onclick="toggleAutoRefresh()" id="autoRefreshBtn">Auto Refresh: Off</button>
    </div>
</div>

<!-- Log Content Display -->
<div class="log-container">
    <pre id="logContent" class="log-content"><?= htmlspecialchars($log_content) ?></pre>
</div>

<script>
let autoRefreshInterval = null;

function changeLineCount(count) {
    const url = new URL(window.location);
    url.searchParams.set('lines', count);
    window.location.href = url.toString();
}

function refreshLogs() {
    const url = new URL(window.location);
    // Add timestamp to prevent caching
    url.searchParams.set('_t', Date.now());
    window.location.href = url.toString();
}

function clearLogs() {
    if (confirm('Are you sure you want to clear the EasyTier log file?')) {
        fetch('/plugins/easytier/include/clear_log.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                log_file: '<?= htmlspecialchars($log_file) ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                refreshLogs();
            } else {
                alert('Error clearing log: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error: ' + error);
        });
    }
}

function downloadLog() {
    const fileName = 'easytier.log';
    const content = document.getElementById('logContent').textContent;

    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = fileName;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

function toggleAutoRefresh() {
    const btn = document.getElementById('autoRefreshBtn');

    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
        btn.textContent = 'Auto Refresh: Off';
        btn.classList.remove('active');
    } else {
        autoRefreshInterval = setInterval(refreshLogs, 5000); // Refresh every 5 seconds
        btn.textContent = 'Auto Refresh: On';
        btn.classList.add('active');
    }
}

// Scroll to bottom of log on page load
window.addEventListener('load', function() {
    const logContent = document.getElementById('logContent');
    logContent.scrollTop = logContent.scrollHeight;
});
</script>
