<?php
defined('MOODLE_INTERNAL') || die();

// function dlog($message){
//     error_log("dlog: {$message}");
// }
function dlog($message) {
    $logfile = __DIR__ . '/general.log'; // Ghi log vào file trong plugin
    $time = date('Y-m-d H:i:s');
    $entry = "[$time] $message\n";

    file_put_contents($logfile, $entry, FILE_APPEND);
}