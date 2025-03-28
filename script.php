<?php
require_once 'utils.php';

$URL_BASE = "https://aup.rlp.cz/data";

function create_url($date) {
    global $URL_BASE;
    return $URL_BASE . "/aup_" . $date->format('dmY') . ".htm";
}

function fetch_content($url) {
    try {
        $response = file_get_contents($url);
        if ($response === false) {
            print_warning("Error fetching content from $url");
            return null;
        }
        return $response;
    } catch (Exception $e) {
        print_warning("Error fetching content from $url: " . $e->getMessage());
        return null;
    }
}

function preprocess_content($content) {
    if (!$content) {
        return null;
    }
    
    $dom = new DOMDocument();
    @$dom->loadHTML($content);
    $xpath = new DOMXPath($dom);
    
    $titleElements = $xpath->query("//th[@class='titlex' and contains(text(), 'C/ Prostory spravovane AMC (AMA)')]");
    
    if ($titleElements->length === 0) {
        print_warning("AMA section not found in the content");
        return null;
    }
    
    $titleRow = $titleElements->item(0)->parentNode;
    
    $dataRow = null;
    $currentNode = $titleRow->nextSibling;
    
    while ($currentNode) {
        if ($currentNode->nodeName === 'tr') {
            $dataRow = $currentNode;
            break;
        }
        $currentNode = $currentNode->nextSibling;
    }
    
    if (!$dataRow) {
        print_warning("AMA data table not found");
        return null;
    }
    
    $result = $dom->saveHTML($titleRow) . $dom->saveHTML($dataRow);
    return $result;
}

function extract_AMA_space($preprocessed_content) {
    if (!$preprocessed_content) {
        return array();
    }
    
    $dom = new DOMDocument();
    @$dom->loadHTML($preprocessed_content);
    $xpath = new DOMXPath($dom);
    
    $dataRows = $xpath->query("//tr[td[@class='data']]");
    $ama_spaces = array();
    
    foreach ($dataRows as $row) {
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

function fetch_spaces_for_date($date) {
    $url = create_url($date);
    print_info("Fetching data for " . $date->format('Y-m-d') . " from: $url");
    
    $content = fetch_content($url);
    if (!$content) {
        print_warning("No content retrieved for " . $date->format('Y-m-d'));
        return array();
    }
    
    $ama_section = preprocess_content($content);
    $ama_spaces = extract_AMA_space($ama_section);
    
    print_info("Found " . count($ama_spaces) . " AMA spaces for " . $date->format('Y-m-d'));
    return $ama_spaces;
}

function create_csv($filename, $spaces) {
    try {
        $directory = dirname($filename);
        if (!empty($directory) && !file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
        
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
        print_info("CSV file created: $filename");
    } catch (Exception $e) {
        print_error("Error creating CSV file $filename: " . $e->getMessage());
    }
}

function main() {
    $today = new DateTime();
    $tomorrow = new DateTime();
    $tomorrow->modify('+1 day');
    
    $output_dir = "ama_data";
    if (!file_exists($output_dir)) {
        mkdir($output_dir, 0777, true);
    }
    
    $ama_today = fetch_spaces_for_date($today);
    if (!empty($ama_today)) {
        $today_filename = $output_dir . "/ama_" . $today->format('dmY') . ".csv";
        create_csv($today_filename, $ama_today);
    }
    
    try {
        $ama_tomorrow = fetch_spaces_for_date($tomorrow);
        if (!empty($ama_tomorrow)) {
            $tomorrow_filename = $output_dir . "/ama_" . $tomorrow->format('dmY') . ".csv";
            create_csv($tomorrow_filename, $ama_tomorrow);
        }
    } catch (Exception $e) {
        print_warning("Error processing tomorrow's data: " . $e->getMessage());
        print_info("Continuing with program execution...");
    }
}

main();
?>
