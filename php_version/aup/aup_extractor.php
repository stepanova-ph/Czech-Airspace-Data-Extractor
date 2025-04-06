<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/../config.php';

function create_aup_url($date) {
    return URL_BASE . "/aup_" . $date->format('dmY') . ".htm";
}

function preprocess_aup_content($content) {
    if (!$content) {
        return null;
    }
    
    $dom = new DOMDocument();
    @$dom->loadHTML($content);
    $xpath = new DOMXPath($dom);
    
    $title_elements = $xpath->query("//th[@class='titlex' and contains(text(), 'C/ Prostory spravovane AMC (AMA)')]");
    
    if ($title_elements->length === 0) {
        print_warning("AMA section not found in the content");
        return null;
    }
    
    $title_row = $title_elements->item(0)->parentNode;
    
    $data_row = null;
    $current_node = $title_row->nextSibling;
    while ($current_node) {
        if ($current_node->nodeName === 'tr') {
            $data_row = $current_node;
            break;
        }
        $current_node = $current_node->nextSibling;
    }
    
    if (!$data_row) {
        print_warning("AMA data table not found");
        return null;
    }
    
    return $dom->saveHTML($title_row) . $dom->saveHTML($data_row);
}

function extract_ama_spaces($preprocessed_content) {
    if (!$preprocessed_content) {
        return array();
    }
    
    $dom = new DOMDocument();
    @$dom->loadHTML($preprocessed_content);
    $xpath = new DOMXPath($dom);
    
    $data_rows = $xpath->query("//tr[td[@class='data']]");
    $ama_spaces = array();
    
    foreach ($data_rows as $row) {
        $cells = $xpath->query(".//td[@class='data']", $row);
        
        if ($cells->length < 8) {
            continue;
        }
        
        $space_info = array(
            'space' => trim($cells->item(1)->textContent),
            'lower_bound' => trim($cells->item(2)->textContent),
            'upper_bound' => trim($cells->item(3)->textContent),
            'from_time' => trim($cells->item(4)->textContent),
            'to_time' => trim($cells->item(5)->textContent),
        );
        
        $ama_spaces[] = $space_info;
    }
    
    return $ama_spaces;
}

function fetch_aup_spaces_for_date($date) {
    $url = create_aup_url($date);
    print_info("Fetching AUP data for " . $date->format('Y-m-d') . " from: $url");
    
    $content = fetch_content($url);
    if (!$content) {
        print_warning("No content retrieved for " . $date->format('Y-m-d'));
        return array();
    }
    
    $ama_section = preprocess_aup_content($content);
    $ama_spaces = extract_ama_spaces($ama_section);
    
    print_info("Found " . count($ama_spaces) . " AMA spaces for " . $date->format('Y-m-d'));
    return $ama_spaces;
}

function create_aup_file($filename, $spaces) {
    try {
        $directory = dirname($filename);
        ensure_directory($directory);
        
        $file = fopen($filename, "w");
        
        if (!$file) {
            throw new Exception("Unable to open file for writing");
        }
        
        fwrite($file, "space;lower_bound;upper_bound;from_time;to_time\n");
        
        foreach ($spaces as $space) {
            fwrite($file, $space['space'] . ";" . 
                           $space['lower_bound'] . ";" . 
                           $space['upper_bound'] . ";" . 
                           $space['from_time'] . ";" . 
                           $space['to_time'] . "\n");
        }
        
        fclose($file);
        print_info("AUP file created: $filename");
    } catch (Exception $e) {
        print_error("Error creating AUP file $filename: " . $e->getMessage());
    }
}

function process_aup_data() {
    $today = new DateTime();
    $tomorrow = new DateTime();
    $tomorrow->modify('+1 day');
    
    ensure_directory(OUTPUT_DIR_AUP);
    
    $ama_today = fetch_aup_spaces_for_date($today);
    if ($ama_today) {
        $today_filename = OUTPUT_DIR_AUP . "/aup_" . $today->format('dmY') . ".csv";
        create_aup_file($today_filename, $ama_today);
    }
    
    try {
        $ama_tomorrow = fetch_aup_spaces_for_date($tomorrow);
        if ($ama_tomorrow) {
            $tomorrow_filename = OUTPUT_DIR_AUP . "/aup_" . $tomorrow->format('dmY') . ".csv";
            create_aup_file($tomorrow_filename, $ama_tomorrow);
        }
    } catch (Exception $e) {
        print_warning("Error processing tomorrow's data: " . $e->getMessage());
        print_info("Continuing with program execution...");
    }
}
?>