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
    // Get POST data
    $tab = $_POST['tab'] ?? '';
    $file_path = $_POST['file_path'] ?? '';
    $config_content = $_POST['config_content'] ?? '';

    // Validate inputs
    if (empty($tab) || empty($file_path)) {
        throw new \Exception('Missing required parameters');
    }

    // Security check: ensure file path is in the allowed directory
    $allowed_path = '/boot/config/plugins/easytier/';
    if (strpos($file_path, $allowed_path) !== 0) {
        throw new \Exception('Invalid file path');
    }

    // Ensure directory exists
    $dir = dirname($file_path);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            throw new \Exception('Failed to create directory');
        }
    }

    // Write content to file
    $result = file_put_contents($file_path, $config_content);
    if ($result === false) {
        throw new \Exception('Failed to write to file');
    }

    // Set proper permissions
    chmod($file_path, 0644);

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
        'message' => 'Configuration file saved successfully',
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
