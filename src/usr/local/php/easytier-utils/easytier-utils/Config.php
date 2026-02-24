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
    public bool $IncludeInterface;
    public bool $Enable;
    public bool $IPForward;
    public bool $AddPeersToHosts;

    // EasyTier specific settings
    public string $NetworkName;
    public string $NetworkSecret;
    public string $ServerAddress;  // Public server address to connect to
    public string $Protocol;       // 'udp', 'tcp', 'ws', 'wss'
    public string $Listener;       // Listener address (e.g., '0.0.0.0:11010')
    public string $Proxy;          // SOCKS5 proxy address
    public int $InstanceId;        // Instance ID (default: hostname based)
    public string $RpcPort;        // RPC port for management
    public string $Hostname;       // Hostname for this instance

    public function __construct()
    {
        $config_file = '/boot/config/plugins/easytier/easytier.cfg';

        // Load configuration file
        if (file_exists($config_file)) {
            $saved_config = parse_ini_file($config_file) ?: array();
        } else {
            $saved_config = array();
        }

        $this->IncludeInterface = boolval($saved_config["INCLUDE_INTERFACE"] ?? "1");
        $this->Enable           = boolval($saved_config["ENABLE_EASYTIER"] ?? "1");
        $this->IPForward        = boolval($saved_config["SYSCTL_IP_FORWARD"] ?? "1");
        $this->AddPeersToHosts  = boolval($saved_config["ADD_PEERS_TO_HOSTS"] ?? "1");

        // EasyTier specific settings
        $this->NetworkName    = $saved_config["NETWORK_NAME"] ?? "";
        $this->NetworkSecret  = $saved_config["NETWORK_SECRET"] ?? "";
        $this->ServerAddress  = $saved_config["SERVER_ADDRESS"] ?? "";
        $this->Protocol       = $saved_config["PROTOCOL"] ?? "udp";
        $this->Listener       = $saved_config["LISTENER"] ?? "0.0.0.0:11010";
        $this->Proxy          = $saved_config["PROXY"] ?? "";
        $this->InstanceId     = intval($saved_config["INSTANCE_ID"] ?? "0");
        $this->RpcPort        = $saved_config["RPC_PORT"] ?? "15888";
        $this->Hostname       = $saved_config["HOSTNAME"] ?? gethostname();
    }
}
