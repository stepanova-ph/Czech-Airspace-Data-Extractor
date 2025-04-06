from utils import setup_logging
import uup_extractor

def web_process_uup():
    setup_logging()
    uup_extractor.process_uup_data()

if __name__ == "__main__":
    web_process_uup()