# Department & Location Management Guide

## Recommended Workflow ⭐

### **Initial Setup (Once)**
Use **Bulk Import** to load all departments and locations from CSV file:
1. Prepare CSV with all departments and locations
2. Go to Departments page → Click "Bulk Import"
3. Select **"📦 Initial Setup / Add New"** mode
4. Upload your CSV file
5. All departments and locations are loaded at once

### **Ongoing Updates (Daily/Monthly)**
Use **manual Add/Edit buttons** for individual changes:
- **Add new department:** Click "Add Department" button
- **Add new location:** Click "Add Location" button  
- **Update info:** Click Edit button on any row
- Fast, safe, no file preparation needed

### **Annual Reset (Once per year)**
Use **Replace All mode** to clear everything and start fresh:
- Bulk Import → Select "Replace All (Annual Reset)"
- Deletes everything and loads new data

---

## Problem Solved
When new departments or locations are added mid-year, the uninspected asset counts stay accurate because:
- Manual add buttons preserve all existing data
- System automatically recalculates totals
- No risk of data loss

---

## Method 1: Manual Add Button ✨ **RECOMMENDED FOR UPDATES**

The simplest way for adding one or two new departments/locations:

✅ **For new departments:**
1. Go to **Departments** page
2. Click **"Add Department"** button (green ⊕ button)
3. Fill in:
   - Department Name (required)
   - Acronym (optional)
4. Click Save
5. Done! Existing data is automatically preserved

✅ **For new locations:**
1. Go to **Locations** page
2. Click **"Add Location"** button (green + button)
3. Fill in:
   - Location Name (required)
   - Department (select from dropdown)
   - Supervisor Name (optional)
   - Contact Number (optional)
4. Click Save
5. Done! Existing data is automatically preserved

**Best for:**
- Adding 1-2 new departments
- Adding 1-5 new locations
- Quick mid-year updates
- When you don't have a CSV file ready

### Method 2: **Bulk Import - Add New Mode** (For Multiple Items) 📁

Use this when adding many new departments/locations from a CSV file:

✅ **What it does:**
- Adds new departments and locations from your CSV
- Keeps ALL existing data intact
- Preserves all inspection records
- System automatically recalculates uninspected counts

✅ **When to use:**
- Adding 5+ new departments or locations
- You already have data in CSV/Excel format
- Bulk mid-year organizational changes
- Monthly/quarterly batch updates

✅ **How it works:**
1. Prepare CSV with new departments/locations only
2. Go to Departments page → Click "Bulk Import"
3. Select **"Add New (Mid-Year Update)"** mode (radio button)
4. Upload CSV file(s)
5. System will:
   - Skip existing departments (no duplicates)
   - Add only new entries
   - Keep all inspection data

### Method 3: **Replace All (Annual Reset)** ⚠️

Complete database reset - use ONLY for annual refresh:

⚠️ **What it does:**
- **DELETES EVERYTHING**
- Clears all departments, locations, assets, inspections
- Complete database reset
- Starts fresh for new year

⚠️ **When to use:**
- Start of new fiscal year
- Complete organizational restructure
- Initial system setup
- When you need to start from scratch

## Example Scenarios

### Scenario A: Single New Department Added in June
**Situation:** A new "Bahagian IT Baharu" department is created

**Solution (EASIEST):**
1. Go to Departments page
2. Click **"Add Department"** button
3. Enter: Name = "Bahagian IT Baharu", Acronym = "ITB"
4. Click Save
5. Go to Locations page → Add locations for this department using **"Add Location"** button
6. Upload assets for this department using Asset Upload
7. Done! Existing inspection data remains intact

**Alternative (CSV method):**
1. Create CSV with just the new department:
   ```csv
   Bahagian,Pegawai Penyelia,Lokasi Terkini
   Bahagian IT Baharu,Ahmad bin Ali,Bangunan Utama
   Bahagian IT Baharu,Ahmad bin Ali,Bangunan Pentadbiran
   ```
2. Use Bulk Import → **"Add New (Mid-Year Update)"** mode
3. Upload → System adds new department with locations

### Scenario B: Multiple New Locations for Existing Department
**Situation:** Need to add 10 new locations to "JABATAN KEJURUTERAAN AWAM"

**Solution (Manual - if just a few):**
1. Go to Locations page
2. Click **"Add Location"** button 10 times
3. For each: Select department, enter location name, supervisor
4. Done!

**Solution (Bulk - if many):**
1. Create CSV with just the new locations:
   ```csv
   Bahagian,Pegawai Penyelia,Lokasi Terkini
   JABATAN KEJURUTERAAN AWAM,Ahmad bin Ali,Makmal Baru 1
   JABATAN KEJURUTERAAN AWAM,Ahmad bin Ali,Makmal Baru 2
   JABATAN KEJURUTERAAN AWAM,Siti binti Hassan,Workshop A
   ```
2. Use Bulk Import → **"Add New"** mode
3. System adds only the new locations to existing department

### Scenario C: Location Moved Between Departments
**Situation:** "Makmal Komputer" moved from Dept A to Dept B

**Solution (Manual):**
1. Go to Locations page
2. Find "Makmal Komputer" → Click Edit
3. Change Department dropdown to "Department B"
4. Click Save

**Note:** Cannot change location name or department via edit. If moving, better to:
1. Add new location under Dept B
2. Delete old location from Dept A (or leave it if it has inspection history)

### Scenario D: Annual Reset (January)
**Situation:** New fiscal year, complete data refresh

**Solution:**
1. Prepare complete CSV with ALL departments and locations
2. Use **"Replace All (Annual Reset)"** mode
3. Upload → Everything is cleared and rebuilt
4. Upload asset inspection data

## CSV Format
Both modes use the same CSV format:

```csv
Bahagian,Pegawai Penyelia,Lokasi Terkini
JABATAN KEJURUTERAAN AWAM,Ahmad bin Ali,Bangunan Utama
JABATAN KEJURUTERAAN AWAM,Ahmad bin Ali,Makmal A
JABATAN PENGAJIAN AM,Siti binti Hassan,Bangunan Akademik
```

**Required Columns:**
- `Bahagian` or `Jabatan` - Department name
- `Pegawai Penyelia` or `Pegawai Penempatan` - Supervisor name (not Staff ID)
- `Lokasi Terkini` or `Lokasi` - Location name

## Quick Decision Guide

**Initial System Setup (First Time):**
- 📦 **Use Bulk Import** with "Initial Setup" mode
- Upload complete CSV with all departments and locations
- One-time setup, typically at project start

**Daily/Monthly Updates (Ongoing):**
- ✨ **Use Add/Edit buttons** (manual)
- Perfect for: New department, new location, updating supervisor names
- Fast, safe, no CSV needed
- This is what you'll use 90% of the time!

**Annual Refresh (Once per year):**
- ⚠️ **Use Bulk Import** with "Replace All" mode
- Deletes everything and starts fresh
- Use at beginning of fiscal year

---

## Summary Table

| Task | Method | When | Why |
|------|--------|------|-----|
| First-time setup | Bulk Import (Initial Setup) | Once | Load all data at once |
| Add 1 department | Manual Add button | Anytime | Fastest & safest |
| Add 1-5 locations | Manual Add button | Anytime | No CSV needed |
| Update supervisor | Manual Edit button | Anytime | Quick & easy |
| Annual reset | Bulk Import (Replace All) | Yearly | Fresh start |

**Key Insight:** After initial bulk import, you'll mostly use the Add/Edit buttons for day-to-day operations! 🎯

## Best Practices

### For Single/Few Items (Manual Method):
✅ Use the Add buttons - fastest and safest
✅ No file preparation needed
✅ Immediate feedback
✅ Can't accidentally delete anything

### For Mid-Year Bulk Updates (Add New Mode):
✅ Only include NEW departments/locations in your CSV
✅ Test with a small file first
✅ Verify counts after upload
✅ Keep a backup of your CSV files

### For Annual Reset:
✅ Include ALL departments and locations
✅ Backup database before reset (if possible)
✅ Verify file is complete before upload
✅ Do this at the start of fiscal year

## Technical Notes

### Add New Mode (Append)
- Backend parameter: `append=true`
- Skips existing entries by name
- No deletion occurs
- Fast and safe for updates

### Replace All Mode (Overwrite)
- Backend parameter: `overwrite=true`
- Deletes in order: asset_inspections → asset_upload_batches → asset_uploads → locations → departments
- Transaction-based (rolls back if error)
- Use with caution!

## Troubleshooting

**Q: I need to add just one new department. What's the fastest way?**
- A: Use the **"Add Department"** button on the Departments page. Takes 10 seconds!

**Q: Can I edit a department or location name after creating it?**
- A: Yes! Click the Edit button on the department/location row. For departments you can edit name and acronym. For locations you can edit supervisor and contact number.

**Q: I uploaded new department but uninspected count is wrong**
- A: The system calculates from total assets. After adding department, upload the asset file with total assets for accurate counts.

**Q: Can I update existing department info (like supervisor name)?**
- A: In "Add New" mode, it skips existing. Use "Replace All" for updates, or manually edit via UI.

**Q: What if I accidentally use Replace All?**
- A: All data is deleted! You'll need to re-upload everything. Always double-check the mode before uploading.

**Q: Can I add just one new location to existing department?**
- A: Yes! Create CSV with department name and new location. Use "Add New" mode.

## Quick Reference

| Task | Upload Mode | What to Include in CSV |
|------|-------------|----------------------|
| New department mid-year | Add New | Just the new department + its locations |
| New location mid-year | Add New | Department name + new location |
| Update supervisor name | Replace All | Complete list with corrections |
| Start of year refresh | Replace All | Complete list of all depts/locations |
| Testing | Add New | Test entries (safe, won't delete) |
| Fix mistakes | Replace All | Corrected complete list |
