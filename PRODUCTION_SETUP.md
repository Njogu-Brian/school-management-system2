# Production Server Setup - Python Dependencies

## Quick Fix: Pull Latest Changes

If you haven't pulled the latest changes from GitHub:

```bash
cd /path/to/school-management-system2
git pull origin main
```

Then install dependencies:

```bash
cd app/Services/python
pip install -r requirements.txt
```

## Alternative: Create requirements.txt Manually

If you can't pull from Git, create the file manually:

```bash
cd app/Services/python
cat > requirements.txt << 'EOF'
pdfplumber>=0.10.0
pytesseract>=0.3.10
pdf2image>=1.16.3
Pillow>=10.0.0
EOF
```

Then install:

```bash
pip install -r requirements.txt
```

## Full Production Setup

1. **Install system dependencies** (if not already installed):

```bash
# For CentOS/RHEL (based on your server)
sudo yum install python3 python3-pip tesseract poppler-utils

# Or for Ubuntu/Debian
sudo apt-get update
sudo apt-get install python3 python3-pip tesseract-ocr poppler-utils
```

2. **Install Python packages**:

```bash
cd /path/to/school-management-system2/app/Services/python
pip3 install -r requirements.txt
```

3. **Verify installation**:

```bash
python3 --version
python3 bank_statement_parser.py --help
```

## Troubleshooting

### If pip install fails with permission errors:

Use `--user` flag (already defaulting to this):
```bash
pip install --user -r requirements.txt
```

### If you need sudo access:

```bash
sudo pip3 install -r requirements.txt
```

### Verify the file exists:

```bash
ls -la app/Services/python/requirements.txt
cat app/Services/python/requirements.txt
```

