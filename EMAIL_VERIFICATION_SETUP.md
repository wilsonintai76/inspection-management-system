# Self-Registration with Email Verification (Brevo SMTP)

## Overview
Users can now self-register with email verification using Brevo SMTP service.

## Features
1. **Self-Registration** - Users register with staff ID, email, and password
2. **Email Verification** - Verification email sent via Brevo SMTP
3. **Admin Registration** - Admin-created users bypass email verification
4. **Resend Verification** - Users can request new verification email
5. **Secure Tokens** - 64-character random tokens, 24-hour expiration

## Database Changes
New fields added to `users` table:
- `email_verified` - TINYINT(1), default 0
- `verification_token` - VARCHAR(64), nullable
- `verification_token_expires` - TIMESTAMP, nullable

Run migration: `php-backend/sql/migration_add_email_verification.sql`

## Brevo SMTP Configuration

### Step 1: Get Brevo Credentials
1. Sign up at https://www.brevo.com (free tier: 300 emails/day)
2. Go to **SMTP & API** → **SMTP**
3. Copy your **SMTP Login** and **SMTP Key**

### Step 2: Update config.php
```php
// Brevo SMTP Configuration
define('BREVO_SMTP_USERNAME', 'your-brevo-smtp-login@example.com');
define('BREVO_SMTP_PASSWORD', 'your-smtp-key-here');
define('BREVO_FROM_EMAIL', 'noreply@yourdomain.com');
define('BREVO_FROM_NAME', 'Inspectable');
define('APP_URL', 'http://localhost:5174');
```

**Important:** 
- BREVO_SMTP_USERNAME is your SMTP login email
- BREVO_SMTP_PASSWORD is your SMTP key (not your Brevo password)
- BREVO_FROM_EMAIL must be verified in your Brevo account

## API Endpoints

### 1. Self-Registration
**POST** `/api/register.php`
```json
{
  "name": "John Doe",
  "staff_id": "1234",
  "email": "john@institution.edu",
  "phone": "+1234567890",
  "department_id": 1,
  "password": "securepassword123"
}
```

**Response:**
```json
{
  "message": "Registration successful. Please check your email to verify your account.",
  "user_id": "1234",
  "staff_id": "1234",
  "email_sent": true
}
```

### 2. Email Verification
**POST** `/api/verify-email.php`
```json
{
  "token": "64-character-verification-token"
}
```

**Response:**
```json
{
  "message": "Email verified successfully",
  "staff_id": "1234",
  "name": "John Doe"
}
```

### 3. Resend Verification
**POST** `/api/resend-verification.php`
```json
{
  "email": "john@institution.edu"
}
```

**Response:**
```json
{
  "message": "Verification email sent. Please check your inbox.",
  "email_sent": true
}
```

## Frontend Routes

### Register Page
- **URL:** `/#/register`
- **Component:** `Register.vue`
- Fields: Name, Staff ID, Email, Phone, Department, Password, Confirm Password
- Validates staff ID (4 digits), password strength (8+ chars)
- Shows success message after registration

### Verify Email Page
- **URL:** `/#/verify-email?token=xxx`
- **Component:** `VerifyEmail.vue`
- Auto-verifies on page load
- Shows success/error with staff ID
- Resend option if token expired

## User Flow

### Self-Registration Flow
1. User visits `/register`
2. Fills form (name, staff ID, email, password)
3. Submits registration
4. Receives verification email via Brevo
5. Clicks link in email → `/verify-email?token=xxx`
6. Email verified → account activated
7. Redirected to login

### Admin Registration Flow
1. Admin creates user in Users panel
2. System auto-generates password
3. User marked as `email_verified=1` (bypasses verification)
4. Password sent via Brevo email
5. User logs in → forced to change password

### Login with Unverified Email
1. User tries to log in
2. If `email_verified=0`, login blocked
3. Error message shown with resend option
4. User can request new verification email

## Email Templates

### 1. Verification Email
- **Subject:** "Verify Your Email - Inspectable"
- **Content:** Welcome message, verification button, 24-hour expiry notice
- **Template:** `brevo_mailer.php::getVerificationEmailTemplate()`

### 2. Welcome Email
- **Subject:** "Welcome to Inspectable!"
- **Content:** Confirmation, staff ID, login link
- **Template:** `brevo_mailer.php::getWelcomeEmailTemplate()`

### 3. Password Email (Admin-created)
- **Subject:** "Your Inspectable Account Password"
- **Content:** Staff ID, temporary password, change password notice
- **Template:** `brevo_mailer.php::getPasswordEmailTemplate()`

## Security Features

### Token Security
- 64-character random hex tokens (128 bits entropy)
- 24-hour expiration
- Single-use (deleted after verification)
- Index on token field for fast lookups

### Password Security
- Minimum 8 characters required
- Bcrypt hashing (PASSWORD_DEFAULT)
- Auto-generated passwords: 12 chars with uppercase, lowercase, numbers, special chars
- Must change password on first login (admin-created users)

### Email Verification
- Prevents fake accounts
- Ensures institutional email validity
- Blocks login until verified
- Resend limit (consider rate limiting in production)

## Testing

### Local Testing (Without Brevo)
If Brevo not configured, emails will fail gracefully:
- Registration still succeeds
- `email_sent: false` in response
- Token logged to PHP error log
- Manual verification via database

### With Brevo SMTP
1. Configure Brevo credentials in `config.php`
2. Register test user: `/#/register`
3. Check email inbox for verification
4. Click link or copy token
5. Verify at `/#/verify-email?token=xxx`
6. Login with staff ID and password

## Deployment Checklist

### Database
- [ ] Run `migration_add_email_verification.sql` in phpMyAdmin
- [ ] Verify new columns exist: `email_verified`, `verification_token`, `verification_token_expires`

### Backend (WAMP)
- [ ] Copy `brevo_mailer.php` to `C:\wamp64\www\inspectable-api\src\`
- [ ] Update `config.php` with Brevo credentials
- [ ] Copy API files to `C:\wamp64\www\inspectable-api\api\`:
  - `register.php`
  - `verify-email.php`
  - `resend-verification.php`
- [ ] Update `login.php` (email verification check)
- [ ] Update `users.php` (email_verified=1 for admin users)

### Frontend (Vue)
- [ ] Add routes in `router/index.ts`:
  - `/register` → Register.vue
  - `/verify-email` → VerifyEmail.vue
- [ ] Update Login.vue (register link, resend verification)
- [ ] Rebuild: `npm run build` (if using production)

### Brevo Configuration
- [ ] Sign up at brevo.com
- [ ] Verify sender email in Brevo dashboard
- [ ] Get SMTP credentials (login + key)
- [ ] Test email sending with test account
- [ ] Monitor daily email quota (300/day free tier)

## Production Considerations

### Rate Limiting
Add rate limiting to prevent abuse:
- Registration: Max 5 per IP per hour
- Resend verification: Max 3 per email per hour
- Login attempts: Max 5 per staff ID per 15 minutes

### Email Deliverability
- Use verified domain in Brevo
- Add SPF/DKIM records
- Warm up IP (gradual increase in volume)
- Monitor bounce rates

### Error Handling
- Log all email failures
- Alert admin if email service down
- Fallback: Manual verification by admin

### HTTPS
- Use HTTPS in production (update APP_URL)
- Secure verification links
- Prevent MITM attacks

## Troubleshooting

### Emails Not Sending
1. Check Brevo credentials in `config.php`
2. Verify sender email in Brevo dashboard
3. Check PHP error log: `C:\wamp64\logs\php_error.log`
4. Test Brevo API key in dashboard
5. Check daily quota (300 emails on free tier)

### Verification Link Not Working
1. Check token expiry (24 hours)
2. Verify APP_URL matches frontend URL
3. Check database: `verification_token` and `verification_token_expires`
4. Test token manually: `/verify-email?token=xxx`

### User Can't Login After Verification
1. Check `email_verified=1` in database
2. Check `status='Verified'` in database
3. Verify password hash exists
4. Check `must_change_password` flag

### SMTP Connection Errors
1. Verify port 587 is open (firewall)
2. Check SMTP credentials (login vs password)
3. Test with Telnet: `telnet smtp-relay.brevo.com 587`
4. Check PHP SMTP settings in `php.ini`

## File Structure
```
php-backend/
├── src/
│   └── brevo_mailer.php          # Brevo SMTP email service
├── public/api/
│   ├── register.php               # Self-registration endpoint
│   ├── verify-email.php           # Email verification endpoint
│   ├── resend-verification.php    # Resend verification email
│   ├── login.php                  # Updated with email check
│   └── users.php                  # Updated with email_verified
├── sql/
│   └── migration_add_email_verification.sql
└── config.php                     # Brevo SMTP credentials

vue-frontend/
├── src/
│   ├── views/
│   │   ├── Register.vue           # Registration form
│   │   ├── VerifyEmail.vue        # Verification success page
│   │   └── Login.vue              # Updated with register link
│   └── router/
│       └── index.ts               # Updated with new routes
```

## Support
For issues with Brevo SMTP:
- Brevo Documentation: https://developers.brevo.com/docs
- SMTP Setup Guide: https://help.brevo.com/hc/en-us/articles/209462765
- Contact: support@brevo.com
