# Staff ID and Password Authentication - Implementation Summary

## ✅ Changes Implemented

### 1. Database Schema Updates
- **Added to `users` table:**
  - `staff_id` VARCHAR(4) - Unique 4-digit staff identifier
  - `personal_email` VARCHAR(191) - Personal email (in addition to institution email)
  - `password_hash` VARCHAR(255) - Encrypted password
  - `must_change_password` TINYINT(1) - Force password change flag

### 2. Backend API Changes

#### New Endpoints:
- **`/api/login.php`** - Staff ID + password authentication
  - POST with `staff_id` and `password`
  - Returns user data and `must_change_password` flag

#### Updated Endpoints:
- **`/api/users.php`**
  - POST: Now requires `staff_id` (4-digit), auto-generates password, emails to user
  - PUT: Supports password update (clears `must_change_password` flag)
  - Returns generated password when creating users via admin

### 3. Frontend Changes

#### Login Page (`Login.vue`)
- Changed from email to **Staff ID** input (4-digit)
- Real authentication via `/api/login.php`
- Redirects to profile if password change required

#### Users Management (`Users.vue`)
- Added **Staff ID** field (required, 4-digit, unique)
- Added **Personal Email** field
- Shows generated password to admin after user creation
- Auto-verifies users created via admin panel

#### Profile Page (`Profile.vue`)
- Displays **Staff ID** (read-only)
- Password change form calls API to update password
- Clears `must_change_password` flag on successful change

## 🚀 Setup Instructions

### For New Installation:

1. **Import Fresh Schema:**
   ```sql
   -- In phpMyAdmin, import:
   php-backend/sql/schema.sql
   ```

2. **Create First Admin User:**
   ```sql
   INSERT INTO users (id, staff_id, name, email, password_hash, must_change_password, status) 
   VALUES (
     '1001', 
     '1001', 
     'Admin User', 
     'admin@institution.edu',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: "password"
     1,
     'Verified'
   );
   
   INSERT INTO user_roles (user_id, role) VALUES ('1001', 'Admin');
   ```

3. **Login:**
   - Staff ID: `1001`
   - Password: `password`
   - You'll be prompted to change password on first login

### For Existing Database Migration:

1. **Run Migration Script:**
   ```sql
   -- In phpMyAdmin, run:
   php-backend/sql/migration_add_staff_id.sql
   ```

2. **Assign Staff IDs to Existing Users:**
   ```sql
   -- Example for existing user
   UPDATE users SET staff_id = '0001' WHERE id = 'wilsonintai76@gmail.com';
   UPDATE users SET staff_id = '0002' WHERE id = 'another@email.com';
   ```

3. **Set Passwords for Existing Users:**
   ```sql
   -- Generate password: "TempPass123!"
   UPDATE users SET 
     password_hash = '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNO',
     must_change_password = 1 
   WHERE staff_id = '0001';
   ```

4. **Make staff_id Required:**
   ```sql
   ALTER TABLE users MODIFY COLUMN staff_id VARCHAR(4) NOT NULL UNIQUE;
   ```

## 📋 User Creation Workflows

### Method 1: Admin Panel (Recommended)
1. Admin logs in
2. Goes to Users page
3. Clicks "Add User"
4. Fills in:
   - **Staff ID** (4 digits, e.g., 1234)
   - Full Name
   - Institution Email
   - Personal Email (optional)
   - Phone (optional)
   - Roles
   - Department
5. System automatically:
   - Generates secure password
   - Sets user status to "Verified"
   - Attempts to email password to user
   - Shows password to admin (SAVE THIS!)
   - Sets `must_change_password = TRUE`
6. Admin shares password with user securely
7. User logs in with Staff ID + password
8. User is forced to change password on first login

### Method 2: Self-Registration (Future Enhancement)
- User fills registration form
- Status set to "Unverified"
- Admin must verify and set password
- User receives email notification

## 🔐 Password Policy

- **Minimum Length:** 8 characters
- **Generated Passwords:** Include uppercase, lowercase, numbers, and special characters
- **Force Change:** New users must change password on first login
- **Hashing:** PHP `password_hash()` with bcrypt

## 📧 Email Notifications

The system includes email notification placeholders in `users.php`:
```php
// Currently logs to error_log
// TODO: Implement actual SMTP email sending
```

To implement real email:
1. Use PHPMailer or similar library
2. Configure SMTP settings in config.php
3. Update `send_password_email()` function

## 🧪 Testing

### Test Login Endpoint:
```powershell
curl -X POST http://localhost/inspectable-api/api/login.php `
  -H "Content-Type: application/json" `
  -d '{"staff_id":"1001","password":"your_password"}'
```

### Test User Creation:
```powershell
curl -X POST http://localhost/inspectable-api/api/users.php `
  -H "Content-Type: application/json" `
  -d '{
    "staff_id":"1234",
    "name":"Test User",
    "email":"test@institution.edu",
    "department_id":1,
    "roles":["Viewer"]
  }'
```

## 📝 Important Notes

1. **Staff ID Format:** Must be exactly 4 digits (e.g., 0001, 1234, 9999)
2. **Email Addresses:** Institution email is primary, personal email is optional backup
3. **Auto-Verification:** Users created via admin panel are auto-verified
4. **Password Security:** Never store plain text passwords; always use hashed passwords
5. **Session Storage:** Login stores user data in sessionStorage (clear on logout)

## 🔧 Troubleshooting

### Issue: User can't login
- Check staff_id is exactly 4 digits
- Verify password_hash exists in database
- Confirm status = 'Verified'
- Check CORS settings in config.php

### Issue: Password not generating
- Check PHP random_int() is available
- Verify password_hash() function works
- Review error logs

### Issue: Email not sending
- Currently emails are logged to error_log
- Implement SMTP for production use

## 🎯 Next Steps (Future Enhancements)

- [ ] Implement actual email sending (SMTP)
- [ ] Add password reset via email link
- [ ] Add "Forgot Password" functionality
- [ ] Implement session timeout
- [ ] Add login attempt rate limiting
- [ ] Add audit log for user actions
- [ ] Implement 2FA (Two-Factor Authentication)
