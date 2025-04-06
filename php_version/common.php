<?php
require_once dirname(__FILE__) . '/utils.php';

function extract_ama_spaces($preprocessed_content) {
    if (!$preprocessed_content) {
        return array();
    }
    
    $dom = new DOMDocument();
    @$dom->loadHTML($preprocessed_content);
    $xpath = new DOMXPath($dom);
    
    $ama_spaces = array();
    
    $rows = $xpath->query('//tr');
    
    foreach ($rows as $row) {
        if ($row->getElementsByTagName('th')->length > 0) {
            continue;
        }
        
        $cells = $row->getElementsByTagName('td');
        if ($cells->length < 6) {
            continue;
        }
        
        $first_cell_text = trim($cells->item(0)->textContent);
        if (empty($first_cell_text) || !is_numeric($first_cell_text[0])) {
            continue;
        }
        
        if ($cells->length >= 6) {
            try {
                $space_info = array(
                    'space' => trim($cells->item(1)->textContent),
                    'lower_bound' => trim($cells->item(2)->textContent),
                    'upper_bound' => trim($cells->item(3)->textContent),
                    'from_time' => trim($cells->item(4)->textContent),
                    'to_time' => trim($cells->item(5)->textContent),
                );
                $ama_spaces[] = $space_info;
            } catch (Exception $e) {
                print_warning("Error parsing row: " . $dom->saveHTML($row));
            }
        }
    }
    
    return $ama_spaces;
}
?>