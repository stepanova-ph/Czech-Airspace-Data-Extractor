<?php
function print_info($message) {
    error_log(date('Y-m-d H:i:s') . " - INFO - " . $message);
}

function print_warning($message) {
    error_log(date('Y-m-d H:i:s') . " - WARNING - " . $message);
}

function print_error($message) {
    error_log(date('Y-m-d H:i:s') . " - ERROR - " . $message);
}
?>