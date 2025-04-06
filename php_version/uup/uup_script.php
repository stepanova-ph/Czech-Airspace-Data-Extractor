<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/uup_extractor.php';

setup_logging();
print_info("Starting UUP data extraction at " . date('Y-m-d H:i:s'));

$uup_data = process_uup_data();

if (!empty($_SERVER['HTTP_HOST'])) {
    if (!empty($uup_data)) {
        $dates = array_keys($uup_data);
        $latest_date = $dates[0];
        $latest_spaces = $uup_data[$latest_date];
        
        $csv_data = format_uup_as_csv($latest_spaces);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="uup_' . $latest_date . '.csv"');
        
        echo $csv_data;
    } else {
        header('HTTP/1.0 404 Not Found');
        echo "No UUP data available.";
    }
}

print_info("UUP data extraction completed at " . date('Y-m-d H:i:s'));
?>