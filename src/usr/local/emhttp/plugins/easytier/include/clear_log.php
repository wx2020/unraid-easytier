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
    // Get JSON input
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);

    $log_file = $data['log_file'] ?? '';

    // Validate input
    if (empty($log_file)) {
        throw new \Exception('Missing log file parameter');
    }

    // Security check: ensure file path is the allowed log file
    if ($log_file !== '/var/log/easytier.log') {
        throw new \Exception('Invalid log file path');
    }

    // Check if file exists
    if (!file_exists($log_file)) {
        throw new \Exception('Log file does not exist');
    }

    // Clear the file
    $result = file_put_contents($log_file, '');
    if ($result === false) {
        throw new \Exception('Failed to clear log file');
    }

    // Log the action
    $log_entry = sprintf(
        "[%s] Log file cleared: %s by user %s\n",
        date('Y-m-d H:i:s'),
        $log_file,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    );
    file_put_contents('/var/log/easytier.log', $log_entry, FILE_APPEND);

    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Log file cleared successfully',
        'file' => $log_file
    ]);

} catch (\Exception $e) {
    // Log error
    error_log('EasyTier log clear error: ' . $e->getMessage());

    // Return error
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
