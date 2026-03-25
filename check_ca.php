<?php
// Quick CA file existence/readability checker for Apache/PHP SAPI.
// Place this file into the Apache-served copy (htdocs) and open it in a browser.

$paths = [
    '/etc/ssl/cert.pem',
    '/etc/ssl/certs/ca-certificates.crt',
    '/Applications/XAMPP/xamppfiles/htdocs/carochat/classes/certs/tidb_ca.pem',
    __DIR__ . '/classes/certs/tidb_ca.pem',
];

header('Content-Type: text/plain; charset=utf-8');
foreach($paths as $p){
    $exists = file_exists($p) ? 'yes' : 'no';
    $readable = is_readable($p) ? 'yes' : 'no';
    $real = realpath($p) ?: '(none)';
    echo "Path: $p\n";
    echo "  exists: $exists\n";
    echo "  readable: $readable\n";
    echo "  realpath: $real\n";
    echo "\n";
}

// Also print PHP info for OpenSSL and PDO MySQL availability
echo "openssl: " . (extension_loaded('openssl') ? 'loaded' : 'missing') . "\n";
echo "pdo_mysql: " . (extension_loaded('pdo_mysql') ? 'loaded' : 'missing') . "\n";

?>