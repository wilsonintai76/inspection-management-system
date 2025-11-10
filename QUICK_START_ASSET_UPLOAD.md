# Quick Start Guide - Asset Inspection Upload

## Column Definitions (IMPORTANT!)

Your Excel/CSV file **MUST** have these exact column names in the first row:

| Column Name | Description | Example Data |
|------------|-------------|--------------|
| **Label** | Unique Asset ID | KPT/PKS/I/08/406 |
| **Jenis Aset** | Asset Type/Description | ALMARI BUKU BERCERMIN |
| **Pegawai Penempatan** | Supervisor/PIC Name | Nazmiah binti Nawi |
| **Bahagian** | Department Name | JABATAN KEJURUTERAAN AWAM |
| **Lokasi Terkini** | Current Location | Bilik Stor Jabatan Awam |

## Sample Data Format

```
Label               | Jenis Aset                           | Pegawai Penempatan         | Bahagian                    | Lokasi Terkini
--------------------|--------------------------------------|----------------------------|-----------------------------|---------------------------------
KPT/PKS/I/08/406   | ALMARI BUKU BERCERMIN               | Nazmiah binti Nawi         | JABATAN KEJURUTERAAN AWAM  | Bilik Stor Jabatan Awam
KPT/PKS/I/89/900   | BANGKU /STOOL                       | MUHAMAD ZAKWAN BIN ZAKARIAH| JABATAN KEJURUTERAAN AWAM  | Jabatan Kejuruteraan... Plumbing
KPT/PKS/I/95/24    | KABINET BERLACI                     | Sufian bin Ahmad           | JABATAN KEJURUTERAAN AWAM  | MAKMAL KOMPUTER 2 (JKA)
```

## Step-by-Step Upload Process

### 1️⃣ Login as Admin
- Use your admin credentials (e.g., Staff ID: 2001)

### 2️⃣ Navigate to Asset Inspection
- Look for the 📦 icon in the sidebar
- Click "Asset Inspection"
- Click "📤 Upload New Data" button

### 3️⃣ Select Your File(s)
- Click "Select Files" button
- **Single File**: Choose one Excel (.xlsx, .xls) or CSV file
- **Multiple Files**: Select multiple department files at once (Ctrl+Click or Shift+Click)
- All files must contain the 5 required columns listed above
- All files will be automatically merged into one batch

**Multi-File Upload Benefits:**
- ✅ No manual merging required
- ✅ Select files from different departments simultaneously
- ✅ System validates all files have matching columns
- ✅ All records combined into single batch upload
- ✅ Can add more files before uploading or remove individual files

### 4️⃣ Add Notes (Optional)
- Enter a description like "Q4 2025 Asset Check" or "November 2025 Update"
- This helps identify uploads later

### 5️⃣ Upload
- Click "Upload X File(s)" button (X = number of selected files)
- Wait for upload to complete
- System will merge all files automatically
- You'll see a success message with:
  - Number of files processed
  - Total records uploaded from all files

### 6️⃣ View Results
- Click "View Summary" or navigate back to Asset Inspection
- See department-wise statistics
- Browse all uploaded assets
- Mark assets as inspected by clicking ✓ Mark button

## Department Mapping

The system will try to match your `Bahagian` (department name) to existing departments in the system:

**Your File Says** → **Maps To**
- "JABATAN KEJURUTERAAN AWAM" → Department: "Information Technology" (if name/acronym matches)
- "ICT" → Department: "Information Technology"
- "Finance" → Department: "Finance"

💡 **Tip**: If a department doesn't match, the asset is still uploaded but won't be assigned to a department. You can check the mapping in the summary view.

## What You'll See in the Summary

### Overall Statistics Card
```
┌─────────────────────────────────────────┐
│  Overall Statistics                     │
├─────────────────────────────────────────┤
│  Total Assets: 100                      │
│  Inspected: 45                          │
│  Not Inspected: 55                      │
│  Completion Rate: 45%                   │
└─────────────────────────────────────────┘
```

### Department Breakdown Table
```
Department              | Total | Inspected | Not Inspected | Percentage
------------------------|-------|-----------|---------------|------------
Information Technology  |   50  |    30     |      20       | [████████░░] 60%
Finance                 |   30  |    10     |      20       | [███░░░░░░░] 33%
HR                      |   20  |     5     |      15       | [██░░░░░░░░] 25%
```

### Asset Details List
- Searchable and filterable table
- Shows all asset information
- Click ✓ Mark to mark as inspected
- Click ✗ Unmark to revert

## Multi-File Upload Feature (NEW!)

### Overview
Upload multiple CSV/Excel files simultaneously and the system will automatically merge them into a single batch.

### How It Works

**Before (Old Way):**
1. Receive 5 department files separately
2. Manually open each file in Excel
3. Copy and paste all data into one file
4. Save merged file
5. Upload to system

**Now (New Way):**
1. Receive 5 department files separately
2. Select all 5 files at once in upload screen
3. System automatically validates and merges them
4. Upload all at once as single batch ✨

### Features

**Multi-File Selection:**
- Select multiple files using Ctrl+Click or Shift+Click
- Add more files after initial selection
- Remove individual files before upload
- Clear all selections with one click

**Automatic Validation:**
- ✅ Checks all files have required columns
- ✅ Ensures all files have matching column structure
- ✅ Reports which file has errors (if any)
- ✅ Prevents upload if validation fails

**Smart Merging:**
- Combines all records into single database batch
- Preserves all data from all files
- Creates audit trail showing merged filenames
- Single batch ID for entire upload

### Example Workflow

**Scenario**: Asset Officer receives files from 4 departments
- `JKA_Assets_Q4.xlsx` (150 records)
- `JKE_Assets_Q4.csv` (200 records)
- `JADUAL_Assets_Q4.xlsx` (180 records)
- `JPH_Assets_Q4.csv` (120 records)

**Steps**:
1. Click "Select Files"
2. Ctrl+Click all 4 files
3. System shows: "Selected Files (4)"
4. Add optional notes: "Q4 2025 - All Departments"
5. Click "Upload 4 Files"
6. System merges and uploads 650 total records
7. Success message: "Successfully merged 4 files with 650 asset records"

### Batch History Display
When multiple files are merged, the batch history shows:
- `4 files: JKA_Assets_Q4.xlsx, JKE_Assets_Q4.csv, JADUAL_Ass...`
- Filenames truncated if too long to fit

## Filters Available

1. **Department**: Show only specific department assets
2. **Upload Batch**: View assets from a specific upload session
3. **Status**: 
   - All Assets
   - Not Inspected only
   - Inspected only
4. **Search Box**: Search by asset ID, type, officer, or location

## Upload History

View all previous uploads:
- Date and time uploaded
- Filename
- Who uploaded it
- Number of records
- Notes
- Delete button (🗑️) to remove old batches

## Testing Your File

A sample file is available at:
```
d:\Inspectable\sample_asset_data.csv
```

This file contains real example data matching your format. Use it to test the upload feature before using your actual data.

## Common Issues

### ❌ "Missing required column: Label"
**Problem**: Your file doesn't have a column named "Label"
**Solution**: Check your column headers match exactly: `Label, Jenis Aset, Pegawai Penempatan, Bahagian, Lokasi Terkini`

### ❌ "Column mismatch: All files must have the same columns"
**Problem**: When uploading multiple files, they have different column structures
**Solution**: 
- All files must have identical column headers
- Check spelling and order of columns
- Example: File 1 has "Label, Jenis Aset..." but File 2 has "Asset ID, Type..."
- Ensure all files follow the exact format

### ❌ "Error in 'filename.csv': [error message]"
**Problem**: One of the uploaded files has an issue
**Solution**: 
- Check the specific filename mentioned
- Fix the file individually
- Remove problematic file from selection and upload others first

### ❌ "Excel support not installed"
**Problem**: PhpSpreadsheet library not installed
**Solution**: 
- Use CSV format instead, OR
- Install PhpSpreadsheet: `composer require phpoffice/phpspreadsheet`

### ❌ No department assigned to assets
**Problem**: Department names in file don't match system departments
**Solution**: 
- Check that "Bahagian" values match department names in Settings → Departments
- Assets still upload, just without department assignment

## Need Help?

- Check `ASSET_INSPECTION_GUIDE.md` for detailed documentation
- Check `EXCEL_SUPPORT_SETUP.md` for Excel installation guide
- All APIs are deployed and ready at `http://localhost/inspectable-api/api/`

## Ready to Start?

1. Prepare your file with the 5 required columns
2. Login as Admin
3. Go to Asset Inspection → Upload New Data
4. Upload and view your summary!

---
*Feature built November 2025 - Fully functional for CSV uploads*
