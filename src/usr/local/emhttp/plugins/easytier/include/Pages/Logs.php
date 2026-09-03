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

// Define log file
$log_file = '/var/log/easytier.log';

// P2 S-05: strict whitelist for lines to avoid enumeration
$allowedLines = [10, 50, 100, 200, 500, 1000];
$lines = intval($_GET['lines'] ?? 100);
if (!in_array($lines, $allowedLines, true)) {
    $lines = 100;
}

// P1 R-06: tail-based reading with size guard
$log_content = '';

if (file_exists($log_file)) {
    $maxSize = 5 * 1024 * 1024;
    $size = filesize($log_file);
    if ($size !== false && $size > $maxSize) {
        $log_content = '# ' . translate('Log file too large, please download') . " ({$size} bytes, >5MB)\n# " . translate('Use Download button to get full log.');
    } else {
        // Use tail for efficient last N lines, fallback to SplFileObject
        $escapedFile = escapeshellarg($log_file);
        $escapedLines = (int)$lines;
        $output = [];
        $ret = 0;
        @exec("tail -n {$escapedLines} {$escapedFile} 2>&1", $output, $ret);
        if ($ret === 0 && $output !== []) {
            $log_content = implode("\n", $output);
        } else {
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
        }
    }
} else {
    $log_content = '# ' . translate('Log file does not exist') . ": {$log_file}\n# " . translate('Logs will appear here once EasyTier is running.');
}

// Get file size if exists
$file_size = file_exists($log_file) ? Utils::size_formatted((int) filesize($log_file)) : 'N/A';
$file_modified = file_exists($log_file) ? date('Y-m-d H:i:s', filemtime($log_file)) : 'N/A';

?>

<link type="text/css" rel="stylesheet" href="/plugins/easytier/styles/logs.css">

<table class="unraid tablesorter"><thead><tr><td><?= translate('EasyTier Logs') ?></td></tr></thead></table>

<!-- Log File Controls -->
<div class="log-controls">
    <div class="log-info">
        <strong><?= translate('File:') ?></strong> <?= htmlspecialchars($log_file) ?>
        <span class="separator">|</span>
        <strong><?= translate('Size:') ?></strong> <?= htmlspecialchars($file_size) ?>
        <span class="separator">|</span>
        <strong><?= translate('Modified:') ?></strong> <?= htmlspecialchars($file_modified) ?>
    </div>

    <div class="line-selector">
        <label for="lineCount"><?= translate('Lines:') ?></label>
        <select id="lineCount" onchange="changeLineCount(this.value)">
            <?= Utils::make_option($lines === 50, '50', '50') ?>
            <?= Utils::make_option($lines === 100, '100', '100') ?>
            <?= Utils::make_option($lines === 200, '200', '200') ?>
            <?= Utils::make_option($lines === 500, '500', '500') ?>
            <?= Utils::make_option($lines === 1000, '1000', '1000') ?>
        </select>
    </div>

    <div class="log-actions">
        <button type="button" onclick="refreshLogs()"><?= translate('Refresh') ?></button>
        <button type="button" onclick="clearLogs()"><?= translate('Clear Log') ?></button>
        <button type="button" onclick="downloadLog()"><?= translate('Download') ?></button>
        <button type="button" onclick="toggleAutoRefresh()" id="autoRefreshBtn"><?= translate('Auto Refresh: Off') ?></button>
    </div>
</div>

<!-- Log Content Display -->
<div class="log-container">
    <pre id="logContent" class="log-content"><?= htmlspecialchars($log_content) ?></pre>
</div>

<script>
let autoRefreshInterval = null;
const easytierLogsI18n = <?= json_encode([
    'clearConfirm' => translate('Are you sure you want to clear the EasyTier log file?'),
    'clearError' => translate('Error clearing log:'),
    'unknownError' => translate('Unknown error'),
    'error' => translate('Error:'),
    'autoOff' => translate('Auto Refresh: Off'),
    'autoOn' => translate('Auto Refresh: On'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

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
    if (confirm(easytierLogsI18n.clearConfirm)) {
        fetch('/plugins/easytier/include/clear_log.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                log_file: '<?= htmlspecialchars($log_file) ?>',
                csrf_token: '<?= htmlspecialchars($GLOBALS['var']['csrf_token'] ?? '') ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                refreshLogs();
            } else {
                alert(easytierLogsI18n.clearError + ' ' + (data.error || easytierLogsI18n.unknownError));
            }
        })
        .catch(error => {
            alert(easytierLogsI18n.error + ' ' + error);
        });
    }
}

function downloadLog() {
    // P2 W-06: stream full file when log is large
    const sizeText = document.querySelector('.log-info')?.textContent || '';
    const isLarge = sizeText.includes('>5MB') || document.getElementById('logContent').textContent.includes('too large');
    if (isLarge) {
        window.location.href = '/plugins/easytier/include/download_log.php';
        return;
    }
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

function fetchLogTail() {
    const lines = new URL(window.location).searchParams.get('lines') || '100';
    fetch('/plugins/easytier/include/log_tail.php?lines=' + encodeURIComponent(lines))
        .then(r => r.text())
        .then(text => {
            const el = document.getElementById('logContent');
            el.textContent = text;
            el.scrollTop = el.scrollHeight;
        })
        .catch(() => refreshLogs());
}
function toggleAutoRefresh() {
    const btn = document.getElementById('autoRefreshBtn');

    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
        btn.textContent = easytierLogsI18n.autoOff;
        btn.classList.remove('active');
    } else {
        // P2 W-02: AJAX refresh instead of full page reload
        autoRefreshInterval = setInterval(fetchLogTail, 5000);
        btn.textContent = easytierLogsI18n.autoOn;
        btn.classList.add('active');
        fetchLogTail();
    }
}

// Scroll to bottom of log on page load
window.addEventListener('load', function() {
    const logContent = document.getElementById('logContent');
    logContent.scrollTop = logContent.scrollHeight;
});
</script>
