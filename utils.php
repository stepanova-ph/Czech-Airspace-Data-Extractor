<?php
function print_info($message) {
    fprintf(STDERR, "%s - INFO - %s\n", date('Y-m-d H:i:s'), $message);
}

function print_warning($message) {
    fprintf(STDERR, "%s - WARNING - %s\n", date('Y-m-d H:i:s'), $message);
}

function print_error($message) {
    fprintf(STDERR, "%s - ERROR - %s\n", date('Y-m-d H:i:s'), $message);
}
?>