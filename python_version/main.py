import argparse
from datetime import datetime
from utils import setup_logging, print_info
from aup import aup_extractor
# from uup import uup_extractor

def main():
    setup_logging()
    
    parser = argparse.ArgumentParser(description='Extract airspace data from AUP and UUP')
    parser.add_argument('--aup', action='store_true', help='Extract AUP data')
    # parser.add_argument('--uup', action='store_true', help='Extract UUP data')
    # parser.add_argument('--all', action='store_true', help='Extract both AUP and UUP data')
    
    args = parser.parse_args()
    
    if not (args.aup): # or args.uup or args.all):
        # args.all = True
        args.aup = True
    
    print_info(f"Starting airspace data extraction at {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    
    if args.aup: # or args.all:
        print_info("Processing AUP data...")
        aup_extractor.process_aup_data()
    
    # if args.uup or args.all:
    #     print_info("Processing UUP data...")
    #     uup_extractor.process_uup_data()
    
    print_info(f"Airspace data extraction completed at {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")

if __name__ == "__main__":
    main()