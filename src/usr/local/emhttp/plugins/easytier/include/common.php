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

define(__NAMESPACE__ . "\PLUGIN_ROOT", dirname(dirname(__FILE__)));
define(__NAMESPACE__ . "\PLUGIN_NAME", "easytier");

/*
 * Unraid's webGUI loads translations for the current request URI.  The
 * plugin also renders API messages and page fragments that are not tied to
 * that URI, so load the merged plugin language file explicitly.
 */
function loadTranslations(): void
{
    static $loaded = false;

    if (!$loaded && function_exists('parse_plugin')) {
        \parse_plugin(PLUGIN_NAME);
        $loaded = true;
    }
}

loadTranslations();

// Try to load composer autoloader first, fallback to manual loading
require_once "/usr/local/php/easytier-utils/bootstrap.php";

$utils = new Utils(PLUGIN_NAME);
$utils->setPHPDebug();

/**
 * Translate plugin text through Unraid's language system.
 *
 * The fallback keeps the pages and command responses usable outside the
 * Unraid webGUI, including the render tests.
 */
function translate(string $text): string
{
    loadTranslations();
    return function_exists('_') ? \_($text) : $text;
}

function requireCsrfToken(?string $token): void
{
    $varFile = '/var/local/emhttp/var.ini';
    $server = file_exists($varFile) ? parse_ini_file($varFile) : false;
    $expected = is_array($server) ? ($server['csrf_token'] ?? '') : '';

    if (!is_string($token) || $expected === '' || !hash_equals($expected, $token)) {
        http_response_code(403);
        throw new \RuntimeException(translate('Invalid CSRF token'));
    }
}
