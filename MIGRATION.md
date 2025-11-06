# Migration to Vue 3 + WAMP (Apache/PHP + MySQL)

This guide helps move the Inspectable app from Next.js + Firestore to Vue 3 + PHP (WAMP) + MySQL, hosting locally on your Windows desktop.

## Overview

- Frontend: Vue 3 + Vite + TypeScript (folder: `vue-frontend/`)
- Backend: PHP 8 + Apache (WAMP) with PDO + MySQL (folder: `php-backend/`)
- Database: MySQL schema based on `MYSQL_SCHEMA.md` (see `php-backend/sql/schema.sql`)

## Steps

1) Install WAMP
- Download and install WAMP (https://www.wampserver.com/).
- Ensure MySQL and Apache services are running (green tray icon).

2) Create database and tables
- Open phpMyAdmin (http://localhost/phpmyadmin).
- Create a database, e.g., `inspectable`.
- Import `php-backend/sql/schema.sql` into that database.

3) Configure PHP API
- Copy `php-backend/config.example.php` to `php-backend/config.php`.
- Update `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
- Optionally copy `php-backend/public/` into WAMP’s `www` (e.g., `C:\\wamp64\\www\\inspectable-api`) or configure an Apache VirtualHost pointing to that folder.

4) Run Vue dev server
- Open a terminal in `vue-frontend/`.
- Install dependencies and start dev server:

```powershell
npm install
npm run dev
```

- By default Vite runs at http://localhost:5173
- The Vue app calls the PHP API at `http://localhost/inspectable-api/api/...` (adjust base URL in `vue-frontend/src/lib/api.ts`).

5) Build Vue for production (optional)
- Build static files:

```powershell
npm run build
```

- Serve the built files (`vue-frontend/dist/`) with Apache (copy into a folder under `www`, or create a VirtualHost).

## Mapping Firestore -> MySQL

- Departments -> `departments`
- Locations -> `locations` (FK to departments)
- Users -> `users` (UID becomes string PK), roles -> `user_roles` (link table)
- Inspections -> `inspections` (FK to locations, auditors reference `users`)

See `MYSQL_SCHEMA.md` for definitions; this repo also contains `php-backend/sql/schema.sql` ready to import.

## API endpoints (initial)

- GET/POST/PUT/DELETE `/api/departments.php`
- GET/POST/PUT/DELETE `/api/locations.php`
- GET/POST/PUT/DELETE `/api/inspections.php`
- GET/POST/PUT/DELETE `/api/users.php`

Notes:
- JSON in/out; CORS enabled for http://localhost:5173 by default.
- Adjust `ALLOWED_ORIGIN` in `php-backend/config.php` as needed.

## Data migration

1) Export Firestore data to JSON
- Use the Firebase Admin SDK or Firestore export tooling to dump collections to JSON (e.g., departments, locations, app_users, inspections). Save files like `departments.json`, `locations.json`, `users.json`, `inspections.json`.

2) Import into MySQL
- Place JSON files in `php-backend/scripts/data/`.
- Run `php-backend/scripts/import_json.php` via PHP CLI or from a browser:
  - CLI: `php import_json.php` (from `php-backend/scripts`)
  - Web: copy scripts folder under web root and open in browser (only for local/dev usage)

Importer handles:
- departments -> departments
- locations -> locations (requires departmentId mapping by name or exact id)
- users -> users + user_roles
- inspections -> inspections (maps locationId and auditor ids by string id)

Review/adjust mapping logic in the script to mirror your Firestore document structure.

## Auth replacement

- The original app used Firebase Auth. This skeleton doesn’t include auth.
- Options:
  - Local-only: keep app open without auth.
  - Add session-based PHP auth with email/password.
  - Use a third-party identity provider (Auth0, etc.).

## Environment and URLs

- Vue Dev: http://localhost:5173
- PHP API (example): http://localhost/inspectable-api/api/
- Update Vue API base URL in `vue-frontend/src/lib/api.ts` if your path differs.

## Next steps

- Flesh out endpoints to match all UI needs
- Add pagination, search, validation, error handling
- Implement authentication/authorization
- Port UI flows from Next.js pages to Vue routes
