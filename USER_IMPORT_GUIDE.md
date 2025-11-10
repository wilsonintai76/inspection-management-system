# Bulk User Import Guide

## Overview
The Bulk User Import feature allows administrators to import multiple users at once using CSV or Excel files, significantly faster than adding users one by one.

## Quick Start

### Step 1: Navigate to User Import
1. Login as **Admin**
2. Go to **Users** page (👥 icon in sidebar)
3. Click **"Bulk Import"** button in the header

### Step 2: Prepare Your File

**Required Columns (exact names):**
- `Staff ID` - 4-digit staff number (e.g., 2001, 2002)
- `Name` - Full name of user
- `Email` - Institution email address (@poliku.edu.my)
- `Department` - Department name (must match existing departments)
- `Role` - One of: `Admin`, `Asset Officer`, `Auditor`, `Viewer`

**Optional Columns:**
- `Phone` - Mobile phone number
- `Personal Email` - Personal email address

### Step 3: Choose Your Format

#### **CSV (Recommended)** ✅
- **4x faster** processing
- Smaller file size
- More reliable
- Works immediately (no setup required)

#### Excel (.xlsx, .xls)
- More familiar to users
- Requires PhpSpreadsheet library
- Slower processing
- Larger file size

### Step 4: Upload and Import
1. Click "Select Files"
2. Choose one or multiple files
3. Add optional notes
4. Click "Import X Files"
5. View results (imported/skipped counts)
6. **Copy the default password** shown after import and distribute securely to users

---

## Sample CSV Template

```csv
Staff ID,Name,Email,Department,Role,Phone,Personal Email
2001,Ahmad bin Ali,ahmad@poliku.edu.my,JABATAN KEJURUTERAAN AWAM,Auditor,0123456789,ahmad@gmail.com
2002,Siti Aminah,siti@poliku.edu.my,JABATAN KEJURUTERAAN ELEKTRIK,Asset Officer,0198765432,
2003,Lee Wei Ming,lee@poliku.edu.my,JABATAN PERDAGANGAN,Viewer,0134567890,lee@yahoo.com
2004,Nurul Huda,nurul@poliku.edu.my,JABATAN KEJURUTERAAN AWAM,Auditor,0145678901,nurul@gmail.com
```

**Download Template:**
Click "Download CSV Template" button in the User Import page to get a pre-formatted template.

---

## File Requirements

### Required Columns

| Column | Format | Example | Validation |
|--------|--------|---------|------------|
| **Staff ID** | 4 digits | 2001 | Must be unique, exactly 4 digits |
| **Name** | Text | Ahmad bin Ali | Cannot be empty |
| **Email** | Email format | ahmad@poliku.edu.my | Must be valid email, unique |
| **Department** | Department name | JABATAN KEJURUTERAAN AWAM | Must match existing department |
| **Role** | Predefined roles | Auditor | Must be: Admin, Asset Officer, Auditor, or Viewer |

### Optional Columns

| Column | Format | Example | Notes |
|--------|--------|---------|-------|
| **Phone** | Phone number | 0123456789 | Can include spaces or dashes |
| **Personal Email** | Email format | ahmad@gmail.com | Secondary email for notifications |

---

## Multi-File Import

### Feature Overview
Import multiple department files simultaneously without manual merging.

### How It Works
1. HR receives separate user files from 3 departments
2. Select all 3 files at once in import screen
3. System validates and processes all files together
4. All users imported in one batch

### Benefits
- ✅ No manual file merging required
- ✅ Faster workflow for HR/Admin
- ✅ Process multiple departments at once
- ✅ Single import operation
- ✅ Single default password for all users (reduces email quota usage)

### Example Workflow

**Scenario:** Admin receives user files from 3 departments
- `JKA_Users.csv` (15 users)
- `JKE_Users.csv` (20 users)
- `JADUAL_Users.csv` (18 users)

**Steps:**
1. Click "Select Files"
2. Ctrl+Click all 3 files (or Shift+Click to select range)
3. System shows: "Selected Files (3)"
4. Add notes: "Q4 2025 - New Staff"
5. Click "Import 3 Files"
6. Result: "Successfully processed 3 files: imported 53 users"

---

## Validation Rules

### Staff ID
- ✅ Must be exactly 4 digits
- ✅ Must be unique (not already in system)
- ❌ Invalid: `001` (too short), `20001` (too long), `A001` (contains letters)

### Email
- ✅ Must be valid email format
- ✅ Must be unique (not already in system)
- ❌ Invalid: `ahmad@` (incomplete), `ahmad.poliku.edu.my` (missing @)

### Department
- ✅ Must exactly match existing department name or acronym
- ⚠️ System attempts fuzzy matching if no exact match
- ❌ If not found, user imported but department_id = NULL

### Role
- ✅ Must be one of: `Admin`, `Asset Officer`, `Auditor`, `Viewer`
- ❌ Invalid: `Administrator` (use Admin), `Manager`, `Staff`

---

## Duplicate Handling

### What Happens to Duplicates?
Users with existing **Staff IDs** or **Emails** are **automatically skipped**.

### Import Results
```
Successfully imported 45 users (5 skipped)

Errors:
- Row 3: Staff ID '2001' already exists
- Row 7: Email 'siti@poliku.edu.my' already exists
- Row 12: Staff ID '2003' already exists
...
```

### Best Practices
1. Export current users before import to check for duplicates
2. Review error messages to identify skipped users
3. Manually add skipped users if needed (update existing users in Users page)

---

## Password Handling (Bulk Import)

When users are created via the bulk import feature:

| Aspect | Behavior |
|--------|---------|
| Password Strategy | **Single default password** shared by all imported users (configurable in `config.php`). |
| Default Password | `PolikuInspect@2025` (can be changed via `DEFAULT_IMPORT_PASSWORD` config). |
| Storage | Only a bcrypt hash is stored in the `users.password_hash` column. Plain password never stored in database. |
| First Login Requirement | `must_change_password = 1` forces user to change password immediately after first login. |
| Verification Status | Users are marked `Verified` and `email_verified = 1` so they can log in right away. |
| Distribution | Default password is shown once in the import success message. Admin must copy/download and distribute securely. |
| Email Sending | **No emails sent** for bulk imports. This preserves Brevo quota for critical functions (password reset, self-registration verification). |
| Security | If admin doesn't copy the password during import session, they can check `config.php` or reset users manually. |

### Why Use Default Password Instead of Unique Passwords?

**Email Quota Conservation:**
- Brevo free tier has limited daily email quota (300/day)
- Bulk importing 50+ users would consume entire daily quota
- Reserves email capacity for:
  - Password reset requests
  - Self-registration email verification
  - Critical notifications

**Simplified Distribution:**
- Admin only needs to communicate one password
- Can be distributed via:
  - Physical printed notices
  - In-person orientation
  - Internal messaging systems
  - Secure department channels

**Security:**
- Users **must** change password on first login
- Initial password acts as temporary access credential
- Account security established when user sets personal password

### Why We Auto-Verify Imported Users?
Bulk-imported users are assumed institution staff vetted by the admin. Skipping email verification accelerates onboarding. If you prefer standard verification, adjust the backend to set `email_verified = 0` and `status = 'Unverified'`.

### Recommended Distribution Process
1. After import, click **📋 Copy Password** or **💾 Download Info**.
2. Distribute password through secure channels:
   - Print welcome letters for each user
   - Announce at department meetings with written handouts
   - Send via internal secure messaging (not email)
   - Post in secure physical notice boards
3. Include instructions: "Login with Staff ID, default password, then **immediately create your own password**."
4. Monitor first-login activity to ensure users complete password changes.

### If a User Forgets Before Changing Password
- They can still use the default password if within initial access period
- Admin can check `config.php` → `DEFAULT_IMPORT_PASSWORD` to remind user
- If password already changed and forgotten: use normal "Forgot Password" flow

### If a Password Is Lost
- **Before user changes password:** Admin can check `config.php` → `DEFAULT_IMPORT_PASSWORD`
- **After user changes password:** Use normal "Forgot Password" flow or admin manual reset via Users page

### Changing the Default Password
To customize the default password for bulk imports:
1. Open `php-backend/config.php`
2. Modify the line:
   ```php
   define('DEFAULT_IMPORT_PASSWORD', getenv('DEFAULT_IMPORT_PASSWORD') ?: 'YourCustomPassword@2025');
   ```
3. Or set environment variable `DEFAULT_IMPORT_PASSWORD`
4. Use strong password: 12+ chars, mixed case, numbers, symbols
5. Communicate new password to admins securely

### Adjusting Policy (Optional)
To require email verification instead:
1. Edit `user-import.php` and change `$emailVerified = 1; $status = 'Verified';` to `$emailVerified = 0; $status = 'Unverified';`.
2. Ensure your email sending service is configured so users receive verification links.

### Future Enhancements
| Feature | Description |
|---------|-------------|
| Email Notification Toggle | Optional setting to email default password to users (when quota allows). |
| Password Expiry Policy | Auto-expire default password after X days if not changed. |
| Bulk Password Reset | One-click force-reset for a selected group. |
| Unique Password Option | Toggle between default vs. unique passwords (when email quota sufficient). |
| Password Complexity Config | Customize minimum length and character requirements. |

---

## Department Mapping

### How It Works
System automatically maps department names from file to department IDs in database.

### Matching Strategy
1. **Exact match**: Checks department name exactly
2. **Acronym match**: Checks department acronym
3. **Partial match**: Fuzzy matching if no exact match

### Example
File has: `JABATAN KEJURUTERAAN AWAM`
- System finds department with name = `JABATAN KEJURUTERAAN AWAM` ✅
- Or finds department with acronym = `JKA` ✅
- Or finds department with name containing `KEJURUTERAAN AWAM` ✅

### If Department Not Found
- User is still imported
- `department_id` = NULL
- Warning message: "Row X: Department 'NAME' not found"
- Admin can manually assign department later in Users page

### Best Practice
**Before importing, check existing departments:**
1. Go to Settings → Departments (or Asset Management page)
2. Note exact department names
3. Use exact names in import file

---

## Error Messages Explained

### Row-Level Errors

| Error Message | Cause | Solution |
|--------------|-------|----------|
| Missing required fields (Staff ID, Name, or Email) | Empty required column | Fill in all required fields |
| Invalid Staff ID 'XXX' (must be 4 digits) | Staff ID not 4 digits | Change to 4-digit number |
| Invalid email format 'XXX' | Email format wrong | Fix email format (must have @) |
| Invalid role 'XXX' | Role not recognized | Use: Admin, Asset Officer, Auditor, or Viewer |
| Staff ID 'XXXX' already exists | Duplicate staff ID | User already in system (skipped) |
| Email 'XXX' already exists | Duplicate email | User already in system (skipped) |
| Department 'XXX' not found | Department name mismatch | Check department names in system |

### File-Level Errors

| Error Message | Cause | Solution |
|--------------|-------|----------|
| No file uploaded | No file selected | Select at least one file |
| Invalid file type for 'filename' | Wrong format | Use CSV, .xlsx, or .xls only |
| Missing required column 'XXX' in file | Column missing | Add required column to file |
| Empty file or invalid format | File has no data | Check file has headers and data rows |
| Excel support not installed | PhpSpreadsheet missing | Use CSV format or install PhpSpreadsheet |

---

## CSV vs Excel - Which to Use?

### Performance Comparison

| Factor | CSV | Excel |
|--------|-----|-------|
| **Processing Speed** | ⚡ 10x faster | ⏱️ Slower |
| **File Size** | 📦 4x smaller | 📦 Larger |
| **Setup Required** | ✅ None | ⚠️ Requires PhpSpreadsheet |
| **Reliability** | ✅ Very reliable | ⚠️ Can have formatting issues |
| **Memory Usage** | ✅ Low | ⚠️ Higher |

### Example: Import 100 Users

**CSV:**
- File size: ~10 KB
- Processing time: 1-2 seconds
- Memory: ~2 MB

**Excel:**
- File size: ~40 KB
- Processing time: 5-8 seconds
- Memory: ~10 MB

### Recommendation
**Use CSV for bulk imports** - faster, more reliable, works everywhere.

### Converting Excel to CSV
If you have Excel file:
1. Open in Excel
2. File → Save As
3. Choose "CSV (Comma delimited) (*.csv)"
4. Click Save

---

## Example Workflows

### Workflow 1: Annual New Staff Import
**Scenario:** Import 50 new staff at start of academic year

1. HR receives Excel file with 50 new staff
2. Save Excel as CSV
3. Go to Users → Bulk Import
4. Upload CSV file
5. Add notes: "Academic Year 2025/2026 - New Staff"
6. Import
7. Result: 50 users imported successfully
8. Users receive verification emails automatically

### Workflow 2: Multi-Department Import
**Scenario:** Each department submits their own user list

1. Receive 5 department files:
   - JKA_Users.csv (12 users)
   - JKE_Users.csv (15 users)
   - JADUAL_Users.csv (10 users)
   - JPH_Users.csv (8 users)
   - JPA_Users.csv (14 users)

2. Go to Users → Bulk Import
3. Ctrl+Click all 5 files
4. Add notes: "Q4 2025 - All Departments"
5. Import 5 Files
6. Result: 59 users imported

### Workflow 3: Update Roles in Bulk
**Scenario:** Promote 10 Viewers to Auditors

**Note:** Current import feature **does not update existing users**. To change roles:
- Option 1: Manually update in Users page (one by one)
- Option 2: Use database query (for admins with DB access)
- Option 3: Future feature (coming soon!)

---

## Tips & Best Practices

### ✅ Before Import
1. **Validate department names** - Check Settings → Departments for exact names
2. **Check for duplicates** - Export current users, compare staff IDs
3. **Use CSV format** - Faster and more reliable than Excel
4. **Test with small file** - Import 2-3 users first to test format

### ✅ During Import
1. **Review file requirements** - Check column names are exact
2. **Add descriptive notes** - Help track import batches
3. **Watch for errors** - Review error messages carefully

### ✅ After Import
1. **Review results** - Check imported/skipped counts
2. **Fix skipped users** - Manually add or update skipped users
3. **Verify users** - Check Users page to confirm import
4. **Send notifications** - Users should receive verification emails

### ✅ Common Mistakes to Avoid
- ❌ Wrong column names (e.g., "Staff No" instead of "Staff ID")
- ❌ Using non-standard roles (e.g., "Manager" instead of "Admin")
- ❌ Department names don't match system
- ❌ Email addresses not in @poliku.edu.my format
- ❌ Staff IDs not 4 digits

---

## Troubleshooting

### Problem: No users imported, all skipped
**Likely Cause:** All staff IDs or emails already exist
**Solution:**
- Check if users already in system
- Use different staff IDs
- Update existing users manually instead

### Problem: Department not assigned to users
**Likely Cause:** Department name in file doesn't match system
**Solution:**
- Go to Settings → Departments
- Copy exact department name
- Update CSV file with exact name
- Re-import

### Problem: "Excel support not installed" error
**Solution 1:** Use CSV format instead (recommended)
**Solution 2:** Install PhpSpreadsheet:
```bash
cd php-backend
composer require phpoffice/phpspreadsheet
```

### Problem: Import very slow with large file
**Likely Cause:** Using Excel format with 500+ users
**Solution:**
- Convert to CSV (4x faster)
- Split into smaller files
- Import in batches

---

## Security & Permissions

### Who Can Import Users?
- **Admin role only**
- Asset Officers, Auditors, Viewers cannot access import feature

### What Happens After Import?
- Users created with status = "Unverified"
- Users cannot login until email verified
- Verification emails sent automatically (if email system configured)
- Admin can manually verify users in Users page

### Data Privacy
- Imported user data stored securely in database
- Personal emails optional (can be blank)
- No passwords set during import (users set on first login)

---

## API Reference (For Developers)

### Endpoint
```
POST /api/user-import.php
```

### Request
```
Content-Type: multipart/form-data

files[]: File (one or more CSV/Excel files)
user_id: string (admin user ID)
notes: string (optional)
```

### Response
```json
{
  "success": true,
  "users_imported": 53,
  "users_skipped": 3,
  "files_processed": 2,
  "errors": [
    "Row 5: Staff ID '2001' already exists",
    "Row 12: Email 'test@poliku.edu.my' already exists"
  ],
  "message": "Successfully processed 2 files: imported 53 users (3 skipped)"
}
```

### Error Response
```json
{
  "error": "Missing required column 'Staff ID' in file: users.csv"
}
```

---

## Future Enhancements

### Planned Features
- 🔄 Update existing users (change roles, departments, etc.)
- 📧 Custom email templates for new users
- 📊 Import preview before committing
- 🔍 Duplicate detection with merge option
- 📤 Export current users to CSV
- 📝 Import history log with rollback option

---

## Related Documentation
- [User Management Guide](USER_MANAGEMENT.md)
- [Department Setup Guide](DEPARTMENT_SETUP.md)
- [Asset Upload Guide](QUICK_START_ASSET_UPLOAD.md)
- [System Overview](SYSTEM_OVERVIEW.md)

---

## Support
For questions or issues with bulk import:
1. Check this guide for common solutions
2. Review error messages carefully
3. Test with small sample file
4. Contact system administrator if issues persist

---

**Last Updated:** November 10, 2025
