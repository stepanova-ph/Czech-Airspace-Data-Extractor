from utils import setup_logging
import aup_extractor

def web_process_aup():
    setup_logging()
    aup_extractor.process_aup_data()

if __name__ == "__main__":
    web_process_aup()