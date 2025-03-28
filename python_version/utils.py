from datetime import datetime
import sys


def print_info(message):
    print(f"{datetime.now().strftime('%Y-%m-%d %H:%M:%S')} - INFO - {message}", file=sys.stderr)

def print_warning(message):
    print(f"{datetime.now().strftime('%Y-%m-%d %H:%M:%S')} - WARNING - {message}", file=sys.stderr)

def print_error(message):
    print(f"{datetime.now().strftime('%Y-%m-%d %H:%M:%S')} - ERROR - {message}", file=sys.stderr)
