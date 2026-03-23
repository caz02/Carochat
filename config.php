<?php

function env_or_default($key, $default = null) {
    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}

define('DB_HOST', env_or_default('DB_HOST', '127.0.0.1'));
define('DB_PORT', env_or_default('DB_PORT', '3306'));
define('DB_NAME', env_or_default('DB_NAME', 'carochat_db'));
define('DB_USER', env_or_default('DB_USER', 'root'));
define('DB_PASS', env_or_default('DB_PASS', ''));
define('DB_SSL_CA', env_or_default('DB_SSL_CA', '/etc/ssl/certs/ca-certificates.crt'));
define('APP_URL', env_or_default('APP_URL', 'http://127.0.0.1:10000'));
define('WS_URL', env_or_default('WS_URL', 'ws://127.0.0.1:3000'));