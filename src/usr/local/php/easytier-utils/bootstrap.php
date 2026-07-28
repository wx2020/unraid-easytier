<?php

declare(strict_types=1);

if (!defined('EasyTier\PLUGIN_NAME')) {
    define('EasyTier\PLUGIN_NAME', 'easytier');
}

$classDir = __DIR__ . '/easytier-utils';
require_once $classDir . '/BaseUtils.php';
require_once $classDir . '/BaseSystem.php';
require_once $classDir . '/Utils.php';
require_once $classDir . '/System.php';
require_once $classDir . '/Config.php';
