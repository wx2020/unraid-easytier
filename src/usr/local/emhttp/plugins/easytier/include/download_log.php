<?php
/*
    Copyright (C) 2026  EasyTier Community
    P2 W-06: stream full log file
*/
namespace EasyTier;

require_once dirname(__FILE__) . '/common.php';

$log_file = '/var/log/easytier.log';
if (!file_exists($log_file)) {
    http_response_code(404);
    exit(translate('Log file does not exist'));
}
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="easytier.log"');
header('Content-Length: ' . filesize($log_file));
readfile($log_file);
