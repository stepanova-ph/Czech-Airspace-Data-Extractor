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

function setup_logging() {
    ini_set('log_errors', 1);
    ini_set('error_log', 'app.log');
}

function ensure_directory($directory) {
    if (!file_exists($directory)) {
        if (mkdir($directory, 0777, true)) {
            print_info("Created directory: $directory");
        } else {
            print_error("Failed to create directory: $directory");
        }
    }
}

function fetch_content($url) {
    try {
        $context = stream_context_create(array(
            'http' => array(
                'ignore_errors' => true,
                'timeout' => 30
            )
        ));
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            print_warning("Error fetching content from $url: " . (isset($error['message']) ? $error['message'] : 'Unknown error'));
            return null;
        }
        
        $status_line = isset($http_response_header[0]) ? $http_response_header[0] : '';
        preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
        $status = isset($match[1]) ? $match[1] : '';
        
        if ($status != '200') {
            print_warning("Error fetching content from $url: Status code $status");
            return null;
        }
        
        return $response;
    } catch (Exception $e) {
        print_warning("Error fetching content from $url: " . $e->getMessage());
        return null;
    }
}
?>