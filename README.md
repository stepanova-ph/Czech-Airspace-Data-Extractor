# FlyIsFun-AMA-Data-Extractor
A utility tool for extracting AMA (Airspace Management Agency) space data from the Czech Republic's AUP (Airspace Use Plan) for the [Fly Is Fun](https://play.google.com/store/apps/details?id=gps.ils.vor.glasscockpit&hl=en) application.

## Description
This tool fetches airspace data from the official [Czech AUP website](https://aup.rlp.cz/), extracts the AMA sections, and converts them into CSV format for easier integration with the Fly Is Fun app. It retrieves data for both the current day and the next day.

## Features
- Extracts AMA airspace data including space designations, altitude bounds, and activation times
- Generates structured CSV output files
- Automatically creates data for today and tomorrow
- Available in both PHP and Python versions
- Includes error handling and logging

## Directory Structure
```
FlyIsFun-AMA-Data-Extractor
├── ama_data/                  # Output directory for CSV files
├── python_version/            # Python implementation
│   ├── script.py              # Main Python script
│   └── utils.py               # Python utility functions
├── script.php                 # Main PHP script
└── utils.php                  # PHP utility functions
```

## Usage
### PHP Version
1. Make sure you have PHP installed with DOM extension enabled
2. Run the script:

```bash
php script.php
```

### Python Version
1. Install required dependencies:
```bash
pip install requests beautifulsoup4
```

2. Run the script:
```bash
python python_version/script.py
```

## Output
The script creates CSV files in the `ama_data` directory with naming convention `ama_DDMMYYYY.csv`. Each CSV contains the following fields:
- `space` - Airspace designation
- `lower_bound` - Lower altitude limit
- `upper_bound` - Upper altitude limit
- `from_time` - Activation start time
- `to_time` - Activation end time

## Requirements
### PHP Version
- PHP 5.3 or higher
- DOM extension

### Python Version
- Python 3.6 or higher
- requests
- BeautifulSoup4
