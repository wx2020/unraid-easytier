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

// Define config files and their paths
$config_files = [
    'main' => [
        'name' => translate('EasyTier Config File'),
        'path' => Config::CORE_CONFIG_FILE,
        'description' => translate('Configuration file used by EasyTier core')
    ],
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
<?php if (isset($GLOBALS['var']['csrf_token'])): ?>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($GLOBALS['var']['csrf_token']) ?>">
<?php endif; ?>

<table class="unraid tablesorter"><thead><tr><td><?= translate('Server Configuration') ?></td></tr></thead></table>

<dl>
    <dt><?= translate('Enable EasyTier') ?></dt>
    <dd>
        <select name='ENABLE_EASYTIER' size='1' class='narrow'>
            <?= Utils::make_option($config->Enable, '1', translate('Yes'));?>
            <?= Utils::make_option( ! $config->Enable, '0', translate('No'));?>
        </select>
    </dd>
</dl>
<blockquote class='inline_help'><?= translate('Enable or disable the EasyTier service.') ?></blockquote>

<dl>
    <dt><?= translate('Include Interface in Unraid') ?></dt>
    <dd><select name="INCLUDE_INTERFACE" class="narrow">
        <?= Utils::make_option($config->IncludeInterface, '1', translate('Yes')) ?>
        <?= Utils::make_option(!$config->IncludeInterface, '0', translate('No')) ?>
    </select></dd>
</dl>

<dl>
    <dt><?= translate('Enable IP Forwarding') ?></dt>
    <dd><select name="SYSCTL_IP_FORWARD" class="narrow">
        <?= Utils::make_option($config->IPForward, '1', translate('Yes')) ?>
        <?= Utils::make_option(!$config->IPForward, '0', translate('No')) ?>
    </select></dd>
</dl>

<dl>
    <dt><?= translate('Add Peers to Hosts') ?></dt>
    <dd><select name="ADD_PEERS_TO_HOSTS" class="narrow">
        <?= Utils::make_option($config->AddPeersToHosts, '1', translate('Yes')) ?>
        <?= Utils::make_option(!$config->AddPeersToHosts, '0', translate('No')) ?>
    </select></dd>
</dl>

<table class="unraid tablesorter"><thead><tr><td><?= translate('Configuration Server') ?></td></tr></thead></table>

<dl>
    <dt><?= translate('Config Server Address') ?></dt>
    <dd>
        <input type="text" name="SERVER_ADDRESS" value="<?= htmlspecialchars($config->ServerAddress ?? '') ?>" placeholder="udp://easytier.example.com:22020/username">
    </dd>
</dl>
<blockquote class='inline_help'>
    <?= translate('EasyTier configuration server passed as') ?> <code>-w</code>.
    <?= translate('Format: protocol://host:port/username') ?>
</blockquote>
<blockquote class='inline_help'><?= translate('A valid Config Server Address overrides local EasyTier settings.') ?></blockquote>

<table class="unraid tablesorter"><thead><tr><td><?= translate('Local EasyTier Configuration') ?></td></tr></thead></table>

<dl>
    <dt><?= translate('Network Name') ?></dt>
    <dd>
        <input type="text" name="NETWORK_NAME" value="<?= htmlspecialchars($config->NetworkName) ?>" placeholder="my-network">
    </dd>
</dl>
<blockquote class='inline_help'><?= translate('The name of the EasyTier network to join.') ?></blockquote>

<dl>
    <dt><?= translate('Network Secret') ?></dt>
    <dd>
        <input type="password" name="NETWORK_SECRET" value="<?= htmlspecialchars($config->NetworkSecret) ?>" placeholder="<?= htmlspecialchars(translate('optional')) ?>">
    </dd>
</dl>
<blockquote class='inline_help'><?= translate('The secret key for the EasyTier network (optional).') ?></blockquote>

<div class="advanced">
    <dl>
        <dt><?= translate('Protocol') ?></dt>
        <dd>
            <select name='PROTOCOL' size='1' class='narrow'>
                <?= Utils::make_option($config->Protocol === 'udp', 'udp', translate('UDP'));?>
                <?= Utils::make_option($config->Protocol === 'tcp', 'tcp', translate('TCP'));?>
                <?= Utils::make_option($config->Protocol === 'ws', 'ws', translate('WebSocket'));?>
                <?= Utils::make_option($config->Protocol === 'wss', 'wss', translate('Secure WebSocket'));?>
            </select>
        </dd>
    </dl>
    <blockquote class='inline_help'><?= translate('The protocol to use for EasyTier connections.') ?></blockquote>

    <dl>
        <dt><?= translate('Listener Address') ?></dt>
        <dd>
            <input type="text" name="LISTENER" value="<?= htmlspecialchars($config->Listener) ?>" placeholder="0.0.0.0:11010">
        </dd>
    </dl>
    <blockquote class='inline_help'>
        <?= translate('The address and port for EasyTier to listen on.') ?>
        <?= translate('Format: IP:PORT') ?>
    </blockquote>

    <dl>
        <dt><?= translate('RPC Port') ?></dt>
        <dd>
            <input type="text" name="RPC_PORT" value="<?= htmlspecialchars($config->RpcPort) ?>" placeholder="15888">
        </dd>
    </dl>
    <blockquote class='inline_help'><?= translate('Port for the management RPC interface.') ?></blockquote>

    <dl>
        <dt><?= translate('Hostname') ?></dt>
        <dd>
            <input type="text" name="HOSTNAME" value="<?= htmlspecialchars($config->Hostname) ?>" placeholder="auto">
        </dd>
    </dl>
    <blockquote class='inline_help'><?= translate('Hostname for this EasyTier instance (optional).') ?></blockquote>

    <dl>
        <dt><?= translate('SOCKS5 Listen Port') ?></dt>
        <dd><input type="number" min="1" max="65535" name="PROXY"
                   value="<?= htmlspecialchars($config->Proxy) ?>" placeholder="1080"></dd>
    </dl>
</div>

<dl>
    <dt></dt>
    <dd>
        <button type='button' onclick='applyServerSettings()'><?= translate('Apply Settings') ?></button>
        <button type='button' onclick='restartService()'><?= translate('Restart Service') ?></button>
    </dd>
</dl>

</form>

<!-- Configuration Files Tabs Section -->
<div class="config-files-section">
<br>
<table class="unraid tablesorter"><thead><tr><td><?= translate('Configuration Files') ?></td></tr></thead></table>

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
        <small><?= translate('File:') ?> <?= htmlspecialchars($config_files[$current_tab]['path']) ?></small>
    </div>
    <blockquote class='inline_help'><?= translate('A saved EasyTier config file overrides local settings unless a valid Config Server Address is configured.') ?></blockquote>

    <form method="POST" action="/plugins/easytier/include/save_config_file.php" id="configFileForm">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($current_tab) ?>">
        <input type="hidden" name="file_path" value="<?= htmlspecialchars($config_files[$current_tab]['path']) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($GLOBALS['var']['csrf_token'] ?? '') ?>">

        <textarea name="config_content" id="configEditor" class="config-editor"
                  placeholder="# Configuration file will be created when you save"
                  spellcheck="false"><?= htmlspecialchars($config_content) ?></textarea>

        <div class="editor-actions">
            <button type='button' onclick='saveConfigFile()'><?= translate('Save Configuration') ?></button>
            <button type='button' onclick='resetConfigFile()'><?= translate('Reset') ?></button>
            <button type='button' onclick='downloadConfigFile()'><?= translate('Download') ?></button>
        </div>
    </form>
</div>

<script>
const easytierI18n = <?= json_encode([
    'restartConfirm' => translate('Are you sure you want to restart the EasyTier service?'),
    'restartFailed' => translate('Restart request failed'),
    'configSaved' => translate('Configuration file saved successfully!'),
    'configSaveError' => translate('Error saving configuration:'),
    'unknownError' => translate('Unknown error'),
    'error' => translate('Error:'),
    'resetConfirm' => translate('Are you sure you want to reset to the last saved version?'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

function switchTab(tabId) {
    const url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    window.location.href = url.toString();
}

function applyServerSettings() {
    document.getElementById('serverForm').submit();
}

function restartService() {
    if (confirm(easytierI18n.restartConfirm)) {
        const body = new URLSearchParams({
            csrf_token: '<?= htmlspecialchars($GLOBALS['var']['csrf_token'] ?? '') ?>'
        });
        fetch('/plugins/easytier/include/restart_service.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body
        }).then(response => {
            if (!response.ok) throw new Error(easytierI18n.restartFailed);
            return response.text();
        }).then(message => alert(message)).catch(error => alert(error));
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
            alert(easytierI18n.configSaved);
        } else {
            alert(easytierI18n.configSaveError + ' ' + (data.error || easytierI18n.unknownError));
        }
    })
    .catch(error => {
        alert(easytierI18n.error + ' ' + error);
    });
}

function resetConfigFile() {
    if (confirm(easytierI18n.resetConfirm)) {
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
</div>
