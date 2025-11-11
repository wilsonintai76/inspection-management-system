# CSV Format Guide for Data Import

This document specifies the CSV file formats required for importing data into the Inspection Management System.

## Asset Inspection Data Upload

**Endpoint:** `POST /api/asset-uploads.php`

### Required Columns

| Column Name | Description | Example | Notes |
|------------|-------------|---------|-------|
| **Label** | Unique asset identifier/label | `A-001`, `COMP-123` | Required, must be unique |
| **Jenis Aset** | Asset type/category | `Komputer`, `Meja`, `Kerusi` | Required |
| **Pegawai Penempatan** | Supervisor name | `Ahmad bin Ali` | Required, should match existing user name |
| **Bahagian** | Department name | `IT Department`, `HR` | Required, must match existing department |
| **Lokasi Terkini** | Current location name | `Office A`, `Lab 2` | Required, must match existing location |

### Format Requirements

- **File Types:** CSV (`.csv`), Excel (`.xlsx`, `.xls`)
- **Encoding:** UTF-8 recommended
- **Header Row:** First row must contain column names (case-insensitive)
- **Max File Size:** 10 MB per file, 50 MB total per upload batch
- **Column Order:** Flexible - columns can be in any order as long as all required columns are present

### Example CSV

```csv
Label,Jenis Aset,Pegawai Penempatan,Bahagian,Lokasi Terkini
A-001,Komputer,Ahmad bin Ali,IT Department,Office A
A-002,Meja,Ahmad bin Ali,IT Department,Office A
A-003,Kerusi,Siti binti Hassan,HR Department,Office B
```

### Important Notes

1. **Supervisor Name Matching:**
   - The system will attempt to match the supervisor name with existing users
   - If a match is found, the user's Staff ID will be used
   - If no match is found, the name will be stored as-is for reference
   - Partial name matching is supported (e.g., "Ahmad" may match "Ahmad bin Ali")

2. **Department Matching:**
   - Department names must exactly match existing departments
   - Both full name and acronym are checked
   - Partial matching is supported (e.g., "IT" may match "IT Department")

3. **Location Matching:**
   - Location names must match existing locations within the specified department
   - Locations are department-specific

4. **Data Validation:**
   - All required columns must be present
   - Empty rows (where Label, Department, or Location are blank) are skipped
   - Multiple files can be uploaded simultaneously; all must have the same column structure

---

## Department & Location Bulk Import

**Endpoint:** `POST /api/department-bulk-import.php`

### Required Columns

| Column Name | Alias | Description | Example | Notes |
|------------|-------|-------------|---------|-------|
| **Bahagian** | Jabatan | Department name | `IT Department`, `HR` | Required |
| **Lokasi Terkini** | Lokasi | Location name | `Office A`, `Lab 2` | Required |
| **Pegawai Penempatan** | Pegawai Penyelia | Supervisor name | `Ahmad bin Ali` | Optional |

### Format Requirements

- **File Types:** CSV (`.csv`), Excel (`.xlsx`, `.xls`)
- **Encoding:** UTF-8 recommended
- **Header Row:** First row must contain column names (case-insensitive)
- **Max File Size:** 10 MB per file, 50 MB total per upload batch
- **Column Aliases:** System accepts both primary and alias column names
- **Overwrite Mode:** Set `overwrite=true` in request to clear all existing data before import

### Example CSV

```csv
Bahagian,Lokasi Terkini,Pegawai Penempatan
IT Department,Office A,Ahmad bin Ali
IT Department,Lab 2,Siti binti Hassan
HR Department,Office B,Kamal bin Omar
Finance,Office C,
```

### Important Notes

1. **Supervisor Lookup:**
   - System attempts to match supervisor name with existing users in the database
   - If found, user's Staff ID is stored in the location record
   - If not found, the name is stored as-is for later reference
   - Partial name matching with LIKE query is used

2. **Department Creation:**
   - If department doesn't exist, it will be created automatically
   - Department acronym can be left empty on creation

3. **Location Creation:**
   - Locations are created under their specified department
   - If location already exists for that department, it's reused (not duplicated)

4. **Overwrite Mode:**
   - Clears all departments, locations, and related inspection data
   - Use with caution - this is a destructive operation
   - Useful for fresh imports or testing

5. **Data Integrity:**
   - Rows with empty department or location names are skipped
   - Supervisor field is optional
   - Multiple files are processed sequentially

---

## Upload Best Practices

### File Preparation

1. **Use UTF-8 Encoding:** Ensures proper handling of special characters and Malay text
2. **Check Column Names:** Verify header row matches required column names (case-insensitive)
3. **Remove Empty Rows:** Clean up any trailing blank rows
4. **Validate Data:** Ensure all required fields are filled
5. **Use Consistent Names:** Keep department and location names consistent across files

### Error Handling

- If upload fails, check the error message for specific issues:
  - Missing required columns
  - Invalid file type or size
  - Column mismatch between multiple files
  - Database constraint violations

### Testing Approach

1. **Start Small:** Test with a small CSV file first (10-20 rows)
2. **Verify Mappings:** Check that departments and locations are created/matched correctly
3. **Check Supervisors:** Verify supervisor names are matched to correct users
4. **Full Import:** Once validated, proceed with full dataset

### Troubleshooting

**Problem:** Supervisor not linked correctly
- **Solution:** Ensure supervisor name in CSV matches name in users table (partial match supported)

**Problem:** Department not found
- **Solution:** For asset uploads, ensure department exists. For bulk import, department will be created automatically.

**Problem:** Location not found
- **Solution:** For asset uploads, ensure location exists under the correct department. For bulk import, location will be created automatically.

**Problem:** File too large
- **Solution:** Split large files into smaller batches (max 10MB per file, 50MB total)

---

## API Request Examples

### Asset Upload (curl)

```bash
curl -X POST http://localhost/api/asset-uploads.php \
  -F "files=@assets.csv" \
  -F "user_id=1" \
  -F "overwrite=false"
```

### Department Bulk Import (curl)

```bash
curl -X POST http://localhost/api/department-bulk-import.php \
  -F "files[]=@departments.csv" \
  -F "overwrite=false"
```

### Multiple File Upload

```bash
curl -X POST http://localhost/api/asset-uploads.php \
  -F "files[]=@building-a.csv" \
  -F "files[]=@building-b.csv" \
  -F "user_id=1" \
  -F "overwrite=false"
```

---

## Database Schema Reference

### Relevant Tables

- **departments:** Stores department master data
- **locations:** Stores location master data (linked to departments)
- **users:** User accounts with staff_id field for supervisor matching
- **asset_inspections:** Individual inspection records
- **asset_upload_batches:** Tracks batch uploads

### Supervisor Field in Locations

The `supervisor` field in the `locations` table stores the Staff ID of the responsible person. During import:

1. System searches users table by name (LIKE match)
2. If found, stores the matched user's `staff_id`
3. If not found, stores the name as-is for reference

This allows supervisors to be assigned even if not yet registered as users in the system.
