<?php
// Dev-only helper: returns recent messages for the current logged-in user as JSON
// Usage (while logged in in the browser):
//  - /debug_messages.php            -> messages involving current user
//  - /debug_messages.php?orphan=1   -> show recent messages with missing sender/receiver

header('Content-Type: application/json; charset=UTF-8');
session_start();

$out = (object)[];
// require DB
require_once(__DIR__.'/classes/autoload.php');
$DB = new Database();

if(!isset($_SESSION['userid'])){
    http_response_code(200);
    $out->logged_in = false;
    echo json_encode($out, JSON_PRETTY_PRINT);
    exit;
}

$userid = $_SESSION['userid'];
$out->userid = $userid;

try{
    if(isset($_GET['orphan']) && $_GET['orphan'] == '1'){
        $sql = "SELECT id,sender,receiver,message,files,date,msgid FROM messages WHERE sender IS NULL OR sender = '' OR receiver IS NULL OR receiver = '' ORDER BY id DESC LIMIT 200";
        $rows = $DB->read($sql, []);
        $out->orphan_count = is_array($rows) ? count($rows) : 0;
        $out->orphan_rows = $rows ?: [];
    } else {
        $sql = "SELECT id,sender,receiver,message,files,date,msgid FROM messages WHERE sender = :u OR receiver = :u ORDER BY id DESC LIMIT 200";
        $rows = $DB->read($sql, ['u' => $userid]);
        $out->count = is_array($rows) ? count($rows) : 0;
        $out->messages = $rows ?: [];
    }
}catch(Exception $e){
    http_response_code(500);
    $out->error = (string)$e->getMessage();
}

echo json_encode($out, JSON_PRETTY_PRINT);
