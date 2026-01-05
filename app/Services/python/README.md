# Bank Statement Parser - Python Dependencies

This directory contains the Python parser for bank statements (MPESA and Equity Bank).

## Prerequisites

### System Dependencies

**Tesseract OCR** (required for OCR fallback on scanned PDFs):

- **Windows**: Download and install from https://github.com/UB-Mannheim/tesseract/wiki
  - Add Tesseract to PATH or set `TESSDATA_PREFIX` environment variable
- **macOS**: `brew install tesseract`
- **Linux (Ubuntu/Debian)**: `sudo apt-get install tesseract-ocr`
- **Linux (CentOS/RHEL)**: `sudo yum install tesseract`

**Poppler** (required for pdf2image):

- **Windows**: Download from https://github.com/oschwartz10612/poppler-windows/releases
  - Extract and add `bin` folder to PATH
- **macOS**: `brew install poppler`
- **Linux (Ubuntu/Debian)**: `sudo apt-get install poppler-utils`
- **Linux (CentOS/RHEL)**: `sudo yum install poppler-utils`

### Python Version

Python 3.9 or higher is required.

## Local Installation

1. **Install Python 3.9+** if not already installed:
   - Windows: Download from https://www.python.org/downloads/
   - macOS: `brew install python3`
   - Linux: Usually pre-installed, or `sudo apt-get install python3 python3-pip`

2. **Navigate to the Python directory**:
   ```bash
   cd app/Services/python
   ```

3. **Create a virtual environment** (recommended):
   ```bash
   # Windows
   python -m venv venv
   venv\Scripts\activate

   # macOS/Linux
   python3 -m venv venv
   source venv/bin/activate
   ```

4. **Install Python dependencies**:
   ```bash
   pip install -r requirements.txt
   ```

5. **Verify installation**:
   ```bash
   python bank_statement_parser.py --help
   ```

## Production Installation

### Option 1: System-wide Python (Recommended for Laravel)

1. **Install Python 3.9+** on the server:
   ```bash
   # Ubuntu/Debian
   sudo apt-get update
   sudo apt-get install python3 python3-pip python3-venv

   # CentOS/RHEL
   sudo yum install python3 python3-pip
   ```

2. **Install system dependencies**:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install tesseract-ocr poppler-utils

   # CentOS/RHEL
   sudo yum install tesseract poppler-utils
   ```

3. **Install Python packages globally** (or use virtualenv):
   ```bash
   cd /path/to/school-management-system2/app/Services/python
   sudo pip3 install -r requirements.txt
   ```

4. **Verify Python is accessible**:
   ```bash
   which python3
   python3 --version
   ```

5. **Update Laravel .env** if needed:
   ```env
   # If Python is not in PATH, specify full path
   PYTHON_PATH=/usr/bin/python3
   ```

### Option 2: Virtual Environment (Isolated)

1. **Create a virtual environment** in production:
   ```bash
   cd /path/to/school-management-system2/app/Services/python
   python3 -m venv venv
   source venv/bin/activate
   pip install -r requirements.txt
   ```

2. **Update Laravel service** to use the virtual environment Python:
   - Modify `BankStatementParser.php` to use `venv/bin/python` instead of `python`

### Option 3: Docker (If using Docker)

Add to your Dockerfile:

```dockerfile
# Install system dependencies
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    tesseract-ocr \
    poppler-utils \
    && rm -rf /var/lib/apt/lists/*

# Install Python dependencies
WORKDIR /var/www/html/app/Services/python
RUN pip3 install --no-cache-dir -r requirements.txt
```

## Testing the Parser

Test the parser with a sample PDF:

```bash
cd app/Services/python
python bank_statement_parser.py /path/to/statement.pdf
```

The output should be JSON array of transactions.

## Troubleshooting

### "pdfplumber not found"
- Ensure Python packages are installed: `pip install -r requirements.txt`

### "Tesseract not found"
- Install Tesseract OCR (see Prerequisites)
- On Windows, ensure Tesseract is in PATH or set `TESSDATA_PREFIX`

### "poppler not found" (for pdf2image)
- Install Poppler utilities (see Prerequisites)
- On Windows, ensure Poppler `bin` folder is in PATH

### "Permission denied" errors
- Use `sudo` for system-wide installation, or use virtual environment
- Ensure Python has write permissions to installation directory

### Laravel can't find Python
- Ensure `python` or `python3` is in system PATH
- Or update `BankStatementParser.php` to use full path: `/usr/bin/python3`

## Notes

- The parser auto-detects MPESA vs Equity Bank statements
- OCR is only used as fallback if PDF text extraction fails
- For production, ensure Python has sufficient memory (PDF parsing can be memory-intensive)

