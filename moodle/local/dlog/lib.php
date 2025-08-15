<?php
defined('MOODLE_INTERNAL') || die();

function dlog($message) {
    $logfile = __DIR__ . '/general.log';
    $time = date('Y-m-d H:i:s');
    
    if (!is_string($message)) {
        ob_start();
        print_r($message); // or var_export($message, true);
        $message = ob_get_clean();
    }
    $entry = "[$time] $message\n";

    file_put_contents($logfile, $entry, FILE_APPEND);
}