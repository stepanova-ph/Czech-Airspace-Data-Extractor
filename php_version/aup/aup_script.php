<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/aup_extractor.php';

setup_logging();
print_info("Starting AUP data extraction at " . date('Y-m-d H:i:s'));
process_aup_data();
print_info("AUP data extraction completed at " . date('Y-m-d H:i:s'));

if (!empty($_SERVER['HTTP_HOST'])) {
    echo "AUP data processed successfully.";
}
?>