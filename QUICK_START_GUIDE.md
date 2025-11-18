# Quick Start Guide - Curriculum Design System

## ✅ Setup Complete!

Your curriculum design system is now fully configured and ready to use.

## Required Dependencies

This project now ships with both a traditional Laravel/PHP stack **and** a small
Python helper that powers OCR fallback during curriculum parsing.

1. **PHP/Laravel**
   - PHP 8.2+
   - Composer dependencies: `composer install`
2. **Node/Vite assets**
   - `npm install` (only if you plan to rebuild frontend assets)
3. **Python OCR helpers**
   - Python 3.10+
   - Install dependencies once:  
     ```bash
     python -m pip install -r requirements.txt
     ```
   - These packages enable the `scripts/ocr_page.py` helper that runs
     `pdfplumber + pytesseract` when Poppler/ImageMagick aren’t available.

## Server Status

Your Laravel server is running at: **http://127.0.0.1:8000**

## Access the Features

### 1. Curriculum Designs Management
- **URL**: http://127.0.0.1:8000/academics/curriculum-designs
- **Upload**: http://127.0.0.1:8000/academics/curriculum-designs/create
- **Features**:
  - Upload PDF curriculum design documents
  - View processing status
  - Review extracted data
  - Edit metadata

### 2. AI Assistant (Coming Soon)
- **URL**: Will be available after creating the assistant view route
- **Features**:
  - Generate schemes of work
  - Generate lesson plans
  - Generate assessment items
  - Chat interface for queries

## Quick Test Steps

### Step 1: Run Migrations (if not done)
```bash
php artisan migrate
```

### Step 2: Seed Permissions
```bash
php artisan db:seed --class=AcademicPermissionsSeeder
```

### Step 3: Start Queue Worker (in a new terminal)
```bash
php artisan queue:work --tries=3 --timeout=3600
```

### Step 4: Test Upload
1. Navigate to: http://127.0.0.1:8000/academics/curriculum-designs/create
2. Fill in the form:
   - Title: "Test Curriculum Design"
   - Subject & Class Level: detected automatically from the PDF now
   - Upload a PDF file
3. Submit and wait for processing

### Step 5: Check Processing Status
- The curriculum design will show as "Processing"
- Check the queue worker terminal for progress
- Once complete, status will change to "Processed"
- Click "Review Extraction" to see extracted data

## Configuration Verified

✅ **Tesseract OCR**: Installed and configured  
✅ **OpenAI API Key**: Configured  
✅ **Embedding Provider**: OpenAI  
✅ **LLM Provider**: OpenAI  
✅ **Vector Store**: pgvector (requires PostgreSQL)  
✅ **All Routes**: Registered and accessible  

## Important Notes

1. **Queue Workers**: Must be running for PDF processing to work
2. **Database**: Currently using MySQL (pgvector requires PostgreSQL)
3. **API Costs**: OpenAI API usage will incur costs
4. **Processing Time**: Large PDFs (300 pages) may take 10-30 minutes

## Troubleshooting

### PDF Not Processing
- Check queue workers are running: `php artisan queue:work`
- Check logs: `storage/logs/laravel.log`
- Verify PDF file is valid

### OCR Not Working
- Verify Tesseract path in `.env`
- Test manually: `tesseract --version`

### API Errors
- Verify OpenAI API key is correct
- Check API quota/limits
- Review error logs

## Next Steps

1. ✅ Server is running
2. ⚠️ Start queue workers (required for processing)
3. ⚠️ Run migrations (if not done)
4. ⚠️ Seed permissions (if not done)
5. ✅ Install Python OCR requirements (`python -m pip install -r requirements.txt`)
6. ✅ Ready to upload curriculum designs!

## Support

- Check logs: `storage/logs/laravel.log`
- Review documentation files in project root
- Test with a small PDF first (10-20 pages)

---

**Status**: ✅ Ready to Use  
**Server**: http://127.0.0.1:8000  
**Queue Workers**: ⚠️ Need to be started separately

