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

enum NotificationType: string
{
    case NORMAL  = 'normal';
    case WARNING = 'warning';
    case ALERT   = 'alert';
}

class System extends \EDACerton\PluginUtils\System
{
    public const RESTART_COMMAND = "/usr/local/emhttp/webGui/scripts/reload_services";
    public const NOTIFY_COMMAND  = "/usr/local/emhttp/webGui/scripts/notify";

    public static function syncHostsFile(array $peers): void
    {
        $entries = [];
        foreach ($peers as $peer) {
            if (isset($peer['hostname']) && isset($peer['virtual_ip'])) {
                $ip = $peer['virtual_ip'];
                $hostname = $peer['hostname'];

                if (
                    filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
                    preg_match('/^[A-Za-z0-9][A-Za-z0-9.-]{0,252}$/', $hostname)
                ) {
                    $entries[$hostname] = $ip;
                }
            }
        }
        self::replaceHostsEntries($entries);
    }

    public static function getPeers(): array
    {
        if (!is_executable('/usr/local/sbin/easytier-cli')) {
            return [];
        }

        // P1 R-05: try JSON first (if upstream supports --json)
        $jsonLines = Utils::runwrap('/usr/local/sbin/easytier-cli peer --json 2>/dev/null', false, false);
        if ($jsonLines !== []) {
            $json = implode("\n", $jsonLines);
            $data = json_decode($json, true);
            if (is_array($data)) {
                // Support both array and object with 'peers' key
                $list = $data['peers'] ?? $data;
                if (is_array($list)) {
                    $peers = [];
                    foreach ($list as $item) {
                        if (is_array($item) && isset($item['virtual_ip'], $item['hostname'])) {
                            $ip = $item['virtual_ip'] ?? $item['ip'] ?? '';
                            $hn = $item['hostname'] ?? '';
                            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && preg_match('/^[A-Za-z0-9][A-Za-z0-9.-]{0,252}$/', $hn)) {
                                $peers[] = ['virtual_ip' => $ip, 'hostname' => $hn];
                            }
                        } elseif (is_array($item) && isset($item['ipv4'], $item['hostname'])) {
                            $ip = $item['ipv4'];
                            $hn = $item['hostname'];
                            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && preg_match('/^[A-Za-z0-9][A-Za-z0-9.-]{0,252}$/', $hn)) {
                                $peers[] = ['virtual_ip' => $ip, 'hostname' => $hn];
                            }
                        }
                    }
                    if ($peers !== []) {
                        return $peers;
                    }
                }
            }
        }

        $lines = Utils::runwrap('/usr/local/sbin/easytier-cli peer', false, false);
        $peers = [];
        foreach ($lines as $line) {
            if (!str_contains($line, '|')) {
                continue;
            }
            $columns = array_map('trim', explode('|', trim($line, " \t|")));
            if (
                count($columns) >= 2 &&
                filter_var($columns[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
                strcasecmp($columns[1], 'hostname') !== 0
            ) {
                // P1 R-05: header auto-detection - skip if header contains non-IP
                if (strcasecmp($columns[0], 'ipv4') === 0 || strcasecmp($columns[0], 'ip') === 0) {
                    continue;
                }
                $peers[] = ['virtual_ip' => $columns[0], 'hostname' => $columns[1]];
            }
        }
        if ($peers === [] && $lines !== []) {
            Utils::logwrap("getPeers: no peers parsed, raw lines: " . implode('; ', array_slice($lines, 0, 3)), true);
        }
        return $peers;
    }

    /**
     * @return array<string>
     */
    public static function isRunning(): bool
    {
        if (!is_executable('/usr/bin/pgrep')) {
            return false;
        }

        return Utils::runwrap(
            '/usr/bin/pgrep --ns $$ --euid root -f "^/usr/local/sbin/easytier-core"',
            false,
            false
        ) !== [];
    }

    /**
     * @return array<string>
     */
    public static function getNodeOutput(): array
    {
        if (!is_executable('/usr/local/sbin/easytier-cli')) {
            return [];
        }

        return Utils::runwrap('/usr/local/sbin/easytier-cli node', false, false);
    }

    public static function checkWebgui(Config $config, string $easytier_ipv4, bool $allowRestart): bool
    {
        // Make certain that the WebGUI is listening on the EasyTier interface
        if ($config->IncludeInterface) {
            $ident_config = parse_ini_file("/boot/config/ident.cfg") ?: array();

            $connection = @fsockopen($easytier_ipv4, $ident_config['PORT']);

            if (is_resource($connection)) {
                Utils::logwrap("WebGUI listening on {$easytier_ipv4}:{$ident_config['PORT']}", false, true);
            } else {
                if ( ! $allowRestart) {
                    Utils::logwrap("WebGUI not listening on {$easytier_ipv4}:{$ident_config['PORT']}, waiting for next check");
                    return true;
                }

                Utils::logwrap("WebGUI not listening on {$easytier_ipv4}:{$ident_config['PORT']}, terminating and restarting");
                Utils::runwrap("/etc/rc.d/rc.nginx term");
                sleep(5);
                Utils::runwrap("/etc/rc.d/rc.nginx start");
            }
        }

        return false;
    }

    public static function restartSystemServices(Config $config): void
    {
        if ($config->IncludeInterface) {
            Utils::runwrap(self::RESTART_COMMAND);
        }
    }

    public static function configureIPForwarding(Config $config): void
    {
        $path = '/etc/sysctl.d/99-easytier.conf';
        if ($config->Enable && $config->IPForward) {
            Utils::logwrap("Enabling IP forwarding");
            $sysctl = "net.ipv4.ip_forward = 1" . PHP_EOL .
                "net.ipv6.conf.all.forwarding = 1" . PHP_EOL;
            file_put_contents($path, $sysctl, LOCK_EX);
            Utils::runwrap("sysctl -p {$path}", true);
        } elseif (file_exists($path)) {
            unlink($path);
        }
    }

    public static function sendNotification(string $event, string $subject, string $message, NotificationType $priority): void
    {
        $command = self::NOTIFY_COMMAND . " -l '/Settings/EasyTier' -e " . escapeshellarg($event) . " -s " . escapeshellarg($subject) . " -d " . escapeshellarg("{$message}") . " -i \"{$priority->value}\" -x 2>/dev/null";
        exec($command);
    }

    public static function setExtraInterface(Config $config): void
    {
        if (file_exists(self::RESTART_COMMAND)) {
            $include_array      = array();
            $exclude_interfaces = "";
            $write_file         = true;
            $network_extra_file = '/boot/config/network-extra.cfg';
            $ifname             = 'easytier0';

            if (file_exists($network_extra_file)) {
                $netExtra = parse_ini_file($network_extra_file);
                if ($netExtra['include_interfaces'] ?? false) {
                    $include_array = explode(' ', $netExtra['include_interfaces']);
                }
                if ($netExtra['exclude_interfaces'] ?? false) {
                    $exclude_interfaces = $netExtra['exclude_interfaces'];
                }
                $write_file = false;
            }

            $in_array = in_array($ifname, $include_array);

            if ($in_array != $config->IncludeInterface) {
                if ($config->IncludeInterface) {
                    $include_array[] = $ifname;
                    Utils::logwrap("{$ifname} added to include_interfaces");
                } else {
                    $include_array = array_diff($include_array, [$ifname]);
                    Utils::logwrap("{$ifname} removed from include_interfaces");
                }
                $write_file = true;
            }

            if ($write_file) {
                $include_interfaces = implode(' ', array_unique($include_array));

                $file = <<<END
                    include_interfaces="{$include_interfaces}"
                    exclude_interfaces="{$exclude_interfaces}"

                    END;

                file_put_contents($network_extra_file, $file);
                Utils::logwrap("Updated network-extra.cfg");
            }
        }
    }

    public static function ensureConfigDir(Config $config): void
    {
        $dir = trim($config->ConfigDir);
        if ($dir === '' || !Config::isValidConfigDir($dir)) {
            $dir = Config::DEFAULT_CONFIG_DIR;
        }
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        } else {
            @chmod($dir, 0700);
        }
        // P0 S-03: secure main config files
        $cfg = '/boot/config/plugins/easytier/easytier.cfg';
        if (is_file($cfg)) {
            @chmod($cfg, 0600);
        }
        if (is_file(Config::CORE_CONFIG_FILE)) {
            @chmod(Config::CORE_CONFIG_FILE, 0600);
        }
        @chmod('/boot/config/plugins/easytier', 0700);
    }

    public static function createEasytierParamsFile(Config $config): void
    {
        self::ensureConfigDir($config);

        $params = ['--dhcp', '--dev-name', 'easytier0'];

        // Always pass --config-dir when valid (default: /boot/config/plugins/easytier).
        // EasyTier will load all *.toml in the directory; explicit -c/-w still take precedence per EasyTier docs.
        $configDir = trim($config->ConfigDir);
        if ($configDir !== '' && Config::isValidConfigDir($configDir)) {
            $params[] = '--config-dir';
            $params[] = $configDir;
        }

        /*
         * A valid config server supplies the EasyTier network configuration.
         * Keep only the plugin-required TUN settings here; local command-line
         * options would otherwise override the configuration delivered by
         * the server.
         */
        if (Config::isValidServerAddress($config->ServerAddress)) {
            $params[] = '-w';
            $params[] = trim($config->ServerAddress);
        } elseif ($config->hasValidServiceSettings()) {
            // Build EasyTier parameters from the local configuration.
            if (!empty($config->NetworkName)) {
                $params[] = '--network-name';
                $params[] = $config->NetworkName;
            }

            if (!empty($config->NetworkSecret)) {
                $params[] = '--network-secret';
                $params[] = $config->NetworkSecret;
            }

            if (!empty($config->Listener)) {
                $listener = str_contains($config->Listener, '://')
                    ? $config->Listener
                    : "{$config->Protocol}://{$config->Listener}";
                $params[] = '--listeners';
                $params[] = $listener;
            }

            if (!empty($config->Proxy)) {
                $params[] = '--socks5';
                $params[] = $config->Proxy;
            }

            if (!empty($config->InstanceId) && $config->InstanceId !== 0) {
                // P1 C-02: implement --instance-id (was defined but unused)
                $params[] = '--instance-id';
                $params[] = (string)$config->InstanceId;
            }

            if (!empty($config->RpcPort)) {
                $params[] = '--rpc-portal';
                $params[] = $config->RpcPort;
            }

            if (!empty($config->Hostname)) {
                $params[] = '--hostname';
                $params[] = $config->Hostname;
            }
        } elseif (is_file(Config::CORE_CONFIG_FILE) && trim((string)file_get_contents(Config::CORE_CONFIG_FILE)) !== '') {
            // A saved EasyTier config file is used only when local settings are incomplete.
            $params[] = '-c';
            $params[] = Config::CORE_CONFIG_FILE;
        }

        // P0 S-02: single-layer handling. Store args one per line without double escaping;
        // rc.easytier reads the file line-by-line and execs without eval.
        $content = implode("\n", $params) . "\n";
        $paramsFile = '/usr/local/emhttp/plugins/easytier/custom-params.sh';
        file_put_contents($paramsFile, $content, LOCK_EX);
        @chmod($paramsFile, 0600);
        // Backward compatibility: also keep legacy variable for older rc.easytier (single outer escaping)
        $legacy = '/boot/config/plugins/easytier/args.txt';
        @file_put_contents($legacy, $content, LOCK_EX);
        @chmod($legacy, 0600);
    }
}
