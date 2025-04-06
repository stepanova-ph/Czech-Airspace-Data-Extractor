import logging
import requests

def print_info(message: str):
    logging.info(message)

def print_warning(message: str):
    logging.warning(message)

def print_error(message: str):
    logging.error(message)

def setup_logging():
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s - %(levelname)s - %(message)s',
        datefmt='%Y-%m-%d %H:%M:%S'
    )

def ensure_directory(directory: str):
    import os
    if not os.path.exists(directory):
        os.makedirs(directory, exist_ok=True)
        print_info(f"Created directory: {directory}")

def fetch_content(url: str) -> str:
    try:
        response = requests.get(url)
        if response.status_code != 200:
            print_warning(f"Error fetching content from {url}: Status code {response.status_code}")
            return None
        return response.text
    except Exception as e:
        print_warning(f"Error fetching content from {url}: {str(e)}")
        return None