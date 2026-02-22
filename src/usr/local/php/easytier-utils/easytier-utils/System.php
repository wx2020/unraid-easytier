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

    public static function addToHostFile(array $peers): void
    {
        foreach ($peers as $peer) {
            if (isset($peer['hostname']) && isset($peer['virtual_ip'])) {
                $ip = $peer['virtual_ip'];
                $hostname = $peer['hostname'];

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    Utils::logwrap("Adding peer {$hostname} with IP {$ip} to hosts file");
                    self::updateHostsFile($hostname, $ip);
                }
            }
        }
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

    public static function enableIPForwarding(Config $config): void
    {
        if ($config->Enable && $config->IPForward) {
            Utils::logwrap("Enabling IP forwarding");
            $sysctl = "net.ipv4.ip_forward = 1" . PHP_EOL . "net.ipv6.conf.all.forwarding = 1";
            file_put_contents('/etc/sysctl.d/99-easytier.conf', $sysctl);
            Utils::runwrap("sysctl -p /etc/sysctl.d/99-easytier.conf", true);
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

    public static function createEasytierParamsFile(Config $config): void
    {
        $custom_params = "";

        // Build EasyTier specific parameters
        if (!empty($config->NetworkName)) {
            $custom_params .= "--network-name \"{$config->NetworkName}\" ";
        }

        if (!empty($config->NetworkSecret)) {
            $custom_params .= "--network-secret \"{$config->NetworkSecret}\" ";
        }

        if (!empty($config->Protocol)) {
            $custom_params .= "--protocol {$config->Protocol} ";
        }

        if (!empty($config->Listener)) {
            $custom_params .= "--listener \"{$config->Listener}\" ";
        }

        if (!empty($config->Proxy)) {
            $custom_params .= "--proxy \"{$config->Proxy}\" ";
        }

        if ($config->InstanceId > 0) {
            $custom_params .= "--instance-id {$config->InstanceId} ";
        }

        if (!empty($config->RpcPort)) {
            $custom_params .= "--rpc-port {$config->RpcPort} ";
        }

        if (!empty($config->Hostname)) {
            $custom_params .= "--hostname \"{$config->Hostname}\" ";
        }

        file_put_contents('/usr/local/emhttp/plugins/easytier/custom-params.sh', 'EASYTIER_CUSTOM_PARAMS="' . $custom_params . '"');
    }
}
