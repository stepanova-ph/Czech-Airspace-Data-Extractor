<?php
require_once dirname(__FILE__) . '/../utils.php';
require_once dirname(__FILE__) . '/../common.php';
require_once dirname(__FILE__) . '/../config.php';

function get_uup_urls_from_aup_page() {
    try {
        $main_url = WEBSITE_ROOT;
        print_info("Fetching UUP links from main page: $main_url");
        
        $html_content = fetch_content($main_url);
        if (!$html_content) {
            print_warning("Failed to fetch main AUP page");
            return array();
        }
        
        $dom = new DOMDocument();
        @$dom->loadHTML($html_content);
        $xpath = new DOMXPath($dom);
        
        $uup_links = array();
        
        $a_tags = $xpath->query('//a[contains(text(), "Platný UUP")]');
        
        foreach ($a_tags as $a_tag) {
            $href = $a_tag->getAttribute('href');
            if ($href && strpos($href, 'uup_') !== false) {
                $time_text = $a_tag->parentNode->textContent;
                
                preg_match('/od (\d{2}\.\d{2}\.\d{4} \d{2}:\d{2} UTC)/', $time_text, $date_from_match);
                preg_match('/do (\d{2}\.\d{2}\.\d{4} \d{2}:\d{2} UTC)/', $time_text, $date_to_match);
                
                $from_time = isset($date_from_match[1]) ? $date_from_match[1] : "Unknown";
                $to_time = isset($date_to_match[1]) ? $date_to_match[1] : "Unknown";
                
                preg_match('/uup_(\d{8})/', $href, $uup_id_match);
                if ($uup_id_match) {
                    $date_str = $uup_id_match[1];
                    
                    $uup_links[] = array(
                        'url' => $href,
                        'date' => $date_str,
                        'from_time' => $from_time,
                        'to_time' => $to_time,
                    );
                }
            }
        }
        
        print_info("Found " . count($uup_links) . " UUP links");
        
        return $uup_links;
    
    } catch (Exception $e) {
        print_error("Error fetching UUP URLs: " . $e->getMessage());
        return array();
    }
}

function preprocess_uup_content($content) {
    if (!$content) {
        return null;
    }
    
    $dom = new DOMDocument();
    @$dom->loadHTML($content);
    $xpath = new DOMXPath($dom);
    
    $nodes = $xpath->query("//text()[contains(., 'C/ Prostory spravovane AMC (AMA)')]");
    
    if ($nodes->length === 0) {
        print_warning("AMA section not found in UUP content");
        return null;
    }
    
    $ama_section = $nodes->item(0);
    
    $heading_element = $ama_section->parentNode;
    if (!$heading_element) {
        print_warning("Could not find AMA section heading element");
        return null;
    }
    
    $table_row = $heading_element->parentNode;
    if (!$table_row) {
        print_warning("Could not find AMA section table row");
        return null;
    }
    
    $data_rows = array();
    $next_row = null;
    
    $current_node = $table_row->nextSibling;
    while ($current_node) {
        if ($current_node->nodeName === 'tr') {
            $next_row = $current_node;
            break;
        }
        $current_node = $current_node->nextSibling;
    }
    
    while ($next_row) {
        $d_section = $xpath->query(".//text()[starts-with(normalize-space(.), 'D/')]", $next_row);
        
        if ($d_section->length > 0) {
            break;
        }
        
        $data_rows[] = $next_row;
        
        $current_node = $next_row->nextSibling;
        $next_row = null;
        while ($current_node) {
            if ($current_node->nodeName === 'tr') {
                $next_row = $current_node;
                break;
            }
            $current_node = $current_node->nextSibling;
        }
    }
    
    if (empty($data_rows)) {
        print_warning("No AMA data rows found in UUP");
        return null;
    }
    
    $result = $dom->saveHTML($table_row);
    foreach ($data_rows as $row) {
        $result .= $dom->saveHTML($row);
    }
    
    return $result;
}

function process_uup_data() {
    print_info("Starting UUP data processing");
    
    $uup_links = get_uup_urls_from_aup_page();
    
    if (empty($uup_links)) {
        print_info("No UUP links found");
        return array();
    }
    
    $all_spaces = array();
    
    foreach ($uup_links as $uup) {
        $url = $uup['url'];
        $date_str = $uup['date'];
        
        print_info("Processing UUP for $date_str, URL: $url");
        
        if (strpos($url, 'http') !== 0) {
            $url = WEBSITE_ROOT . ltrim($url, '/');
        }
        
        $content = fetch_content($url);
        if (!$content) {
            print_warning("Failed to fetch UUP content for $date_str");
            continue;
        }
        
        $ama_section = preprocess_uup_content($content);
        if (!$ama_section) {
            print_warning("No AMA section found in UUP for $date_str");
            continue;
        }
        
        $ama_spaces = extract_ama_spaces($ama_section);
        if (empty($ama_spaces)) {
            print_warning("No AMA spaces found in UUP for $date_str");
            continue;
        }
        
        print_info("Found " . count($ama_spaces) . " AMA spaces in UUP for $date_str");
        
        if (!isset($all_spaces[$date_str])) {
            $all_spaces[$date_str] = array();
        }
        
        $all_spaces[$date_str] = array_merge($all_spaces[$date_str], $ama_spaces);
    }
    
    print_info("UUP data processing completed");
    return $all_spaces;
}

function save_uup_data($filename, $spaces) {
    try {
        $directory = dirname($filename);
        ensure_directory($directory);
        
        $file_exists = file_exists($filename);
        
        $file = fopen($filename, 'a+');
        
        if (!$file) {
            throw new Exception("Unable to open file for writing");
        }
        
        if (!$file_exists) {
            fwrite($file, "space;lower_bound;upper_bound;from_time;to_time;\n");
        }
        
        foreach ($spaces as $space) {
            fwrite($file, $space['space'] . ";" . 
                           $space['lower_bound'] . ";" . 
                           $space['upper_bound'] . ";" . 
                           $space['from_time'] . ";" . 
                           $space['to_time'] . ";\n");
        }
        
        fclose($file);
        print_info("UUP file created: $filename");
    } catch (Exception $e) {
        print_error("Error creating UUP file $filename: " . $e->getMessage());
    }
}

function format_uup_as_csv($spaces) {
    $output = "space;lower_bound;upper_bound;from_time;to_time;\n";
    
    foreach ($spaces as $space) {
        $output .= $space['space'] . ";" . 
                   $space['lower_bound'] . ";" . 
                   $space['upper_bound'] . ";" . 
                   $space['from_time'] . ";" . 
                   $space['to_time'] . ";\n";
    }
    
    return $output;
}


?>