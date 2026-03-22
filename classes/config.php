<?php

// Read DB configuration from environment variables when available so the
// application can run in containers without editing source files.
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'carochat_db';

define('DBUSER', $db_user);
define('DBPASS', $db_pass);
define('DBNAME', $db_name);
// prefer TCP loopback to avoid socket path mismatches between different PHP builds
// set to '127.0.0.1' to force TCP; if you run a socket-only setup, change to 'localhost'
define('DBHOST', $db_host);
