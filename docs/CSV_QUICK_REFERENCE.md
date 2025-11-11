# CSV Upload Quick Reference

## Asset Inspection Data

**Columns Required:**
```
Label | Jenis Aset | Pegawai Penempatan | Bahagian | Lokasi Terkini
```

**Example:**
```csv
Label,Jenis Aset,Pegawai Penempatan,Bahagian,Lokasi Terkini
A-001,Komputer,Ahmad bin Ali,IT Department,Office A
A-002,Meja,Ahmad bin Ali,IT Department,Office A
```

**Notes:**
- Pegawai Penempatan = Supervisor **name** (not ID)
- Department and Location must already exist
- Max 10MB per file, 50MB total

---

## Department & Location Setup

**Columns Required:**
```
Bahagian (or Jabatan) | Lokasi Terkini (or Lokasi) | Pegawai Penempatan (optional)
```

**Example:**
```csv
Bahagian,Lokasi Terkini,Pegawai Penempatan
IT Department,Office A,Ahmad bin Ali
IT Department,Lab 2,Siti binti Hassan
HR Department,Office B,Kamal bin Omar
```

**Notes:**
- Creates departments and locations automatically
- Pegawai Penempatan = Supervisor **name** (system will lookup Staff ID)
- Use `overwrite=true` to clear existing data first
- Max 10MB per file, 50MB total

---

## Common Issues

| Problem | Solution |
|---------|----------|
| Supervisor not linked | Ensure name in CSV matches user name in system (partial match works) |
| Department not found | For assets: ensure dept exists. For bulk import: auto-created |
| Location not found | For assets: ensure location exists. For bulk import: auto-created |
| File too large | Split into smaller files (max 10MB each) |

---

## File Format Support

✅ CSV (`.csv`)  
✅ Excel (`.xlsx`, `.xls`)  
✅ UTF-8 encoding recommended  
✅ Case-insensitive column names  
✅ Multiple files per upload
