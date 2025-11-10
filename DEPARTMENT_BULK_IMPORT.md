# Department Bulk Import Feature

## Overview
The Departments page now supports **bulk import from multiple CSV/Excel files**. This feature allows you to upload multiple department data files at once, and they will be automatically merged and imported/updated in the system.

## How to Use

1. **Navigate to Departments Page**
   - Go to the Departments section in the application

2. **Click "Bulk Import" Button**
   - Located at the top right of the page, next to "Add Department"

3. **Select Multiple Files**
   - Choose one or more CSV/Excel files (.csv, .xlsx, .xls)
   - All selected files will be merged together

4. **Upload and Process**
   - Click "Import" button
   - The system will:
     - Merge all files together
     - Create new departments
     - Update existing departments (matched by name)
     - Show summary of results

## File Format Requirements

### Required Columns
- **name** (required) - Full department name
- **acronym** (optional) - Department acronym/code
- **total_assets** (optional) - Total number of assets (default: 0)

### Example CSV Format
```csv
name,acronym,total_assets
JABATAN KEJURUTERAAN AWAM,JKA,1000
JABATAN KEJURUTERAAN ELEKTRIK,JKE,1500
JABATAN KEJURUTERAAN MEKANIKAL,JKM,1200
```

### File Types Supported
- CSV (.csv)
- Excel (.xlsx, .xls)

## Features

✅ **Multiple File Upload** - Select and upload multiple files at once
✅ **Automatic Merging** - All files are merged into a single import batch
✅ **Smart Update** - Existing departments are updated, new ones are created
✅ **Error Reporting** - Detailed feedback on any issues during import
✅ **File Management** - Remove individual files before uploading

## Update Logic

- **Matching**: Departments are matched by `name` (exact match)
- **If exists**: Updates `acronym` and `total_assets`
- **If new**: Creates new department with provided data

## Template File

A sample template file is available at:
`docs/department_import_template.csv`

## Example Use Cases

### Scenario 1: Import from Multiple Department Files
You have separate CSV files from different departments:
- `engineering_depts.csv`
- `admin_depts.csv`
- `support_depts.csv`

Simply select all three files, and they'll be merged and imported together.

### Scenario 2: Update Asset Counts
You have updated asset counts from an audit. Upload the CSV with:
- Existing department names
- New `total_assets` values

The system will update the counts automatically.

## Tips

- **Headers are case-insensitive** - "Name" or "name" both work
- **First row must be headers** - Don't include data in row 1
- **Empty rows are skipped** - Blank lines won't cause errors
- **Duplicates within files** - Last occurrence wins
- **Validation** - Department names must not be empty

## Success Response

After import, you'll see:
```
Import completed!

✓ Created: 5 departments
✓ Updated: 3 departments

⚠ Errors: 0
```

## Error Handling

Common errors:
- Missing 'name' column
- Empty department names
- File read errors
- Duplicate names in single file

All errors are reported with specific details so you can fix and re-upload.
