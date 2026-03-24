<?php
// Temporary DB connectivity test (local dev only). Remove after use.
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
$out = [];
$out[] = "DB_HOST=" . DB_HOST;
$out[] = "DB_PORT=" . DB_PORT;
$out[] = "DB_NAME=" . DB_NAME;
$out[] = "DB_USER=" . DB_USER;
$out[] = "DB_PASS=" . (DB_PASS === '' ? '<empty>' : '<hidden>');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $out[] = "CONNECTION: OK";
} catch (PDOException $e) {
    $out[] = "CONNECTION: ERROR";
    $out[] = "ERROR_MSG: " . $e->getMessage();
}

echo implode("\n", $out) . "\n";
