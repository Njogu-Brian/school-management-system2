# How to Enable PHP Zip Extension

## Error
```
Class "ZipArchive" not found
```

## Solution

The PHP `zip` extension is required to read Excel (.xlsx) files. Follow these steps to enable it:

### For Windows (XAMPP)

1. **Open the PHP configuration file:**
   - Navigate to: `C:\xampp\php\php.ini`
   - Or use the path shown by running: `php --ini`

2. **Find and uncomment the zip extension:**
   - Search for: `;extension=zip`
   - Remove the semicolon (`;`) at the beginning to uncomment it
   - Change from: `;extension=zip`
   - To: `extension=zip`

3. **Save the file**

4. **Restart your web server:**
   - Stop Apache in XAMPP Control Panel
   - Start Apache again

5. **Verify the extension is loaded:**
   ```bash
   php -m | findstr -i zip
   ```
   You should see `zip` in the output.

### For Linux (Ubuntu/Debian)

```bash
sudo apt-get install php-zip
sudo systemctl restart apache2  # or nginx, php-fpm, etc.
```

### For Linux (CentOS/RHEL)

```bash
sudo yum install php-zip
sudo systemctl restart httpd  # or nginx, php-fpm, etc.
```

### Verify Installation

Run this command to check if the extension is loaded:
```bash
php -m | grep -i zip
```

Or check in PHP:
```php
<?php
if (class_exists('ZipArchive')) {
    echo "ZipArchive is available!";
} else {
    echo "ZipArchive is NOT available!";
}
```

## Alternative: Use CSV Format

If you cannot enable the zip extension, you can:
1. Convert your Excel file to CSV format
2. Upload the CSV file instead (CSV files don't require the zip extension)
