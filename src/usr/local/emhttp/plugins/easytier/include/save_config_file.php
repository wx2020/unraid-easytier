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

header('Content-Type: application/json');

// Load common functions
require_once dirname(__FILE__) . '/common.php';

try {
    requireCsrfToken($_POST['csrf_token'] ?? $_REQUEST['csrf_token'] ?? null);

    // Get POST data
    $tab = $_POST['tab'] ?? '';
    $file_path = $_POST['file_path'] ?? '';
    $config_content = $_POST['config_content'] ?? '';

    // P1 S-07: limit size to protect /boot (FAT, small)
    if (strlen($config_content) > 256 * 1024) {
        throw new \Exception(translate('Configuration file too large'));
    }
    if (str_contains($config_content, "\0")) {
        throw new \Exception(translate('Invalid file content'));
    }
    // Basic TOML validation: non-empty, non-comment lines should contain '=' or '[' or be empty
    $hasInvalid = false;
    foreach (explode("\n", $config_content) as $line) {
        $t = trim($line);
        if ($t === '' || str_starts_with($t, '#') || str_starts_with($t, ';')) {
            continue;
        }
        if (!str_contains($t, '=') && !str_contains($t, '[')) {
            $hasInvalid = true;
            break;
        }
        if (strlen($t) > 4096) {
            $hasInvalid = true;
            break;
        }
    }
    if ($hasInvalid) {
        throw new \Exception(translate('Invalid TOML content'));
    }

    // Validate inputs
    if (empty($tab) || empty($file_path)) {
        throw new \Exception(translate('Missing required parameters'));
    }

    $allowedFiles = [
        'main' => Config::CORE_CONFIG_FILE,
    ];
    if (!isset($allowedFiles[$tab]) || !hash_equals($allowedFiles[$tab], $file_path)) {
        throw new \Exception(translate('Invalid file path'));
    }

    // Ensure directory exists
    $dir = dirname($file_path);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            throw new \Exception(translate('Failed to create directory'));
        }
    }

    // Write content to file
    $result = file_put_contents($file_path, $config_content, LOCK_EX);
    if ($result === false) {
        throw new \Exception(translate('Failed to write to file'));
    }

    // P0 S-03: restrict to owner only (contains secrets)
    chmod($file_path, 0600);
    @chmod(dirname($file_path), 0700);

    // Log the action
    $log_entry = sprintf(
        "[%s] Config file saved: %s by user %s\n",
        date('Y-m-d H:i:s'),
        $file_path,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    );
    file_put_contents('/var/log/easytier.log', $log_entry, FILE_APPEND);

    // Return success
    echo json_encode([
        'success' => true,
        'message' => translate('Configuration file saved successfully'),
        'file' => $file_path
    ]);

} catch (\Exception $e) {
    // Log error
    error_log('EasyTier config save error: ' . $e->getMessage());

    // Return error
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
