<?php

function env_or_default($key, $default = null) {
    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}

// Default DB connection values. These default to your TiDB Cloud cluster
// connection parameters (shown in the cluster console). You can override any
// value by setting the corresponding environment variable (recommended for
// secrets like DB_PASS).
define('DB_HOST', env_or_default('DB_HOST', 'gateway01.eu-central-1.prod.aws.tidbcloud.com'));
define('DB_PORT', env_or_default('DB_PORT', '4000'));
define('DB_NAME', env_or_default('DB_NAME', 'carochat_db'));
define('DB_USER', env_or_default('DB_USER', '33LLC6EVcQXEZmc.root'));
// Leave DB_PASS empty by default so secrets aren't stored in the repo.
// Set DB_PASS via environment variable or replace the second argument below
// with the password when ready.
define('DB_PASS', env_or_default('DB_PASS', 'P3HCIytvBuqIHvxG'));

// Path to TLS CA certificate used for secure DB connections (TiDB Cloud requires TLS).
// Prefer the system CA path which is readable on this macOS host, but you may
// also set DB_SSL_CA to the downloaded PEM path if you have a cluster bundle.
define('DB_SSL_CA', env_or_default('DB_SSL_CA', '/etc/ssl/cert.pem'));
define('APP_URL', env_or_default('APP_URL', 'http://127.0.0.1:10000'));
define('WS_URL', env_or_default('WS_URL', 'ws://127.0.0.1:3000'));