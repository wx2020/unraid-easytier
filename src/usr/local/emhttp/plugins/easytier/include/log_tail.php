<?php
/*
    Copyright (C) 2026  EasyTier Community
    P2 W-02: tail endpoint for AJAX log refresh
*/
namespace EasyTier;

require_once dirname(__FILE__) . '/common.php';

$log_file = '/var/log/easytier.log';
$allowed = [10, 50, 100, 200, 500, 1000];
$lines = intval($_GET['lines'] ?? 100);
if (!in_array($lines, $allowed, true)) {
    $lines = 100;
}
header('Content-Type: text/plain; charset=utf-8');
if (!file_exists($log_file)) {
    http_response_code(404);
    echo '# ' . translate('Log file does not exist') . ": {$log_file}\n";
    exit;
}
$maxSize = 5 * 1024 * 1024;
$size = filesize($log_file);
if ($size !== false && $size > $maxSize) {
    echo '# ' . translate('Log file too large, please download') . " ({$size} bytes)\n";
    exit;
}
$escapedFile = escapeshellarg($log_file);
$output = [];
$ret = 0;
@exec("tail -n {$lines} {$escapedFile} 2>&1", $output, $ret);
if ($ret === 0) {
    echo implode("\n", $output);
} else {
    // fallback simple
    echo implode("\n", array_slice(file($log_file) ?: [], -$lines));
}
