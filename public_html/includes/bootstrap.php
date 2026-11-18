<?php
declare(strict_types=1);

define('ROOT_DIR', dirname(__DIR__));
require __DIR__ . '/config.php';
date_default_timezone_set(APP_TIMEZONE);

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
