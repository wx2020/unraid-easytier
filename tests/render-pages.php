<?php

declare(strict_types=1);

$GLOBALS['var'] = ['csrf_token' => 'test-token'];
$_GET = [];

require_once '/usr/local/emhttp/plugins/easytier/include/page.php';

$entryPages = [
    'EasyTier.page' => 'EasyTier Node Status',
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

$dashboard = \EasyTier\getPage('Dashboard', false);
if (!str_contains($dashboard, 'EasyTier Node Status') || !str_contains($dashboard, 'easytier-cli node')) {
    throw new RuntimeException('Dashboard page did not render expected content.');
}

$logs = \EasyTier\getPage('Logs', false);
if (!str_contains($logs, 'EasyTier Logs') || !str_contains($logs, 'logContent')) {
    throw new RuntimeException('Logs page did not render expected content.');
}

$config = new \EasyTier\Config();
$config->ServerAddress = 'udp://config.example.com:22020/unraid';
$config->NetworkName = 'local-network';
$config->NetworkSecret = 'local-secret';
$config->Listener = '0.0.0.0:11010';
$config->Proxy = '1080';
$config->RpcPort = '15888';
$config->Hostname = 'local-host';
\EasyTier\System::createEasytierParamsFile($config);
$params = file_get_contents('/usr/local/emhttp/plugins/easytier/custom-params.sh');
if (!is_string($params) || !str_contains($params, "'-w' 'udp://config.example.com:22020/unraid'")) {
    throw new RuntimeException('A valid config server address was not added to the startup parameters.');
}
foreach (['--network-name', '--network-secret', '--listeners', '--socks5', '--rpc-portal', '--hostname'] as $option) {
    if (str_contains($params, "'{$option}'")) {
        throw new RuntimeException("Local option {$option} was not ignored for a valid config server.");
    }
}

$config->ServerAddress = 'not a valid address';
\EasyTier\System::createEasytierParamsFile($config);
$fallbackParams = file_get_contents('/usr/local/emhttp/plugins/easytier/custom-params.sh');
if (!is_string($fallbackParams) || !str_contains($fallbackParams, "'--network-name' 'local-network'")) {
    throw new RuntimeException('Local settings were not used when the config server address was invalid.');
}

$error = \EasyTier\includePage(
    '/usr/local/emhttp/plugins/easytier/include/Pages/Error.php',
    ['e' => new RuntimeException('render test')]
);
if (!str_contains($error, 'render test') || !str_contains($error, 'easytier-error.log')) {
    throw new RuntimeException('Error page did not render expected content.');
}

echo "WebUI runtime render tests passed." . PHP_EOL;
