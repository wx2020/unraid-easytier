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

if (( ! isset($var)) || ( ! isset($display))) {
    echo("Missing required WebGUI variables");
    return;
}

?>

<link type="text/css" rel="stylesheet" href="<?= Utils::auto_v('/webGui/styles/jquery.filetree.css');?>">
<link type="text/css" rel="stylesheet" href="<?= Utils::auto_v('/webGui/styles/jquery.switchbutton.css');?>">
<span class="status vhshift"><input type="checkbox" class="advancedview"></span>
<form method="POST" action="/update.php" target="progressFrame">
<input type="hidden" name="#file" value="easytier/easytier.cfg">
<input type="hidden" name="#cleanup" value="">
<input type="hidden" name="#command" value="/usr/local/emhttp/plugins/easytier/restart.sh">

<table class="unraid tablesorter"><thead><tr><td>System Settings</td></tr></thead></table>

<dl>
    <dt>Enable EasyTier</dt>
    <dd>
        <select name='ENABLE_EASYTIER' size='1' class='narrow'>
            <?= Utils::make_option($config->Enable, '1', 'Yes');?>
            <?= Utils::make_option( ! $config->Enable, '0', 'No');?>
        </select>
    </dd>
</dl>
<blockquote class='inline_help'>Enable or disable the EasyTier service.</blockquote>

<dl>
    <dt>Include Interface in Unraid</dt>
    <dd>
        <select name='INCLUDE_INTERFACE' size='1' class='narrow'>
            <?= Utils::make_option($config->IncludeInterface, '1', 'Yes');?>
            <?= Utils::make_option( ! $config->IncludeInterface, '0', 'No');?>
        </select>
    </dd>
</dl>
<blockquote class='inline_help'>Include the EasyTier interface (easytier0) in Unraid's network settings. This allows the WebGUI to be accessible via EasyTier.</blockquote>

<dl>
    <dt>Enable IP Forwarding</dt>
    <dd>
        <select name='SYSCTL_IP_FORWARD' size='1' class='narrow'>
            <?= Utils::make_option($config->IPForward, '1', 'Yes');?>
            <?= Utils::make_option( ! $config->IPForward, '0', 'No');?>
        </select>
    </dd>
</dl>
<blockquote class='inline_help'>Enable IP forwarding. This is required for routing traffic through EasyTier.</blockquote>

<div class="advanced">
    <dl>
        <dt>Add Peers to Hosts File</dt>
        <dd>
            <select name='ADD_PEERS_TO_HOSTS' size='1' class='narrow'>
                <?= Utils::make_option($config->AddPeersToHosts, '1', 'Yes');?>
                <?= Utils::make_option( ! $config->AddPeersToHosts, '0', 'No');?>
            </select>
        </dd>
    </dl>
    <blockquote class='inline_help'>Automatically add EasyTier peers to /etc/hosts file for name resolution.</blockquote>
</div>

<table class="unraid tablesorter"><thead><tr><td>Network Configuration</td></tr></thead></table>

<dl>
    <dt>Network Name</dt>
    <dd>
        <input type="text" name="NETWORK_NAME" value="<?= htmlspecialchars($config->NetworkName) ?>" placeholder="my-network">
    </dd>
</dl>
<blockquote class='inline_help'>The name of the EasyTier network to join. Leave empty to create a new network or join the default network.</blockquote>

<dl>
    <dt>Network Secret</dt>
    <dd>
        <input type="password" name="NETWORK_SECRET" value="<?= htmlspecialchars($config->NetworkSecret) ?>" placeholder="optional">
    </dd>
</dl>
<blockquote class='inline_help'>The secret key for the EasyTier network. Required for private networks.</blockquote>

<div class="advanced">
    <dl>
        <dt>Protocol</dt>
        <dd>
            <select name='PROTOCOL' size='1' class='narrow'>
                <?= Utils::make_option($config->Protocol === 'udp', 'udp', 'UDP');?>
                <?= Utils::make_option($config->Protocol === 'tcp', 'tcp', 'TCP');?>
                <?= Utils::make_option($config->Protocol === 'ws', 'ws', 'WebSocket');?>
                <?= Utils::make_option($config->Protocol === 'wss', 'wss', 'Secure WebSocket');?>
            </select>
        </dd>
    </dl>
    <blockquote class='inline_help'>The protocol to use for EasyTier connections. UDP is recommended for best performance.</blockquote>

    <dl>
        <dt>Listener Address</dt>
        <dd>
            <input type="text" name="LISTENER" value="<?= htmlspecialchars($config->Listener) ?>" placeholder="0.0.0.0:11010">
        </dd>
    </dl>
    <blockquote class='inline_help'>The address and port for EasyTier to listen on. Format: IP:PORT</blockquote>

    <dl>
        <dt>Proxy Address</dt>
        <dd>
            <input type="text" name="PROXY" value="<?= htmlspecialchars($config->Proxy) ?>" placeholder="optional">
        </dd>
    </dl>
    <blockquote class='inline_help'>SOCKS5 proxy address (optional). Format: IP:PORT</blockquote>

    <dl>
        <dt>Instance ID</dt>
        <dd>
            <input type="number" name="INSTANCE_ID" value="<?= htmlspecialchars($config->InstanceId) ?>" placeholder="0">
        </dd>
    </dl>
    <blockquote class='inline_help'>Unique instance ID. Use 0 for auto-assignment (based on hostname).</blockquote>

    <dl>
        <dt>RPC Port</dt>
        <dd>
            <input type="text" name="RPC_PORT" value="<?= htmlspecialchars($config->RpcPort) ?>" placeholder="15888">
        </dd>
    </dl>
    <blockquote class='inline_help'>Port for the management RPC interface.</blockquote>

    <dl>
        <dt>Hostname</dt>
        <dd>
            <input type="text" name="HOSTNAME" value="<?= htmlspecialchars($config->Hostname) ?>" placeholder="auto">
        </dd>
    </dl>
    <blockquote class='inline_help'>Hostname for this EasyTier instance. Leave empty to use system hostname.</blockquote>
</div>

<table class="unraid tablesorter"><thead><tr><td>Service Control</td></tr></thead></table>

<dl>
    <dt></dt>
    <dd>
        <button type='button' onclick='done()'>Apply Settings</button>
        <button type='button' onclick='restartService()'>Restart Service</button>
    </dd>
</dl>

</form>

<script>
function done() {
    $("#progressFrame").attr('src', "/plugins/easytier/include/save_settings.php");
}

function restartService() {
    if (confirm("Are you sure you want to restart the EasyTier service?")) {
        $("#progressFrame").attr('src', "/plugins/easytier/include/restart_service.php");
    }
}
</script>
