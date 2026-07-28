<?php

declare(strict_types=1);

$GLOBALS['var'] = ['csrf_token' => 'test-token'];
$_GET = [];

require_once '/usr/local/emhttp/plugins/easytier/include/page.php';

$entryPages = [
    'EasyTier.page' => 'Server Configuration',
    'EasyTier-1-Settings.page' => 'Server Configuration',
    'EasyTier-2-Logs.page' => 'EasyTier Logs',
];
foreach ($entryPages as $entryPage => $expectedText) {
    ob_start();
    include '/usr/local/emhttp/plugins/easytier/' . $entryPage;
    $entryOutput = ob_get_clean();
    if (!is_string($entryOutput) || !str_contains($entryOutput, $expectedText)) {
        throw new RuntimeException("{$entryPage} did not render expected content.");
    }
}

$settings = \EasyTier\getPage('Settings', false);
if (!str_contains($settings, 'Server Configuration') || !str_contains($settings, 'serverForm')) {
    throw new RuntimeException('Settings page did not render expected content.');
}

$logs = \EasyTier\getPage('Logs', false);
if (!str_contains($logs, 'EasyTier Logs') || !str_contains($logs, 'logContent')) {
    throw new RuntimeException('Logs page did not render expected content.');
}

$error = \EasyTier\includePage(
    '/usr/local/emhttp/plugins/easytier/include/Pages/Error.php',
    ['e' => new RuntimeException('render test')]
);
if (!str_contains($error, 'render test') || !str_contains($error, 'easytier-error.log')) {
    throw new RuntimeException('Error page did not render expected content.');
}

echo "WebUI runtime render tests passed." . PHP_EOL;
