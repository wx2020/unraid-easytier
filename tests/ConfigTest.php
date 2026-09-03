<?php

declare(strict_types=1);

// P1 T-01/T-02: Config validation and params priority tests (pure PHP asserts, no PHPUnit required)
$bootstrapCandidates = [
    '/usr/local/php/easytier-utils/bootstrap.php',
    __DIR__ . '/../src/usr/local/php/easytier-utils/bootstrap.php',
];
$loaded = false;
foreach ($bootstrapCandidates as $candidate) {
    if (is_file($candidate)) {
        require_once $candidate;
        $loaded = true;
        break;
    }
}
if (!$loaded) {
    throw new RuntimeException('bootstrap.php not found');
}

use EasyTier\Config;
use EasyTier\System;

function assertTrue(bool $cond, string $msg): void
{
    if (!$cond) {
        throw new RuntimeException("assertTrue failed: $msg");
    }
}

function assertFalse(bool $cond, string $msg): void
{
    if ($cond) {
        throw new RuntimeException("assertFalse failed: $msg");
    }
}

// T-01: isValidServerAddress
assertTrue(Config::isValidServerAddress('user123'), 'username valid');
assertTrue(Config::isValidServerAddress('udp://config.example.com:22020/unraid'), 'udp url valid');
assertTrue(Config::isValidServerAddress('tcp://[::1]:22020/user'), 'ipv6 url valid');
assertTrue(Config::isValidServerAddress('wss://example.com:443/u'), 'wss valid');
assertFalse(Config::isValidServerAddress(''), 'empty invalid');
assertFalse(Config::isValidServerAddress('not a valid address'), 'space invalid');
assertFalse(Config::isValidServerAddress('udp://1.1.1.1:99999/u'), 'port >65535 invalid');
assertFalse(Config::isValidServerAddress('http://1.1.1.1:22020/u'), 'http scheme invalid');
assertFalse(Config::isValidServerAddress('udp://host:22020/'), 'missing username invalid');
assertFalse(Config::isValidServerAddress('udp://host:22020/ user'), 'space in username invalid');

// isValidConfigDir
assertTrue(Config::isValidConfigDir('/boot/config/plugins/easytier'), 'default dir valid');
assertTrue(Config::isValidConfigDir('/boot/config/plugin/easytier'), 'singular valid per char set');
assertFalse(Config::isValidConfigDir(''), 'empty dir invalid');
assertFalse(Config::isValidConfigDir('relative/path'), 'relative invalid');
assertFalse(Config::isValidConfigDir('/boot/config/../etc'), 'traversal invalid');
assertFalse(Config::isValidConfigDir('/boot//config'), 'double slash invalid');
assertFalse(Config::isValidConfigDir('/boot/config/plugins/easytier/'), 'trailing slash invalid');

// hasValidServiceSettings and isValidListener via Config object
$config = new Config();
$config->NetworkName = 'test-net';
$config->Protocol = 'udp';
$config->Listener = '0.0.0.0:11010';
$config->RpcPort = '15888';
$config->Proxy = '1080';
$config->Hostname = 'unraid';
assertTrue($config->hasValidServiceSettings(), 'valid service settings');

$config->Listener = 'tcp://0.0.0.0:11010';
assertTrue($config->hasValidServiceSettings(), 'listener with scheme valid');
$config->Listener = '999.999.999.999:99999';
assertFalse($config->hasValidServiceSettings(), 'invalid listener port');
$config->Listener = '0.0.0.0:11010';
$config->Proxy = '99999';
assertFalse($config->hasValidServiceSettings(), 'invalid proxy port');
$config->Proxy = '1080';
$config->Hostname = 'bad host';
assertFalse($config->hasValidServiceSettings(), 'hostname with space invalid');
$config->Hostname = 'good-host';
assertTrue($config->hasValidServiceSettings(), 'hostname valid');

// isValidPort via reflection
$refPort = new ReflectionMethod(Config::class, 'isValidPort');
$refPort->setAccessible(true);
assertTrue($refPort->invoke(null, '1'), 'port 1 valid');
assertTrue($refPort->invoke(null, '65535'), 'port 65535 valid');
assertFalse($refPort->invoke(null, '0'), 'port 0 invalid');
assertFalse($refPort->invoke(null, '65536'), 'port 65536 invalid');
assertFalse($refPort->invoke(null, 'abc'), 'port abc invalid');

// isValidListener via reflection
$refListener = new ReflectionMethod(Config::class, 'isValidListener');
$refListener->setAccessible(true);
assertTrue($refListener->invoke(null, '0.0.0.0:11010'), 'listener ip:port valid');
assertTrue($refListener->invoke(null, 'tcp://0.0.0.0:11010'), 'listener scheme valid');
assertFalse($refListener->invoke(null, '0.0.0.0:99999'), 'listener bad port');
assertFalse($refListener->invoke(null, '://:11010'), 'listener missing host');

// T-02/C-02: createEasytierParamsFile priority and instance-id
$tmpDir = sys_get_temp_dir() . '/easytier-test-' . getmypid();
@mkdir($tmpDir, 0755, true);
// Mock: use real Config but override files
// Ensure core config file exists for fallback test
$coreFile = Config::CORE_CONFIG_FILE;
$backupCore = is_file($coreFile) ? file_get_contents($coreFile) : null;
@mkdir(dirname($coreFile), 0755, true);
file_put_contents($coreFile, "test = 1\n");

$config2 = new Config();
$config2->ServerAddress = 'udp://config.example.com:22020/unraid';
$config2->NetworkName = 'local-network';
$config2->NetworkSecret = 'local-secret';
$config2->Listener = '0.0.0.0:11010';
$config2->Proxy = '1080';
$config2->RpcPort = '15888';
$config2->Hostname = 'local-host';
$config2->InstanceId = 0;
$config2->ConfigDir = $tmpDir;
System::createEasytierParamsFile($config2);
$params = (string)file_get_contents('/usr/local/emhttp/plugins/easytier/custom-params.sh');
assertTrue(str_contains($params, '-w'), 'config server -w present');
assertTrue(str_contains($params, 'udp://config.example.com:22020/unraid'), '-w value present');
assertTrue(str_contains($params, '--config-dir'), '--config-dir present');
foreach (['--network-name', '--network-secret', '--listeners', '--socks5', '--rpc-portal', '--hostname', '--instance-id'] as $opt) {
    assertFalse(str_contains($params, $opt), "local $opt ignored for valid -w");
}

// Fallback to local settings when server invalid, with instance-id
$config2->ServerAddress = 'not valid';
$config2->InstanceId = 5;
System::createEasytierParamsFile($config2);
$fallback = (string)file_get_contents('/usr/local/emhttp/plugins/easytier/custom-params.sh');
assertTrue(str_contains($fallback, '--network-name'), 'fallback has network-name');
assertTrue(str_contains($fallback, '--instance-id'), 'fallback has instance-id');
assertTrue(str_contains($fallback, '5'), 'instance-id value 5');
assertTrue(str_contains($fallback, '--config-dir'), 'fallback has config-dir');

// Fallback to -c when service settings invalid and server invalid
$config2->NetworkName = '';
$config2->InstanceId = 0;
System::createEasytierParamsFile($config2);
$withC = (string)file_get_contents('/usr/local/emhttp/plugins/easytier/custom-params.sh');
assertTrue(str_contains($withC, '-c'), 'fallback to -c');
assertTrue(str_contains($withC, Config::CORE_CONFIG_FILE), '-c value');

// Empty service + no core file -> no -c, only base + config-dir
@unlink($coreFile);
$config2->ServerAddress = '';
$config2->NetworkName = '';
System::createEasytierParamsFile($config2);
$empty = (string)file_get_contents('/usr/local/emhttp/plugins/easytier/custom-params.sh');
assertFalse(str_contains($empty, '-c'), 'no -c when core missing');
assertTrue(str_contains($empty, '--config-dir'), 'empty still has config-dir');

// Restore core file
if ($backupCore !== null) {
    file_put_contents($coreFile, $backupCore);
} else {
    @unlink($coreFile);
}
@rmdir($tmpDir);

// Test PROXY alias: SOCKS5_PORT vs PROXY
// Simulate cfg with SOCKS5_PORT
$cfgPath = '/boot/config/plugins/easytier/easytier.cfg';
$backupCfg = is_file($cfgPath) ? file_get_contents($cfgPath) : null;
@mkdir(dirname($cfgPath), 0755, true);
file_put_contents($cfgPath, "SOCKS5_PORT=\"1081\"\nPROXY=\"1080\"\n");
$cfgTest = new Config();
assertTrue($cfgTest->Proxy === '1081', 'SOCKS5_PORT alias preferred');
@unlink($cfgPath);
file_put_contents($cfgPath, "PROXY=\"1080\"\n");
$cfgTest2 = new Config();
assertTrue($cfgTest2->Proxy === '1080', 'PROXY fallback');
if ($backupCfg !== null) {
    file_put_contents($cfgPath, $backupCfg);
} else {
    @unlink($cfgPath);
}

echo "Config tests passed.\n";
