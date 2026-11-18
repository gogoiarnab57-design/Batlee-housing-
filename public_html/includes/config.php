<?php
/**
 * Batlee Housing Solution - configuration
 * Edit the database details below after creating your MySQL database.
 * For best security, keep this folder blocked from the web (the included .htaccess does this).
 */

// ---- Database -------------------------------------------------------------
define('DB_DRIVER', 'mysql');          // 'mysql' for live hosting. 'sqlite' is only for quick local testing.
define('DB_HOST',   'localhost');
define('DB_NAME',   'batlee_shop');
define('DB_USER',   'root');
define('DB_PASS',   '');
define('SQLITE_PATH', dirname(__DIR__) . '/sql/local-test.sqlite');

// ---- Site -----------------------------------------------------------------
define('APP_DEBUG', false);            // keep false on a live site
define('APP_TIMEZONE', 'Asia/Kolkata');
define('BASE_URL_OVERRIDE', '');       // e.g. '/shop' if auto-detection is wrong; normally leave empty

// ---- Limits ---------------------------------------------------------------
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);
define('LOGIN_MAX_ATTEMPTS', 5);       // failed logins allowed ...
define('LOGIN_WINDOW_MINUTES', 15);    // ... within this many minutes before a temporary lock
define('ADMIN_IDLE_MINUTES', 30);      // admin is logged out after this much inactivity
