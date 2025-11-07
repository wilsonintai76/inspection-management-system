# Asset Inspection Feature

## Overview
This feature allows administrators to upload merged Excel/CSV files containing uninspected assets from external systems, view department-wise summaries, and track inspection progress.

## User Flow

### 1. File Preparation
- Download asset data from external systems (one file per department if needed)
- **Manually merge** all department files into a single Excel or CSV file
- Ensure the file has these **exact column headers**:
  - `Label` - Asset ID (unique identifier, e.g., KPT/PKS/I/08/406)
  - `Jenis Aset` - Asset Type (e.g., ALMARI BUKU BERCERMIN, BANGKU /STOOL)
  - `Pegawai Penempatan` - Supervisor or Person-in-Charge of that location
  - `Bahagian` - Department/Division name (e.g., JABATAN KEJURUTERAAN AWAM)
  - `Lokasi Terkini` - Current Location (e.g., Bilik Stor Jabatan Awam)

### 2. Upload File (Admin Only)
- Navigate to **Asset Inspection** → **Upload New Data**
- Select your merged Excel (.xlsx, .xls) or CSV file
- Add optional notes (e.g., "Q1 2025 Asset Check")
- Click **Upload File**
- System will:
  - Parse the file
  - Validate column headers
  - Map department names to existing departments in the system
  - Store all records with batch tracking

### 3. View Summary
- Navigate to **Asset Inspection**
- Dashboard shows:
  - **Overall Statistics**: Total assets, inspected count, not inspected count, completion percentage
  - **Department Breakdown**: Table showing stats per department with visual progress bars
  - **Asset Details**: Full list of all assets with filters

### 4. Filter & Search
- **Department**: Filter by specific department (or view all)
- **Upload Batch**: View assets from a specific upload session
- **Status**: Show all, only inspected, or only not-inspected assets
- **Search**: Find assets by label, type, officer, or location

### 5. Mark Assets as Inspected
- In the Asset Details table, click **✓ Mark** to mark an asset as inspected
- Click **✗ Unmark** to revert an inspected asset
- Summary statistics update automatically

### 6. Upload History
- View all previous uploads with:
  - Date and time
  - Filename
  - Uploaded by (user name)
  - Total records
  - Notes
- Delete old batches (removes all associated asset records)

## File Format Examples

### CSV Example
```csv
Label,Jenis Aset,Pegawai Penempatan,Bahagian,Lokasi Terkini
KPT/PKS/I/08/406,ALMARI BUKU BERCERMIN,Nazmiah binti Nawi,JABATAN KEJURUTERAAN AWAM,Bilik Stor Jabatan Awam
KPT/PKS/I/89/900,BANGKU /STOOL,MUHAMAD ZAKWAN BIN ZAKARIAH,JABATAN KEJURUTERAAN AWAM,Jabatan Kejuruteraan Awam Aras 1 Bengkel Paip Plumbing
KPT/PKS/I/95/24,KABINET BERLACI,Sufian bin Ahmad,JABATAN KEJURUTERAAN AWAM,MAKMAL KOMPUTER 2 (JKA)
```

### Excel Example
Same structure as CSV, but in .xlsx format with headers in row 1.

## Department Mapping
- The system automatically tries to map `Bahagian` (department name from file) to existing departments
- Mapping is attempted by:
  1. Exact match on department name
  2. Exact match on department acronym
  3. Partial match (contains)
- If no match found, asset is stored with `department_id = NULL`
- You can manually assign departments later if needed

## Permissions
- **Admin Only**: Can upload files, delete batches, view all data
- **Other Roles**: Cannot access this feature (menu item hidden)

## Sample Data
A sample CSV file is provided at: `d:\Inspectable\sample_asset_data.csv`

## API Endpoints

### Upload Asset File
```
POST /asset-uploads.php
Content-Type: multipart/form-data

Form Data:
- file: [Excel or CSV file]
- user_id: [admin user ID]
- notes: [optional notes]
```

### Get Summary Statistics
```
GET /asset-summary.php?action=summary&department_id={optional}

Response: {
  summary: [ { department_id, department_name, total_assets, assets_inspected, assets_not_inspected, percentage_inspected } ],
  overall: { total_assets, assets_inspected, assets_not_inspected, percentage_inspected }
}
```

### Get Asset List
```
GET /asset-summary.php?action=assets&department_id={optional}&batch_id={optional}&inspected={0|1}&search={text}

Response: [ { id, label, jenis_aset, pegawai_penempatan, bahagian, lokasi_terkini, is_inspected, ... } ]
```

### Mark Asset as Inspected
```
PUT /asset-summary.php?id={asset_id}
Content-Type: application/json

Body: {
  is_inspected: 1,
  inspected_by: "{user_id}",
  inspected_date: "2025-11-07",
  notes: "Checked and verified"
}
```

### Get Upload History
```
GET /asset-uploads.php

Response: [ { id, filename, uploaded_by, uploaded_by_name, total_records, upload_date, notes } ]
```

### Delete Upload Batch
```
DELETE /asset-uploads.php?id={batch_id}

Response: { success: true }
```

## Database Schema

### asset_upload_batches
- `id` - Auto-increment primary key
- `uploaded_by` - User ID (FK to users)
- `filename` - Original filename
- `total_records` - Number of asset records in this batch
- `upload_date` - Timestamp
- `notes` - Optional notes

### asset_inspections
- `id` - Auto-increment primary key
- `batch_id` - FK to asset_upload_batches
- `label` - Asset label/ID
- `jenis_aset` - Asset type
- `pegawai_penempatan` - Officer assigned
- `bahagian` - Department name from file
- `lokasi_terkini` - Current location
- `department_id` - Mapped department (FK to departments, nullable)
- `is_inspected` - Boolean (0 = not inspected, 1 = inspected)
- `inspected_date` - Date asset was inspected
- `inspected_by` - User who marked as inspected (FK to users, nullable)
- `notes` - Optional notes
- `created_at` / `updated_at` - Timestamps

## Migration Script
Run `dev_add_asset_tables.php` to create tables if they don't exist:
```
GET http://localhost/inspectable-api/api/dev_add_asset_tables.php
```

## Notes for Future Enhancement
- **Excel library**: For Excel support, install PhpSpreadsheet via Composer:
  ```bash
  composer require phpoffice/phpspreadsheet
  ```
- **Export**: Add export functionality to download summary as Excel/CSV
- **Charts**: Add visual charts for inspection progress
- **Bulk Actions**: Allow bulk mark as inspected
- **Asset History**: Track inspection history over time
