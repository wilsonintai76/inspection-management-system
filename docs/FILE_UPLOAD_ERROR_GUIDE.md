# File Upload Error Troubleshooting Guide

## Common File Upload Errors & Solutions

---

## Error Types

### 1. **Missing Required Columns**

**Error Message:**
```
File 'filename.csv': Missing required columns. Expected: 'Bahagian' (or 'Jabatan') and 'Lokasi Terkini' (or 'Lokasi')
```

**Cause:**
- File doesn't have the correct column headers
- Column names are spelled incorrectly
- Using wrong format

**How to Fix:**

**For Standard Department Format:**
Make sure your CSV has these columns:
```csv
Bahagian,Lokasi Terkini,Pegawai Penyelia
```
OR
```csv
Jabatan,Lokasi,Pegawai Penempatan
```

**For Asset Inspection Format:**
Make sure your CSV has ALL these columns:
```csv
Bil,No. Siri Pendaftaran,Maklumat Aset,Lokasi Semasa,Pengguna,Bahagian
```

**Quick Check:**
1. Open file in Excel/Notepad
2. Look at Row 1 (header row)
3. Compare with expected format above
4. Fix spelling/naming
5. Save and re-upload

---

### 2. **Empty File or No Data Rows**

**Error Message:**
```
File 'filename.csv': No data rows found
```

**Cause:**
- File only has header row, no data
- All rows are empty
- File is corrupted

**How to Fix:**
1. Open file and check if it has data rows (Row 2 onwards)
2. Make sure at least one row has department and location data
3. Remove any completely empty rows at the end
4. Save and re-upload

---

### 3. **File Upload Error**

**Error Message:**
```
File 'filename.csv': Upload error
```

**Cause:**
- File too large (over 10MB)
- Network interruption during upload
- Browser issue

**How to Fix:**
1. **Check file size**: Right-click file → Properties
   - If over 10MB, split into smaller files
2. **Try again**: Re-upload the same file
3. **Clear browser cache**: Hard refresh (Ctrl+F5)
4. **Try different browser**: Use Chrome/Edge

---

### 4. **Column Mismatch Between Files**

**Error Message:**
```
Column mismatch: All files must have the same columns. File 'filename.csv' has different columns.
```

**Cause:**
- Uploading multiple files with different formats
- Some files use "Bahagian", others use "Jabatan"
- Different column order

**How to Fix:**
1. Make ALL files use the same format
2. Check header row in each file
3. Standardize column names across all files
4. Either:
   - Upload files with same format together, OR
   - Upload different formats in separate batches

**Example - Make Consistent:**
```
✗ BAD (Different formats):
File 1: Bahagian,Lokasi Terkini,Pegawai Penyelia
File 2: Jabatan,Lokasi,Pengguna

✓ GOOD (Same format):
File 1: Bahagian,Lokasi Terkini,Pegawai Penyelia
File 2: Bahagian,Lokasi Terkini,Pegawai Penyelia
```

---

### 5. **File Type Not Supported**

**Error Message:**
```
Invalid file type for 'filename.xyz'. Only CSV and Excel (.xlsx, .xls) are supported
```

**Cause:**
- File is not CSV or Excel format
- Wrong file extension

**How to Fix:**
1. **Convert to CSV**:
   - Open in Excel
   - File → Save As
   - Choose "CSV (Comma delimited) (*.csv)"
2. **Or Save as Excel**:
   - Save as .xlsx format
3. Re-upload the converted file

---

### 6. **Total File Size Too Large**

**Error Message:**
```
Total file size exceeds maximum allowed (50MB)
```

**Cause:**
- Total size of all selected files is over 50MB

**How to Fix:**
1. **Upload in batches**:
   - Select 5-10 files at a time
   - Upload first batch
   - Then upload next batch
2. **Compress files**:
   - Save as CSV instead of Excel (smaller)
   - Remove unnecessary columns
3. **Split large files**:
   - Break one large file into smaller ones

---

### 7. **Database Errors**

**Error Message:**
```
Row from 'filename.csv': Duplicate entry 'DEPARTMENT_NAME' for key 'name'
```

**Cause:**
- Department already exists in database
- Location already exists

**How to Fix:**
This is actually OK! The system will:
- Skip existing departments
- Skip existing locations
- Only add new ones

If you want to start fresh:
1. Use **"Replace All (Annual Reset)"** mode
2. This clears everything first

---

### 8. **Supervisor Not Found**

**This is NOT an error!**

If supervisor name is not found in users table:
- System stores the name as-is
- You can update supervisor later manually
- Or add user to system first

---

## How to Read Error Messages

Error messages follow this format:
```
File 'filename.csv': [Specific problem]
```
or
```
Row from 'filename.csv': [Problem with specific data]
```

**Example:**
```
File 'JABATAN_MEKANIKAL.csv': Missing required columns. Expected: 'Bahagian' and 'Lokasi Terkini'
```

This tells you:
- **Which file**: JABATAN_MEKANIKAL.csv
- **What's wrong**: Missing columns
- **How to fix**: Add 'Bahagian' and 'Lokasi Terkini' columns

---

## Step-by-Step Error Fixing Process

### Step 1: Read the Error Message
Look at the upload result - errors are listed under the success message

### Step 2: Identify Which File
Error message shows the filename

### Step 3: Check the File Format
Open the problematic file in Excel or Notepad

### Step 4: Compare with Expected Format
Use this guide to see what format is expected

### Step 5: Fix the Issue
- Add missing columns
- Fix column names
- Add data if empty
- Save changes

### Step 6: Re-Upload
- Go back to Bulk Import
- Select the fixed file
- Upload again

---

## Prevention Tips

### ✅ Before Uploading:

**1. Use Templates**
- Check `docs/example-department-import.csv` for correct format
- Copy the template and fill in your data

**2. Validate Headers**
Make sure first row has correct column names:
- `Bahagian` or `Jabatan`
- `Lokasi Terkini` or `Lokasi`
- `Pegawai Penyelia` or `Pegawai Penempatan`

**3. Check for Empty Rows**
Remove any blank rows at the end of file

**4. Ensure Data Exists**
Make sure each row has:
- Department name (required)
- Location name (required)
- Supervisor (optional)

**5. Test with One File First**
- Upload one file first to test
- If successful, upload remaining files

**6. Keep Files Small**
- Under 10MB per file
- Split large files if needed

---

## Quick Fix Checklist

When you get an error, check:

- [ ] **Column names correct?** (Row 1)
- [ ] **Has data rows?** (Row 2+)
- [ ] **File size OK?** (<10MB per file)
- [ ] **Correct file type?** (.csv, .xlsx, .xls)
- [ ] **No empty required columns?** (Bahagian, Lokasi)
- [ ] **Same format for all files?** (if uploading multiple)

---

## Example: Fixing a Real Error

### Scenario:
Uploaded 3 files, got this error:
```
File 'DEPT_2.csv': Missing required columns. Expected: 'Bahagian' and 'Lokasi Terkini'
```

### Solution:

**1. Open DEPT_2.csv**

Found this:
```csv
Department,Location,Supervisor
JABATAN MEKANIKAL,BENGKEL A,Ahmad
```

**2. Problem Identified**
- Using "Department" instead of "Bahagian"
- Using "Location" instead of "Lokasi Terkini"

**3. Fix the Headers**
Change to:
```csv
Bahagian,Lokasi Terkini,Pegawai Penyelia
JABATAN MEKANIKAL,BENGKEL A,Ahmad
```

**4. Save and Re-Upload**
- Save file
- Go to Bulk Import
- Select DEPT_2.csv
- Upload → Success! ✓

---

## Still Having Issues?

### Check These:

1. **Browser Console**
   - Press F12
   - Click "Console" tab
   - Look for red error messages
   - Screenshot and report

2. **File Encoding**
   - Save CSV as "UTF-8"
   - In Excel: Save As → More Options → Tools → Web Options → Encoding → UTF-8

3. **Special Characters**
   - Avoid special characters in department names
   - Use standard letters and numbers
   - Remove: `< > : " / \ | ? *`

4. **Line Endings**
   - For CSV, use standard line endings
   - Re-save in Excel usually fixes this

---

## Error Message Reference

| Error | Severity | Action |
|-------|----------|--------|
| Missing columns | 🔴 Critical | Fix headers, re-upload |
| No data rows | 🔴 Critical | Add data, re-upload |
| Column mismatch | 🟡 Warning | Standardize format |
| File type invalid | 🔴 Critical | Convert to CSV/Excel |
| File too large | 🟡 Warning | Split file or compress |
| Upload error | 🟡 Warning | Retry upload |
| Duplicate entry | 🟢 Info | Ignore (skipped automatically) |
| Supervisor not found | 🟢 Info | Ignore (stored as name) |

**🔴 Critical**: Must fix before upload succeeds  
**🟡 Warning**: May need to adjust approach  
**🟢 Info**: No action needed, informational only  

---

## Summary

Most file upload errors are due to:
1. ❌ Wrong column names
2. ❌ Empty files
3. ❌ Mixing different formats
4. ❌ Wrong file type

**Quick fix for 90% of errors:**
```
1. Check header row (Row 1)
2. Make sure columns are: Bahagian, Lokasi Terkini, Pegawai Penyelia
3. Ensure file has data rows
4. Save and re-upload
```

✅ **Success!** The system will show how many departments and locations were created.
