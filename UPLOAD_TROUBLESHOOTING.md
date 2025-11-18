# 🔍 Upload Troubleshooting Guide

## Problem: "I UPLOADED A FILE NOTHING HAPPENED"

### Most Common Issue: Queue Workers Not Running ⚠️

**The file uploads successfully, but processing requires queue workers to be running!**

### Quick Fix:

1. **Open a NEW terminal window** (keep your server running in the first one)

2. **Start the queue worker:**
   ```bash
   php artisan queue:work --tries=3 --timeout=3600
   ```

3. **Re-upload your file** - it should process immediately!

---

## Step-by-Step Diagnosis

### Step 1: Check if File Was Uploaded

Run this command:
```bash
php check_upload_status.php
```

**If you see:**
- ✅ "Latest Uploads: ID: X" → File uploaded, check Step 2
- ❌ "No curriculum designs found" → Upload failed, check Step 3

### Step 2: Check Queue Status

**If file exists but status is "processing":**

```bash
php artisan queue:work --once
```

**If you see jobs processing:** Queue workers need to run continuously!

**If no jobs found:** The job may have already processed or failed.

### Step 3: Check for Errors

**Check logs:**
```bash
Get-Content storage\logs\laravel.log -Tail 50
```

**Common errors:**
- Permission denied → Check file permissions
- Validation error → Check form fields
- Missing method → Check controller code

---

## Common Issues & Solutions

### Issue 1: Queue Workers Not Running

**Symptom:** File uploads, status stays "processing", nothing happens

**Solution:**
```bash
# Start queue worker (run in separate terminal)
php artisan queue:work --tries=3 --timeout=3600
```

**For production, use supervisor or similar to keep workers running.**

### Issue 2: Permission Denied

**Symptom:** Error message about permissions

**Solution:**
```bash
# Check storage permissions
php artisan storage:link

# Ensure storage is writable
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Issue 3: Validation Errors

**Symptom:** Form redirects back with errors

**Check:**
- File is PDF format
- File size < 50MB (default)
- Title is filled
- Subject selected (if required)

### Issue 4: Missing Permissions

**Symptom:** 403 Forbidden or "Unauthorized"

**Solution:**
```bash
# Seed permissions
php artisan db:seed --class=AcademicPermissionsSeeder

# Assign permission to your user
php artisan tinker
>>> $user = User::find(1);
>>> $user->givePermissionTo('curriculum_designs.create');
```

### Issue 5: Job Fails Silently

**Check failed jobs:**
```bash
php artisan queue:failed
```

**Retry failed job:**
```bash
php artisan queue:retry {job-id}
```

---

## Complete Setup Checklist

- [ ] ✅ Migrations run: `php artisan migrate`
- [ ] ✅ Permissions seeded: `php artisan db:seed --class=AcademicPermissionsSeeder`
- [ ] ✅ Storage linked: `php artisan storage:link`
- [ ] ✅ Queue worker running: `php artisan queue:work`
- [ ] ✅ Server running: `php artisan serve`
- [ ] ✅ User has `curriculum_designs.create` permission
- [ ] ✅ `.env` configured (Tesseract, OpenAI, etc.)

---

## Testing the Upload

1. **Start server** (Terminal 1):
   ```bash
   php artisan serve
   ```

2. **Start queue worker** (Terminal 2):
   ```bash
   php artisan queue:work --tries=3 --timeout=3600
   ```

3. **Upload file** via browser:
   - Go to: http://127.0.0.1:8000/academics/curriculum-designs/create
   - Fill form and upload PDF
   - Watch Terminal 2 for processing output

4. **Check status:**
   ```bash
   php check_upload_status.php
   ```

---

## Still Not Working?

1. **Check browser console** for JavaScript errors
2. **Check network tab** for failed requests
3. **Check Laravel logs:** `storage/logs/laravel.log`
4. **Verify route exists:** `php artisan route:list --name=curriculum`
5. **Test with small PDF first** (1-2 pages)

---

## Need More Help?

- Check `QUICK_START_GUIDE.md` for setup steps
- Review `storage/logs/laravel.log` for detailed errors
- Verify all environment variables in `.env`

