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

class Config
{
    public const CORE_CONFIG_FILE = '/boot/config/plugins/easytier/easytier.toml';
    public const DEFAULT_CONFIG_DIR = '/boot/config/plugins/easytier';

    public bool $IncludeInterface;
    public bool $Enable;
    public bool $IPForward;
    public bool $AddPeersToHosts;

    // EasyTier specific settings
    public string $NetworkName;
    public string $NetworkSecret;
    public string $ServerAddress;  // Config server address to connect to
    public string $Protocol;       // 'udp', 'tcp', 'ws', 'wss'
    public string $Listener;       // Listener address (e.g., '0.0.0.0:11010')
    public string $Proxy;          // SOCKS5 proxy address
    public int $InstanceId;        // Instance ID (default: hostname based)
    public string $RpcPort;        // RPC port for management
    public string $Hostname;       // Hostname for this instance
    public string $ConfigDir;      // Directory for --config-dir (e.g., '/boot/config/plugins/easytier')

    public function __construct()
    {
        $config_file = '/boot/config/plugins/easytier/easytier.cfg';

        // Load configuration file
        if (file_exists($config_file)) {
            $saved_config = parse_ini_file($config_file) ?: array();
        } else {
            $saved_config = array();
        }

        $this->IncludeInterface = self::parseBool($saved_config["INCLUDE_INTERFACE"] ?? "1");
        $this->Enable           = self::parseBool($saved_config["ENABLE_EASYTIER"] ?? "1");
        $this->IPForward        = self::parseBool($saved_config["SYSCTL_IP_FORWARD"] ?? "1");
        $this->AddPeersToHosts  = self::parseBool($saved_config["ADD_PEERS_TO_HOSTS"] ?? "1");

        // EasyTier specific settings
        $this->NetworkName    = $saved_config["NETWORK_NAME"] ?? "";
        $this->NetworkSecret  = $saved_config["NETWORK_SECRET"] ?? "";
        $this->ServerAddress  = $saved_config["SERVER_ADDRESS"] ?? "";
        $this->Protocol       = $saved_config["PROTOCOL"] ?? "udp";
        $this->Listener       = $saved_config["LISTENER"] ?? "0.0.0.0:11010";
        // P1 C-02: PROXY is actually SOCKS5 port; support SOCKS5_PORT alias for rename, fallback to PROXY
        $this->Proxy          = $saved_config["SOCKS5_PORT"] ?? $saved_config["PROXY"] ?? "";
        $this->InstanceId     = intval($saved_config["INSTANCE_ID"] ?? "0");
        $this->RpcPort        = $saved_config["RPC_PORT"] ?? "15888";
        $this->Hostname       = $saved_config["HOSTNAME"] ?? (gethostname() ?: 'unraid');
        $rawConfigDir         = $saved_config["CONFIG_DIR"] ?? self::DEFAULT_CONFIG_DIR;
        // Backward compatibility: user requested '/boot/config/plugin/easytier' (singular) -> normalize to plural
        if (trim((string)$rawConfigDir) === '/boot/config/plugin/easytier') {
            $rawConfigDir = self::DEFAULT_CONFIG_DIR;
        }
        $this->ConfigDir      = trim((string)$rawConfigDir) !== '' ? trim((string)$rawConfigDir) : self::DEFAULT_CONFIG_DIR;
    }

    private static function parseBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function isValidServerAddress(string $address): bool
    {
        $address = trim($address);
        if ($address === '') {
            return false;
        }

        // EasyTier also accepts a username and uses the official server.
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_.@-]{0,127}$/', $address) === 1) {
            return true;
        }

        if (preg_match(
            '~^(udp|tcp|ws|wss)://(?:\[[0-9A-Fa-f:.]+\]|[A-Za-z0-9][A-Za-z0-9.-]*):([0-9]{1,5})/[^/?#\s]+$~i',
            $address,
            $matches
        ) !== 1) {
            return false;
        }

        return self::isValidPort($matches[2]);
    }

    public function hasValidServiceSettings(): bool
    {
        if (trim($this->NetworkName) === '') {
            return false;
        }

        if (!in_array(strtolower($this->Protocol), ['udp', 'tcp', 'ws', 'wss'], true)) {
            return false;
        }

        if ($this->Listener !== '' && !self::isValidListener($this->Listener)) {
            return false;
        }

        if ($this->RpcPort !== '' && !self::isValidPort($this->RpcPort)) {
            return false;
        }

        if ($this->Proxy !== '' && !self::isValidPort($this->Proxy)) {
            return false;
        }

        return $this->Hostname === '' || preg_match('/\s/', $this->Hostname) !== 1;
    }

    private static function isValidListener(string $listener): bool
    {
        $listener = trim($listener);
        if (str_contains($listener, '://')) {
            return preg_match(
                '~^(tcp|udp|ring|wg|ws|wss|quic|faketcp)://(?:\[[0-9A-Fa-f:.]+\]|[A-Za-z0-9][A-Za-z0-9.-]*):([0-9]{1,5})$~i',
                $listener,
                $matches
            ) === 1 && self::isValidPort($matches[2]);
        }

        return preg_match(
            '~^(?:\[[0-9A-Fa-f:.]+\]|[A-Za-z0-9][A-Za-z0-9.-]*):([0-9]{1,5})$~',
            $listener,
            $matches
        ) === 1 && self::isValidPort($matches[1]);
    }

    private static function isValidPort(mixed $port): bool
    {
        return is_int($port) || is_string($port)
            ? preg_match('/^[0-9]{1,5}$/', (string)$port) === 1 && (int)$port >= 1 && (int)$port <= 65535
            : false;
    }

    public static function isValidConfigDir(string $path): bool
    {
        $path = trim($path);
        if ($path === '') {
            return false;
        }
        // Must be absolute path, no null bytes, no traversal
        if ($path[0] !== '/' || str_contains($path, "\0") || str_contains($path, '..')) {
            return false;
        }
        // Allow only safe characters: alphanum, / . _ -
        if (preg_match('~^/[A-Za-z0-9/_\-.]+$~', $path) !== 1) {
            return false;
        }
        // No double slashes and no trailing slash (except root)
        if (str_contains($path, '//') || (strlen($path) > 1 && str_ends_with($path, '/'))) {
            return false;
        }
        return true;
    }
}
