<?php
// One-off TLS-aware PDO test for deployed environment.
// Drop this into your deployed app root and open it in the browser to get
// verbose info about the PDO connection, env vars, and CA file visibility.

header('Content-Type: text/plain; charset=UTF-8');

function e($x){ echo $x . "\n"; }

// Read DB config from env or config.php
$host = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : '');
$port = getenv('DB_PORT') ?: (defined('DB_PORT') ? DB_PORT : '');
$dbname = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : '');
$user = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : '');
$pass = getenv('DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : '');
$ca = getenv('DB_SSL_CA') ?: (defined('DB_SSL_CA') ? DB_SSL_CA : '');

e("DB_HOST=$host");
e("DB_PORT=$port");
e("DB_NAME=$dbname");
e("DB_USER=$user");
e("DB_PASS=" . ($pass ? '***SET***' : '(<empty>)'));
e("DB_SSL_CA=$ca");

e("\n-- CA file checks --");
if($ca){
    e("file_exists: " . (file_exists($ca) ? 'yes' : 'no'));
    e("is_readable: " . (is_readable($ca) ? 'yes' : 'no'));
    e("realpath: " . (realpath($ca) ?: '(none)'));
    e("\nfirst 200 chars of CA (sanity):\n" . (file_exists($ca) ? substr(file_get_contents($ca),0,200) : '(no file)'));
} else {
    e("DB_SSL_CA is empty");
}

// show openssl & pdo
e("\nextensions: openssl=" . (extension_loaded('openssl') ? 'yes' : 'no') . ", pdo_mysql=" . (extension_loaded('pdo_mysql') ? 'yes' : 'no'));

// Try PDO connect with explicit MYSQL_ATTR_SSL_CA option if CA file exists
if(!$host || !$port || !$dbname || !$user){
    e("\nMissing DB config, skipping PDO connect test.");
    exit;
}

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ];

if($ca && file_exists($ca) && defined('PDO::MYSQL_ATTR_SSL_CA')){
    $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
}

try {
    e("\nAttempting PDO connection...\n");
    $pdo = new PDO($dsn, $user, $pass, $options);
    e("PDO connected OK. Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION));
    // quick query
    $st = $pdo->query("SELECT 1 as ok");
    $row = $st->fetch();
    e("Test query returned: " . json_encode($row));
} catch (PDOException $e) {
    e("PDOException: " . $e->getMessage());
    if(method_exists($e, 'getTraceAsString')){
        e("Trace:\n" . $e->getTraceAsString());
    }
}

?>