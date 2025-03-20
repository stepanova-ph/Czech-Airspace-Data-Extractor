#!/usr/bin/env python

from datetime import datetime, timedelta
from typing import Dict, List, Optional, Any
import requests
from bs4 import BeautifulSoup
import re
import os

from utils import print_error, print_info, print_warning

URL_BASE = "https://aup.rlp.cz/data"

def create_url(date: datetime.date) -> str:
    return f"{URL_BASE}/aup_{date.strftime('%d%m%Y')}.htm"

def fetch_content(url: str) -> Optional[str]:
    try:
        response: requests.Response = requests.get(url)
        response.raise_for_status()
        return response.text
    except requests.exceptions.RequestException as e:
        print_warning(f"Error fetching content from {url}: {e}")
        return None

def preprocess_content(content: Optional[str]) -> Optional[str]:
    if not content:
        return None
    
    soup: BeautifulSoup = BeautifulSoup(content, 'html.parser')
    
    ama_title: Optional[Any] = soup.find('th', class_='titlex', string=re.compile(r'C/ Prostory spravovane AMC \(AMA\)'))
    
    if not ama_title:
        print_warning("AMA section not found in the content")
        return None
    
    title_row: Any = ama_title.parent
    data_row: Optional[Any] = title_row.find_next_sibling('tr')
    
    if not data_row:
        print_warning("AMA data table not found")
        return None
    
    return str(title_row) + str(data_row)

def extract_AMA_space(preprocessed_content: Optional[str]) -> List[Dict[str, str]]:
    if not preprocessed_content:
        return []
    
    soup: BeautifulSoup = BeautifulSoup(preprocessed_content, 'html.parser')
    data_rows: List[Any] = soup.find_all('tr')
    data_rows = [row for row in data_rows if row.find('td', class_='data')]
    
    ama_spaces: List[Dict[str, str]] = []
    
    for row in data_rows:
        cells: List[Any] = row.find_all('td', class_='data')
        
        if len(cells) < 8:
            continue
        
        space_info: Dict[str, str] = {
            'space': cells[1].text.strip(),
            'lower_bound': cells[2].text.strip(),
            'upper_bound': cells[3].text.strip(),
            'from_time': cells[4].text.strip(),
            'to_time': cells[5].text.strip(),
        }
        
        ama_spaces.append(space_info)
    
    return ama_spaces

def fetch_spaces_for_date(date: datetime.date) -> List[Dict[str, str]]:
    url: str = create_url(date)
    print_info(f"Fetching data for {date.strftime('%Y-%m-%d')} from: {url}")
    
    content: Optional[str] = fetch_content(url)
    if not content:
        print_warning(f"No content retrieved for {date.strftime('%Y-%m-%d')}")
        return []
    
    ama_section: Optional[str] = preprocess_content(content)
    ama_spaces: List[Dict[str, str]] = extract_AMA_space(ama_section)
    
    print_info(f"Found {len(ama_spaces)} AMA spaces for {date.strftime('%Y-%m-%d')}")
    return ama_spaces

def create_csv(filename: str, spaces: List[Dict[str, str]]) -> None:
    try:
        os.makedirs(os.path.dirname(filename) or '.', exist_ok=True)
        
        with open(filename, "w") as file:
            file.write("space;lower_bound;upper_bound;from_time;to_time\n")
            
            for space in spaces:
                file.write(f"{space['space']};{space['lower_bound']};{space['upper_bound']};{space['from_time']};{space['to_time']}\n")
        
        print_info(f"CSV file created: {filename}")
    except Exception as e:
        print_error(f"Error creating CSV file {filename}: {e}")

def main() -> None:
    today: datetime.date = datetime.today().date()
    tomorrow: datetime.date = today + timedelta(days=1)
    
    output_dir: str = "ama_data"
    os.makedirs(output_dir, exist_ok=True)
    
    ama_today: List[Dict[str, str]] = fetch_spaces_for_date(today)
    if ama_today:
        today_filename: str = os.path.join(output_dir, f"ama_{today.strftime('%d%m%Y')}.csv")
        create_csv(today_filename, ama_today)
    
    try:
        ama_tomorrow: List[Dict[str, str]] = fetch_spaces_for_date(tomorrow)
        if ama_tomorrow:
            tomorrow_filename: str = os.path.join(output_dir, f"ama_{tomorrow.strftime('%d%m%Y')}.csv")
            create_csv(tomorrow_filename, ama_tomorrow)
    except Exception as e:
        print_warning(f"Error processing tomorrow's data: {e}")
        print_info("Continuing with program execution...")

if __name__ == '__main__':
    main()