# Asset Upload CSV Format Guide

## Supported Formats

The system now supports **TWO** CSV formats for asset uploads:

---

## Format 1: Standard Format (Simple)

### Column Headers:
```csv
Label,Jenis Aset,Pegawai Penempatan,Bahagian,Lokasi Terkini
```

### Example:
```csv
Label,Jenis Aset,Pegawai Penempatan,Bahagian,Lokasi Terkini
KPT/PKS/H/08/157,HALFCUT ON STAND,AHMAD FAIRUZ BIN MOHAMED,JABATAN KEJURUTERAAN MEKANIKAL,BENGKEL AUTOMOTIF
KPT/PKS/H/16/38,TYRE CHANGER MACHINE,AHMAD FAIRUZ BIN MOHAMED,JABATAN KEJURUTERAAN MEKANIKAL,BENGKEL AUTOMOTIF
```

### Columns:
- **Label**: Asset registration number (unique identifier)
- **Jenis Aset**: Asset type/name
- **Pegawai Penempatan**: Supervisor/User name
- **Bahagian**: Department name
- **Lokasi Terkini**: Location name

---

## Format 2: Multi-Line Format (Government Standard)

### Column Headers:
```csv
Bil,No. Siri Pendaftaran,Maklumat Aset,Lokasi Semasa,Pengguna,Bahagian
```

### Format Structure:
Each asset uses **2 rows**:
- **Row 1**: Contains registration number, category, location, user, department
- **Row 2**: Contains detailed asset description (system extracts the last part after "/")

### Example:
```csv
Bil,No. Siri Pendaftaran,Maklumat Aset,Lokasi Semasa,Pengguna,Bahagian
1,KPT/PKS/H/08/157,PERALATAN DAN KELENGKAPAN BENGKEL / KEJURUTERAAN,BENGKEL AUTOMOTIF,AHMAD FAIRUZ BIN MOHAMED,JABATAN KEJURUTERAAN MEKANIKAL
,,PERALATAN BENGKEL AUTOMOTIF / HALFCUT ON STAND/ENGINE HALF CUT,,,
2,KPT/PKS/H/16/38,PERALATAN DAN KELENGKAPAN BENGKEL / KEJURUTERAAN,BENGKEL AUTOMOTIF,AHMAD FAIRUZ BIN MOHAMED,JABATAN KEJURUTERAAN MEKANIKAL
,,PERALATAN BENGKEL AUTOMOTIF / PNEUMATIC TYRE CHANGER MACHINE,,,
3,KPT/PKS/H/20/96,PERALATAN DAN KELENGKAPAN BENGKEL / KEJURUTERAAN,BENGKEL AUTOMOTIF,AHMAD FAIRUZ BIN MOHAMED,JABATAN KEJURUTERAAN MEKANIKAL
,,PERALATAN BENGKEL AUTOMOTIF / JACK STAND,,,
```

### How It Works:
1. System detects multi-line format by checking for "Bil", "Maklumat Aset", and "Pengguna" columns
2. For each asset:
   - Reads Row 1: Gets registration number, location, user, department
   - Reads Row 2: Extracts asset type from detailed description (last part after "/")
   - Example: `PERALATAN BENGKEL AUTOMOTIF / JACK STAND` → Asset Type = `JACK STAND`
3. Transforms to standard format internally

### Mapping:
| Multi-Line Format | → | Standard Format |
|-------------------|---|-----------------|
| No. Siri Pendaftaran | → | Label |
| Last part of Maklumat Aset (Row 2) | → | Jenis Aset |
| Pengguna | → | Pegawai Penempatan |
| Bahagian | → | Bahagian |
| Lokasi Semasa | → | Lokasi Terkini |

---

## Auto-Detection

The system **automatically detects** which format you're using:

✅ **Standard Format**: If headers match `Label`, `Jenis Aset`, etc.
✅ **Multi-Line Format**: If headers match `Bil`, `No. Siri Pendaftaran`, `Maklumat Aset`, etc.

No configuration needed - just upload your file!

---

## Which Format Should I Use?

### Use **Standard Format** when:
- Creating files manually
- Simple data entry
- Exporting from modern systems
- Want smaller file size

### Use **Multi-Line Format** when:
- Exporting from government asset management systems
- Following official format requirements
- Receiving files from external sources
- Need to maintain detailed asset categorization

---

## Important Notes

### For Both Formats:

1. **Department Must Exist**: The department specified must already exist in the system
   - Use "Add Department" button or bulk import to create departments first

2. **Location Auto-Creation**: If location doesn't exist, it will be created automatically under the specified department

3. **Supervisor Matching**: 
   - System tries to match supervisor name with users in the system
   - If not found, stores the name as-is
   - Matching is case-insensitive

4. **File Types**: Both CSV (.csv) and Excel (.xlsx, .xls) are supported

5. **Multiple Files**: You can upload multiple files at once - they will be merged

---

## Examples

### Standard Format - Complete Example:
```csv
Label,Jenis Aset,Pegawai Penempatan,Bahagian,Lokasi Terkini
KPT/PKS/H/08/157,ENGINE HALF CUT,Ahmad Fairuz bin Mohamed,JABATAN KEJURUTERAAN MEKANIKAL,BENGKEL AUTOMOTIF
KPT/PKS/H/16/38,TYRE CHANGER,Ahmad Fairuz bin Mohamed,JABATAN KEJURUTERAAN MEKANIKAL,BENGKEL AUTOMOTIF
KPT/PKS/H/16/39,GAS ANALYSER,Ahmad Fairuz bin Mohamed,JABATAN KEJURUTERAAN MEKANIKAL,MAKMAL AUTOMOTIF
KPT/PKS/H/20/96,JACK STAND,Siti binti Hassan,JABATAN PENGAJIAN AM,WORKSHOP A
```

### Multi-Line Format - Complete Example:
```csv
Bil,No. Siri Pendaftaran,Maklumat Aset,Lokasi Semasa,Pengguna,Bahagian
1,KPT/PKS/H/08/157,PERALATAN DAN KELENGKAPAN BENGKEL / KEJURUTERAAN,BENGKEL AUTOMOTIF,AHMAD FAIRUZ BIN MOHAMED,JABATAN KEJURUTERAAN MEKANIKAL
,,PERALATAN BENGKEL AUTOMOTIF / HALFCUT ON STAND/ENGINE HALF CUT,,,
2,KPT/PKS/H/16/38,PERALATAN DAN KELENGKAPAN BENGKEL / KEJURUTERAAN,BENGKEL AUTOMOTIF,AHMAD FAIRUZ BIN MOHAMED,JABATAN KEJURUTERAAN MEKANIKAL
,,PERALATAN BENGKEL AUTOMOTIF / PNEUMATIC TYRE CHANGER MACHINE,,,
3,KPT/PKS/H/16/39,PERALATAN DAN KELENGKAPAN MAKMAL,MAKMAL AUTOMOTIF,AHMAD FAIRUZ BIN MOHAMED,JABATAN KEJURUTERAAN MEKANIKAL
,,PERALATAN PENYELIDIKAN DAN PENGUJIAN / MULTIPLE GAS ANALYSER,,,
4,KPT/PKS/H/20/96,PERALATAN DAN KELENGKAPAN BENGKEL / KEJURUTERAAN,WORKSHOP A,SITI BINTI HASSAN,JABATAN PENGAJIAN AM
,,PERALATAN BENGKEL AUTOMOTIF / JACK STAND,,,
```

Both examples above will produce the same result in the system!

---

## Troubleshooting

**Q: My multi-line format file isn't being recognized**
- A: Make sure the header row contains exactly: `Bil`, `No. Siri Pendaftaran`, `Maklumat Aset`, `Lokasi Semasa`, `Pengguna`, `Bahagian`

**Q: Asset type shows the full category path instead of just the name**
- A: Check that Row 2 (detail row) has the format: `CATEGORY / SUBCATEGORY / ASSET NAME`
- System extracts everything after the last "/"

**Q: Some assets are missing after upload**
- A: In multi-line format, each asset needs 2 rows. If a detail row is missing, that asset is skipped

**Q: Can I mix both formats in one upload?**
- A: No, each file must use only one format. But you can upload multiple files with different formats in separate uploads.

---

## Summary

| Feature | Standard Format | Multi-Line Format |
|---------|----------------|-------------------|
| Rows per asset | 1 | 2 |
| File size | Smaller | Larger |
| Ease of creation | Easier | Complex |
| Government standard | No | Yes |
| Auto-detected | ✅ Yes | ✅ Yes |
| Supported | ✅ Yes | ✅ Yes |

Choose the format that works best for your data source! 🎯
