import requests
from bs4 import BeautifulSoup
from datetime import datetime, timedelta
import os
import re
from common import extract_ama_spaces
from utils import print_info, print_warning, print_error, ensure_directory
from config import URL_BASE, OUTPUT_DIR_UUP

LOCAL_HTML_PATH = "/home/petra/Desktop/PROJECTS/tata_script/web.htm"
USE_LOCAL_FILE = True

def get_uup_urls_from_aup_page():
    try:
        if USE_LOCAL_FILE:
            print_info(f"Using local HTML file: {LOCAL_HTML_PATH}")
            try:
                with open(LOCAL_HTML_PATH, 'r', encoding='utf-8') as file:
                    html_content = file.read()
                print_info("Successfully read local HTML file")
            except Exception as e:
                print_error(f"Error reading local HTML file: {str(e)}")
                return []
        else:
            main_url = "https://aup.rlp.cz/"
            print_info(f"Fetching UUP links from main page: {main_url}")
            
            response = requests.get(main_url)
            if response.status_code != 200:
                print_warning(f"Failed to fetch main AUP page: Status code {response.status_code}")
                return []
            html_content = response.text
        
        soup = BeautifulSoup(html_content, 'html.parser')
        
        uup_links = []
        
        for a_tag in soup.find_all('a', string=lambda s: s and "Platný UUP" in s):
            print(a_tag.get('href'))
            href = a_tag.get('href')
            if href and "uup_" in href:
                time_text = a_tag.parent.get_text()
                
                date_from_match = re.search(r'od (\d{2}\.\d{2}\.\d{4} \d{2}:\d{2} UTC)', time_text)
                date_to_match = re.search(r'do (\d{2}\.\d{2}\.\d{4} \d{2}:\d{2} UTC)', time_text)
                
                from_time = date_from_match.group(1) if date_from_match else "Unknown"
                to_time = date_to_match.group(1) if date_to_match else "Unknown"
                
                uup_id_match = re.search(r'uup_(\d{8})', href)
                if uup_id_match:
                    date_str = uup_id_match.group(1)  # DDMMYYYY
                    
                    uup_links.append({
                        'url': href,
                        'date': date_str,
                        'from_time': from_time,
                        'to_time': to_time,
                    })
        
        print_info(f"Found {len(uup_links)} UUP links")
        
        return uup_links
    
    except Exception as e:
        print_error(f"Error fetching UUP URLs: {str(e)}")
        return []

def fetch_content(url):
    try:
        if USE_LOCAL_FILE and os.path.exists(url):
            print_info(f"Reading content from local file: {url}")
            with open(url, 'r', encoding='utf-8') as file:
                return file.read()
        
        print_info(f"Fetching content from URL: {url}")
        response = requests.get(url)
        if response.status_code != 200:
            print_warning(f"Error fetching content from {url}: Status code {response.status_code}")
            return None
        return response.text
    except Exception as e:
        print_warning(f"Error fetching content from {url}: {str(e)}")
        return None

def preprocess_uup_content(content):
    if not content:
        return None
    
    soup = BeautifulSoup(content, 'html.parser')
    
    ama_section = soup.find(string=lambda text: text and 'C/ Prostory spravovane AMC (AMA)' in text)
    
    if not ama_section:
        print_warning("AMA section not found in UUP content")
        return None
    
    heading_element = ama_section.parent
    if not heading_element:
        print_warning("Could not find AMA section heading element")
        return None
    
    table_row = heading_element.parent
    if not table_row:
        print_warning("Could not find AMA section table row")
        return None
    
    data_rows = []
    next_row = table_row.find_next_sibling('tr')
    
    while next_row and not next_row.find(string=lambda text: text and text.strip().startswith('D/')):
        data_rows.append(next_row)
        next_row = next_row.find_next_sibling('tr')
    
    if not data_rows:
        print_warning("No AMA data rows found in UUP")
        return None
    
    result = str(table_row)
    for row in data_rows:
        result += str(row)
    
    return result

def process_uup_data():
    """
    Main function to process UUP data
    1. Gets UUP URLs from main AUP page
    2. Fetches and processes each UUP
    3. Saves the data to appropriate output files
    """
    print_info("Starting UUP data processing")
    
    ensure_directory(OUTPUT_DIR_UUP)
    
    uup_links = get_uup_urls_from_aup_page()
    
    if not uup_links:
        print_info("No UUP links found")
        return
    
    for uup in uup_links:
        url = uup['url']
        date_str = uup['date']  # DDMMYYYY
        
        print_info(f"Processing UUP for {date_str}, URL: {url}")
        
        content = fetch_content(url)
        if not content:
            print_warning(f"Failed to fetch UUP content for {date_str}")
            continue
        
        ama_section = preprocess_uup_content(content)
        if not ama_section:
            print_warning(f"No AMA section found in UUP for {date_str}")
            continue
        
        ama_spaces = extract_ama_spaces(ama_section)
        if not ama_spaces:
            print_warning(f"No AMA spaces found in UUP for {date_str}")
            continue
        
        print_info(f"Found {len(ama_spaces)} AMA spaces in UUP for {date_str}")
        
        filename = os.path.join(OUTPUT_DIR_UUP, f"uup_{date_str}.uup")
        
        save_uup_data(filename, ama_spaces)
    
    print_info("UUP data processing completed")

def save_uup_data(filename, spaces):
    try:
        directory = os.path.dirname(filename)
        ensure_directory(directory)
        file_exists = os.path.exists(filename)
        
        with open(filename, 'a+', newline='') as file:
            if (not file_exists):
                file.write("space;lower_bound;upper_bound;from_time;to_time;\n")
            
            for space in spaces:
                file.write(f"{space['space']};{space['lower_bound']};{space['upper_bound']};" +
                           f"{space['from_time']};{space['to_time']};\n")
        
        print_info(f"UUP file created: {filename}")
    except Exception as e:
        print_error(f"Error creating UUP file {filename}: {str(e)}")

def process_uup_from_html_file(html_file_path):
    print_info(f"Processing UUP from local file: {html_file_path}")
    
    try:
        with open(html_file_path, 'r', encoding='utf-8') as file:
            content = file.read()
        
        date_match = re.search(r'uup_(\d{8})_', html_file_path)
        date_str = date_match.group(1) if date_match else datetime.now().strftime('%d%m%Y')
        
        ama_section = preprocess_uup_content(content)
        if not ama_section:
            print_warning("No AMA section found in UUP test file")
            return
        
        ama_spaces = extract_ama_spaces(ama_section)
        if not ama_spaces:
            print_warning("No AMA spaces found in UUP test file")
            return
        
        print_info(f"Found {len(ama_spaces)} AMA spaces in test UUP file")
        
        filename = os.path.join(OUTPUT_DIR_UUP, f"uup_{date_str}_test.uup")
        
        save_uup_data(filename, ama_spaces)
        
        print_info(f"Test UUP data saved to {filename}")
    
    except Exception as e:
        print_error(f"Error processing test UUP file: {str(e)}")

if __name__ == "__main__":
    ensure_directory(OUTPUT_DIR_UUP)
    process_uup_data()