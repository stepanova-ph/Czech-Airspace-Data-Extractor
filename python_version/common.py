from bs4 import BeautifulSoup

from utils import print_warning

def extract_ama_spaces(preprocessed_content):
    """
    Extract AMA spaces from the preprocessed UUP content
    Similar to AUP extraction but specific to UUP format
    """
    if not preprocessed_content:
        return []
    
    soup = BeautifulSoup(preprocessed_content, 'html.parser')
    ama_spaces = []
    
    for row in soup.find_all('tr'):
        if row.find('th'):
            continue
        
        cells = row.find_all('td')
        if len(cells) < 6:
            continue
        
        first_cell_text = cells[0].text.strip() if cells[0].text else ""
        if not first_cell_text or not first_cell_text[0].isdigit():
            continue
        
        if len(cells) >= 6:
            try:
                space_info = {
                    'space': cells[1].text.strip(),
                    'lower_bound': cells[2].text.strip(),
                    'upper_bound': cells[3].text.strip(),
                    'from_time': cells[4].text.strip(),
                    'to_time': cells[5].text.strip(),
                }
                ama_spaces.append(space_info)
            except IndexError:
                print_warning(f"Error parsing row: {row}")
    
    return ama_spaces