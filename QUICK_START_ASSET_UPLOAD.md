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

### 3️⃣ Select Your File
- Click "Select File" button
- Choose your Excel (.xlsx, .xls) or CSV file
- File must contain the 5 required columns listed above

### 4️⃣ Add Notes (Optional)
- Enter a description like "Q4 2025 Asset Check" or "November 2025 Update"
- This helps identify uploads later

### 5️⃣ Upload
- Click "Upload File" button
- Wait for upload to complete
- You'll see a success message with total records uploaded

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
