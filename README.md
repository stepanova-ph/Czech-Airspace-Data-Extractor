# FlyIsFun-AMA-Data-Extractor

A utility tool for extracting AMA (Airspace Management Agency) space data from the Czech Republic's AUP (Airspace Use Plan) and UUP (Updated Airspace Use Plan) for the [Fly Is Fun](https://play.google.com/store/apps/details?id=gps.ils.vor.glasscockpit&hl=en) application.

## Description

This tool fetches airspace data from the official [Czech AUP website](https://aup.rlp.cz/), extracts the AMA sections, and converts them into CSV format for easier integration with the Fly Is Fun app. It retrieves AUP data for both the current day and the next day, as well as UUP data when available.

## Features

- Extracts AMA airspace data including space designations, altitude bounds, and activation times
- Processes both AUP (Airspace Use Plan) and UUP (Updated Airspace Use Plan) data
- Generates structured CSV/UUP output files
- Automatically creates data for today and tomorrow (AUP)
- Available in both PHP and Python versions
- Includes error handling and logging
- Command-line options in Python version for selective extraction

## Directory Structure

```
FlyIsFun-AMA-Data-Extractor
├── php_version/                   # PHP implementation
│   ├── aup/
│   │   ├── aup_extractor.php
│   │   └── aup_script.php
│   ├── common.php                 
│   ├── config.php                 
│   ├── utils.php                  
│   └── uup/
│       ├── uup_extractor.php
│       └── uup_script.php
└── python_version/                # Python implementation
    ├── aup/
    │   ├── aup_extractor.py
    │   └── aup_script.py
    ├── common.py
    ├── config.py
    ├── main.py
    ├── utils.py
    └── uup/
        ├── uup_extractor.py
        └── uup_script.py
```

## Usage

### PHP Version

1. Make sure you have PHP installed with DOM extension enabled
2. Run the AUP script:

```bash
php php_version/aup/aup_script.php
```

3. Run the UUP script:

```bash
php php_version/uup/uup_script.php
```

### Python Version

1. Install required dependencies:

```bash
pip install requests beautifulsoup4
```

2. Run the main script (processes both AUP and UUP by default):

```bash
python python_version/main.py
```

3. Run with specific options:

```bash
# Process only AUP data
python python_version/main.py --aup

# Process only UUP data
python python_version/main.py --uup

# Process both (same as default)
python python_version/main.py --all
```

4. Run individual scripts directly:

```bash
# Process AUP data only
python python_version/aup/aup_script.py

# Process UUP data only
python python_version/uup/uup_script.py
```

## Output

### AUP Output

The script creates CSV files in the `lk_aup` directory with naming convention `aup_DDMMYYYY.csv`. Each CSV contains the following fields:
- `space` - Airspace designation
- `lower_bound` - Lower altitude limit
- `upper_bound` - Upper altitude limit
- `from_time` - Activation start time
- `to_time` - Activation end time

### UUP Output

#### PHP Version
When run as a web script, the PHP UUP extractor will provide the UUP data as a downloadable CSV file with the naming convention `uup_DDMMYYYY.csv`. When run from the command line, the script processes the UUP data but doesn't save it to a file by default.

#### Python Version
The Python script creates UUP files in the `lk_uup` directory with naming convention `uup_DDMMYYYY.csv`. Each UUP file contains the same field structure as the AUP files, with an additional semicolon at the end of each line.

## Requirements

### PHP Version
- PHP 5.3 or higher (uses older array syntax for compatibility)
- DOM extension

### Python Version
- Python 3.6 or higher
- requests
- BeautifulSoup4
