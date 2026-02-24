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

// Define config files and their paths
$config_files = [
    'main' => [
        'name' => 'Main Config',
        'path' => '/boot/config/plugins/easytier/easytier.cfg',
        'description' => 'Main EasyTier configuration file'
    ],
    'network' => [
        'name' => 'Network Config',
        'path' => '/boot/config/plugins/easytier/network.cfg',
        'description' => 'Network-specific configuration'
    ],
    'advanced' => [
        'name' => 'Advanced Config',
        'path' => '/boot/config/plugins/easytier/advanced.cfg',
        'description' => 'Advanced tuning parameters'
    ]
];

// Get current tab (default to 'main')
$current_tab = $_GET['tab'] ?? 'main';
if (!isset($config_files[$current_tab])) {
    $current_tab = 'main';
}

// Read current config file content
$config_file_path = $config_files[$current_tab]['path'];
$config_content = file_exists($config_file_path) ? file_get_contents($config_file_path) : '';

?>

<link type="text/css" rel="stylesheet" href="<?= Utils::auto_v('/webGui/styles/jquery.filetree.css');?>">
<link type="text/css" rel="stylesheet" href="<?= Utils::auto_v('/webGui/styles/jquery.switchbutton.css');?>">
<link type="text/css" rel="stylesheet" href="/plugins/easytier/styles/settings.css">

<!-- Server Configuration Section -->
<span class="status vhshift"><input type="checkbox" class="advancedview"></span>
<form method="POST" action="/update.php" target="progressFrame" id="serverForm">
<input type="hidden" name="#file" value="easytier/easytier.cfg">
<input type="hidden" name="#cleanup" value="">
<input type="hidden" name="#command" value="/usr/local/emhttp/plugins/easytier/restart.sh">

<table class="unraid tablesorter"><thead><tr><td>Server Configuration</td></tr></thead></table>

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
    <dt>Network Name</dt>
    <dd>
        <input type="text" name="NETWORK_NAME" value="<?= htmlspecialchars($config->NetworkName) ?>" placeholder="my-network">
    </dd>
</dl>
<blockquote class='inline_help'>The name of the EasyTier network to join.</blockquote>

<dl>
    <dt>Network Secret</dt>
    <dd>
        <input type="password" name="NETWORK_SECRET" value="<?= htmlspecialchars($config->NetworkSecret) ?>" placeholder="optional">
    </dd>
</dl>
<blockquote class='inline_help'>The secret key for the EasyTier network (optional).</blockquote>

<dl>
    <dt>Server Address</dt>
    <dd>
        <input type="text" name="SERVER_ADDRESS" value="<?= htmlspecialchars($config->ServerAddress ?? '') ?>" placeholder="udp://easytier.example.com:11010">
    </dd>
</dl>
<blockquote class='inline_help'>Public EasyTier server address to connect to. Format: protocol://host:port</blockquote>

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
    <blockquote class='inline_help'>The protocol to use for EasyTier connections.</blockquote>

    <dl>
        <dt>Listener Address</dt>
        <dd>
            <input type="text" name="LISTENER" value="<?= htmlspecialchars($config->Listener) ?>" placeholder="0.0.0.0:11010">
        </dd>
    </dl>
    <blockquote class='inline_help'>The address and port for EasyTier to listen on. Format: IP:PORT</blockquote>

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
    <blockquote class='inline_help'>Hostname for this EasyTier instance (optional).</blockquote>
</div>

<dl>
    <dt></dt>
    <dd>
        <button type='button' onclick='applyServerSettings()'>Apply Settings</button>
        <button type='button' onclick='restartService()'>Restart Service</button>
    </dd>
</dl>

</form>

<!-- Configuration Files Tabs Section -->
<br>
<table class="unraid tablesorter"><thead><tr><td>Configuration Files</td></tr></thead></table>

<!-- Tab Navigation -->
<div class="config-tabs">
    <?php foreach ($config_files as $tab_id => $tab_info): ?>
        <button class="tab-button <?= $current_tab === $tab_id ? 'active' : '' ?>"
                onclick="switchTab('<?= $tab_id ?>')">
            <?= htmlspecialchars($tab_info['name']) ?>
        </button>
    <?php endforeach; ?>
</div>

<!-- Tab Content -->
<div class="tab-content">
    <div class="tab-description">
        <strong><?= htmlspecialchars($config_files[$current_tab]['name']) ?></strong> -
        <?= htmlspecialchars($config_files[$current_tab]['description']) ?>
        <br>
        <small>File: <?= htmlspecialchars($config_files[$current_tab]['path']) ?></small>
    </div>

    <form method="POST" action="/plugins/easytier/include/save_config_file.php" id="configFileForm">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($current_tab) ?>">
        <input type="hidden" name="file_path" value="<?= htmlspecialchars($config_files[$current_tab]['path']) ?>">

        <textarea name="config_content" id="configEditor" class="config-editor"
                  placeholder="# Configuration file will be created when you save"
                  spellcheck="false"><?= htmlspecialchars($config_content) ?></textarea>

        <div class="editor-actions">
            <button type='button' onclick='saveConfigFile()'>Save Configuration</button>
            <button type='button' onclick='resetConfigFile()'>Reset</button>
            <button type='button' onclick='downloadConfigFile()'>Download</button>
        </div>
    </form>
</div>

<script>
function switchTab(tabId) {
    const url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    window.location.href = url.toString();
}

function applyServerSettings() {
    $("#progressFrame").attr('src', "/plugins/easytier/include/save_settings.php");
}

function restartService() {
    if (confirm("Are you sure you want to restart the EasyTier service?")) {
        $("#progressFrame").attr('src', "/plugins/easytier/include/restart_service.php");
    }
}

function saveConfigFile() {
    const form = document.getElementById('configFileForm');
    const formData = new FormData(form);

    fetch('/plugins/easytier/include/save_config_file.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Configuration file saved successfully!');
        } else {
            alert('Error saving configuration: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error: ' + error);
    });
}

function resetConfigFile() {
    if (confirm('Are you sure you want to reset to the last saved version?')) {
        location.reload();
    }
}

function downloadConfigFile() {
    const filePath = '<?= htmlspecialchars($config_files[$current_tab]['path']) ?>';
    const fileName = filePath.split('/').pop();
    const content = document.getElementById('configEditor').value;

    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = fileName;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Tab key support in textarea
document.getElementById('configEditor').addEventListener('keydown', function(e) {
    if (e.key === 'Tab') {
        e.preventDefault();
        const start = this.selectionStart;
        const end = this.selectionEnd;

        // Insert 4 spaces
        this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);

        // Move cursor
        this.selectionStart = this.selectionEnd = start + 4;
    }
});
</script>
