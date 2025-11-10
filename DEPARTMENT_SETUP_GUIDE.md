# Department and Location Setup Guide

## Overview

The Department Bulk Import feature allows administrators to set up the master data (departments, locations, and supervisors) that will be used throughout the inspection system. This is the **first step** in setting up your inspection workflow.

## Workflow

1. **Setup Master Data** (This Guide)
   - Import departments and locations
   - Set supervisors for each location
   - Optional: Use overwrite mode to replace all existing data

2. **Upload Assets for Inspection** (Asset Upload)
   - Upload asset inspection files
   - System automatically links assets to departments/locations
   - Supervisors are derived from location master data

## File Format

### Required Columns

| Column | Malay Name | Description | Example |
|--------|-----------|-------------|---------|
| **Bahagian** | Bahagian | Department name | JABATAN KEJURUTERAAN AWAM |
| **Lokasi Terkini** | Lokasi Terkini | Location name | BILIK KULIAH 29 |

### Optional Columns

| Column | Malay Name | Description | Example |
|--------|-----------|-------------|---------|
| **Pegawai Penempatan** | Pegawai Penempatan | Supervisor/PIC for this location | Nazmiah binti Nawi |

### Sample CSV

```csv
Bahagian,Lokasi Terkini,Pegawai Penempatan
JABATAN KEJURUTERAAN AWAM,BILIK KULIAH 29,Nazmiah binti Nawi
JABATAN KEJURUTERAAN AWAM,BILIK KULIAH 42,Joynna Chong
JABATAN KEJURUTERAAN ELEKTRIK,Bilik KJ JKE,Noor Munirah Binti Mohd Noor
```

## Features

### Multi-File Support

- Upload multiple CSV/Excel files simultaneously
- All files are merged and processed together
- Useful when different departments maintain separate files

### Overwrite Mode ⚠️

**When enabled, overwrite mode will:**
- Delete ALL existing departments
- Delete ALL existing locations
- Delete ALL asset inspection data
- Then import the new data

**Use overwrite mode when:**
- Starting fresh with a complete new dataset
- Replacing entire organizational structure
- Cleaning up after testing

**⚠️ Warning:** Overwrite mode is destructive and cannot be undone. Always backup your data before using this feature.

## Usage Instructions

### Step 1: Prepare Your File

1. Create a CSV or Excel file with the required columns
2. Include the optional `Pegawai Penempatan` column if you want to set supervisors
3. First row must contain exact column headers (case-insensitive)
4. Subsequent rows contain your data

### Step 2: Access Bulk Import

1. Navigate to **Departments** page
2. Click **"📁 Bulk Import"** button
3. The bulk import dialog will open

### Step 3: Select Files

1. Click **"Select Files"** or drag files into the upload area
2. Choose one or more CSV/Excel files
3. Review the selected files list
4. You can remove individual files or clear all

### Step 4: Configure Options

**Overwrite Mode (Optional):**
- Check the **"⚠️ Overwrite Mode"** checkbox to clear existing data first
- A confirmation dialog will appear before proceeding
- Leave unchecked to add/update data incrementally

### Step 5: Import

1. Click **"Import X File(s)"** button
2. Wait for processing to complete
3. Review the success message showing:
   - Departments created
   - Locations created
   - Any errors encountered

## Data Behavior

### Department Creation

- Departments are created automatically from the `Bahagian` column
- If a department already exists (by name or acronym), it is reused
- Department names are matched case-insensitively

### Location Creation

- Locations are created per department
- Each unique combination of (Department, Location) creates one location record
- If a location already exists in that department, it is reused

### Supervisor Assignment

- Supervisors are stored at the **location level**
- If `Pegawai Penempatan` is provided, it overwrites the supervisor for that location
- If multiple rows have the same location with different supervisors, the last one wins
- Supervisors are **not** stored per asset; they are inherited from the location

## How Supervisors Work

### Master Data Approach

The system uses a **master data** approach for supervisors:

1. **Setup Phase (This Import):**
   - Supervisors are stored in the `locations` table
   - Each location has one supervisor

2. **Asset Upload Phase:**
   - Asset files may include a `Pegawai Penempatan` column
   - **This column is ignored** during asset import
   - Assets inherit supervisors from their location

3. **Display Phase:**
   - When viewing asset details, the supervisor is looked up from the location
   - This ensures consistency and easy updates

### Benefits

- **Single Source of Truth:** Update supervisor once in Locations, affects all assets
- **Data Integrity:** No duplicate or conflicting supervisor data
- **Easy Maintenance:** Change supervisor for a location without touching asset records

## Example Workflows

### Workflow 1: Initial Setup

```bash
1. Create department_setup.csv with all departments and locations
2. Include Pegawai Penempatan for each location
3. Enable Overwrite Mode (since database is empty or needs reset)
4. Upload the file
5. Verify departments and locations in the system
```

### Workflow 2: Add New Department

```bash
1. Create new_dept.csv with only the new department's locations
2. Do NOT enable Overwrite Mode
3. Upload the file
4. System will add new department and locations without affecting existing data
```

### Workflow 3: Update Supervisors

```bash
1. Export existing data or create CSV with same structure
2. Update Pegawai Penempatan values
3. Do NOT enable Overwrite Mode
4. Upload the file
5. Supervisors will be updated for matching locations
```

## Error Handling

### Common Errors

**"Missing required columns"**
- Ensure your file has `Bahagian` and `Lokasi Terkini` columns
- Check spelling and capitalization (should be case-insensitive but exact)

**"No data rows found"**
- File may be empty or have only headers
- Check that rows have actual data

**"Upload error"**
- File may be corrupted
- Try re-saving as CSV with UTF-8 encoding

### Partial Success

- If some rows fail, the import continues with remaining rows
- Check the error message for details on which rows failed
- Failed rows can be fixed and re-uploaded without Overwrite Mode

## Integration with Asset Inspection

After setting up departments and locations:

1. Navigate to **Asset Upload** page
2. Upload asset inspection files (Label, Jenis Aset, Bahagian, Lokasi Terkini)
3. System will:
   - Match `Bahagian` to departments
   - Match `Lokasi Terkini` to locations
   - Automatically assign supervisors from location master data
4. View assets in **Asset Inspection** page
5. Supervisor column will show the value from the location setup

## Template Files

Download template: `docs/department_setup_template.csv`

```csv
Bahagian,Lokasi Terkini,Pegawai Penempatan
JABATAN KEJURUTERAAN AWAM,BILIK KULIAH 29,Nazmiah binti Nawi
JABATAN KEJURUTERAAN AWAM,BILIK KULIAH 42,Joynna Chong
JABATAN KEJURUTERAAN ELEKTRIK,Bilik KJ JKE,Noor Munirah Binti Mohd Noor
```

## API Reference

**Endpoint:** `POST /department-bulk-import.php`

**Parameters:**
- `files[]`: Array of CSV/Excel files
- `uploaded_by`: User ID (auto-populated)
- `notes`: Optional notes
- `overwrite`: Set to `'true'` to enable overwrite mode

**Response:**
```json
{
  "success": true,
  "departments_created": 5,
  "locations_created": 12,
  "assets_created": 0,
  "total_rows": 12,
  "errors": []
}
```

## Troubleshooting

### Departments not showing up
- Check browser console for errors
- Verify file format matches template
- Ensure columns are spelled correctly

### Supervisors not appearing in Asset Inspection
- Verify supervisors were set during department import
- Check Locations page to confirm supervisor values
- Ensure asset's location name matches exactly with location in database

### Overwrite mode didn't clear data
- Check for database errors in browser console
- Verify you have admin permissions
- Foreign key constraints may prevent deletion if data is in use

## Best Practices

1. **Start with Overwrite Mode** on first import to ensure clean state
2. **Test with small file** before importing hundreds of records
3. **Keep master file** with all departments/locations for future reference
4. **Document supervisors** outside the system as well
5. **Regular backups** before using Overwrite Mode
6. **Incremental updates** use non-overwrite mode to add/update specific records

## See Also

- [Asset Inspection Guide](ASSET_INSPECTION_GUIDE.md)
- [User Import Guide](USER_IMPORT_GUIDE.md)
- [System Overview](SYSTEM_OVERVIEW.md)
