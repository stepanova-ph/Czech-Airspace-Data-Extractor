from bs4 import BeautifulSoup
from datetime import datetime, timedelta
import os
from utils import fetch_content, print_info, print_warning, print_error, ensure_directory
from config import URL_BASE, OUTPUT_DIR_AUP

def create_aup_url(date):
    return f"{URL_BASE}/aup_{date.strftime('%d%m%Y')}.htm"

def preprocess_aup_content(content):
    if not content:
        return None
    
    soup = BeautifulSoup(content, 'html.parser')
    title_elements = soup.find_all('th', {'class': 'titlex'}, string=lambda s: s and 'C/ Prostory spravovane AMC (AMA)' in s)
    
    if not title_elements:
        print_warning("AMA section not found in the content")
        return None
    
    title_row = title_elements[0].parent
    data_row = title_row.find_next_sibling('tr')
    
    if not data_row:
        print_warning("AMA data table not found")
        return None
    
    return str(title_row) + str(data_row)

def extract_ama_spaces(preprocessed_content):
    if not preprocessed_content:
        return []
    
    soup = BeautifulSoup(preprocessed_content, 'html.parser')
    data_rows = soup.select('tr:has(td.data)')
    ama_spaces = []
    
    for row in data_rows:
        cells = row.select('td.data')
        
        if len(cells) < 8:
            continue
        
        space_info = {
            'space': cells[1].text.strip(),
            'lower_bound': cells[2].text.strip(),
            'upper_bound': cells[3].text.strip(),
            'from_time': cells[4].text.strip(),
            'to_time': cells[5].text.strip(),
        }
        
        ama_spaces.append(space_info)
    
    return ama_spaces

def fetch_aup_spaces_for_date(date):
    url = create_aup_url(date)
    print_info(f"Fetching AUP data for {date.strftime('%Y-%m-%d')} from: {url}")
    
    content = fetch_content(url)
    if not content:
        print_warning(f"No content retrieved for {date.strftime('%Y-%m-%d')}")
        return []
    
    ama_section = preprocess_aup_content(content)
    ama_spaces = extract_ama_spaces(ama_section)
    
    print_info(f"Found {len(ama_spaces)} AMA spaces for {date.strftime('%Y-%m-%d')}")
    return ama_spaces

def create_aup_file(filename, spaces):
    try:
        directory = os.path.dirname(filename)
        ensure_directory(directory)
        
        with open(filename, 'w', newline='') as file:
            file.write("space;lower_bound;upper_bound;from_time;to_time\n")
            
            for space in spaces:
                file.write(f"{space['space']};{space['lower_bound']};{space['upper_bound']};{space['from_time']};{space['to_time']}\n")
        
        print_info(f"AUP file created: {filename}")
    except Exception as e:
        print_error(f"Error creating AUP file {filename}: {str(e)}")

def process_aup_data():
    today = datetime.now()
    tomorrow = today + timedelta(days=1)
    
    ensure_directory(OUTPUT_DIR_AUP)
    
    ama_today = fetch_aup_spaces_for_date(today)
    if ama_today:
        today_filename = os.path.join(OUTPUT_DIR_AUP, f"aup_{today.strftime('%d%m%Y')}.aup")
        create_aup_file(today_filename, ama_today)
    
    try:
        ama_tomorrow = fetch_aup_spaces_for_date(tomorrow)
        if ama_tomorrow:
            tomorrow_filename = os.path.join(OUTPUT_DIR_AUP, f"aup_{tomorrow.strftime('%d%m%Y')}.aup")
            create_aup_file(tomorrow_filename, ama_tomorrow)
    except Exception as e:
        print_warning(f"Error processing tomorrow's data: {str(e)}")
        print_info("Continuing with program execution...")
