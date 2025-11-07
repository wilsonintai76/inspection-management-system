# Quick Setup Guide: Email Verification with Brevo SMTP

## What's New?
✅ Self-registration with email verification  
✅ Brevo SMTP email integration  
✅ Admin-created users bypass email verification  
✅ Resend verification email feature  
✅ Secure 24-hour verification tokens  

---

## Step 1: Database Migration

Run this SQL in phpMyAdmin:

```sql
USE inspectable;

ALTER TABLE users
ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER must_change_password,
ADD COLUMN verification_token VARCHAR(64) NULL AFTER email_verified,
ADD COLUMN verification_token_expires TIMESTAMP NULL AFTER verification_token;

UPDATE users 
SET email_verified = 1 
WHERE password_hash IS NOT NULL AND must_change_password = 1;

CREATE INDEX idx_verification_token ON users(verification_token);
```

---

## Step 2: Brevo SMTP Setup

### Get Brevo Credentials (FREE - 300 emails/day)

1. **Sign up:** <https://www.brevo.com>
2. **Go to:** SMTP & API → SMTP
3. **Copy:**
   - SMTP Login (looks like: your-email@brevo.com)
   - SMTP Key (long string)

### Update config.php

Edit `C:\wamp64\www\inspectable-api\config.php`:

```php
// Brevo SMTP Configuration
define('BREVO_SMTP_USERNAME', 'your-actual-smtp-login@brevo.com');
define('BREVO_SMTP_PASSWORD', 'your-actual-smtp-key-here');
define('BREVO_FROM_EMAIL', 'noreply@yourdomain.com');
define('BREVO_FROM_NAME', 'Inspectable');
define('APP_URL', 'http://localhost:5174');
```

**⚠️ Important:**
- Replace `your-actual-smtp-login@brevo.com` with YOUR Brevo SMTP login
- Replace `your-actual-smtp-key-here` with YOUR Brevo SMTP key
- Verify sender email (`BREVO_FROM_EMAIL`) in Brevo dashboard

---

## Step 3: Test Registration Flow

### Option A: Test Self-Registration

1. **Start Vue dev server:**
   ```powershell
   cd D:\Inspectable\vue-frontend
   npm run dev
   ```

2. **Open browser:** <http://localhost:5174>

3. **Click "Create now"** → Register page

4. **Fill form:**
   - Name: Test User
   - Staff ID: 9999 (4 digits)
   - Email: your-real-email@gmail.com
   - Password: testpass123

5. **Submit** → Check your email inbox

6. **Click verification link** → Account activated

7. **Login** with Staff ID 9999 and password

### Option B: Test Admin Registration

1. **Login as admin** (Staff ID: 1001, Password: password)

2. **Go to Users** → Add New User

3. **Fill form** with institutional email

4. **Password auto-generated** → Sent to user's email

5. **User logs in** → Forced to change password

---

## What's Deployed

### Backend Files (WAMP)
```
C:\wamp64\www\inspectable-api\
├── api/
│   ├── register.php              ✅ NEW
│   ├── verify-email.php          ✅ NEW
│   ├── resend-verification.php   ✅ NEW
│   ├── login.php                 ✅ UPDATED
│   └── users.php                 ✅ UPDATED
├── src/
│   ├── brevo_mailer.php          ✅ NEW
│   └── password_utils.php        ✅ UPDATED
└── config.php                    ✅ UPDATED
```

### Frontend Routes (Vue)
- `/register` - Self-registration form
- `/verify-email?token=xxx` - Email verification page
- `/login` - Login with resend verification option

---

## User Flows

### Self-Registration
1. User visits `/register`
2. Fills form → Submits
3. Receives verification email
4. Clicks link → Email verified
5. Logs in with Staff ID + password

### Admin Registration
1. Admin creates user
2. Password auto-generated
3. Sent to user via email
4. User logs in → Changes password

### Unverified Login
1. User tries to login
2. If email not verified → Error shown
3. "Resend verification" button appears
4. User clicks → New email sent

---

## Troubleshooting

### Emails Not Sending?

**Check Brevo credentials:**
```powershell
# View config
Get-Content C:\wamp64\www\inspectable-api\config.php | Select-String "BREVO"
```

**Check PHP error log:**
```powershell
Get-Content C:\wamp64\logs\php_error.log -Tail 20
```

**Verify Brevo dashboard:**
- Check sender email is verified
- Check daily quota (300/day on free tier)
- View email logs in Brevo dashboard

### Verification Link Not Working?

1. Token expires in 24 hours
2. Check APP_URL matches frontend: `http://localhost:5174`
3. Use resend verification option

### Database Errors?

Run migration again:
```sql
SHOW COLUMNS FROM users LIKE 'email_verified';
```

If no results, run migration script.

---

## Email Templates

### Verification Email
- **Subject:** Verify Your Email - Inspectable
- **Content:** Gradient header, verification button, 24h expiry notice
- **Beautiful HTML design** with Inspectable branding

### Welcome Email
- **Subject:** Welcome to Inspectable!
- **Content:** Staff ID display, login link, success message

### Password Email
- **Subject:** Your Inspectable Account Password
- **Content:** Staff ID + temporary password, change password notice

---

## Security Notes

✅ **Tokens:** 64-char random hex, 24-hour expiry, single-use  
✅ **Passwords:** Bcrypt hashing, 8+ chars required  
✅ **Email:** Brevo SMTP (TLS encrypted)  
✅ **Verification:** Prevents fake accounts  

---

## Support

📧 **Brevo Support:** <https://help.brevo.com>  
📚 **Full Documentation:** `EMAIL_VERIFICATION_SETUP.md`  
🐛 **Issues:** Check PHP error log + Brevo dashboard  

---

## Next Steps

1. ✅ Run database migration
2. ✅ Configure Brevo SMTP in config.php
3. ✅ Test self-registration
4. ✅ Test admin registration
5. ✅ Monitor Brevo email logs

**Ready to use!** 🎉
