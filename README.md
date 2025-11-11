# Inspectable (Vue + PHP + MySQL)

This workspace now targets a local desktop setup using:

- Frontend: Vue 3 + Vite (in `vue-frontend/`)
- Backend: PHP (WAMP/Apache) + MySQL (in `php-backend/`)

See `MIGRATION.md` for full instructions to set up WAMP, import the schema, configure the PHP API, and run the Vue app.

Key entry points:
- Vue app dev: `vue-frontend/` — run `npm install` then `npm run dev`
- PHP API: `php-backend/public/` — copy to `C:\wamp64\www\inspectable-api` (or use a VirtualHost)
- MySQL schema: `php-backend/sql/schema.sql`

## CSV Data Import

The system supports bulk import of departments, locations, and asset inspection data via CSV/Excel files.

**Documentation:**
- 📖 **[CSV Format Guide](CSV_FORMAT_GUIDE.md)** - Complete format specifications, examples, and troubleshooting
- 🚀 **[CSV Quick Reference](docs/CSV_QUICK_REFERENCE.md)** - Quick reference card for users
- 📁 **[Example Files](docs/)** - `example-department-import.csv` and `example-asset-import.csv`

**Key Points:**
- Supervisor field uses **names** (not Staff IDs) - system automatically matches to users
- Department and Location names must match existing records for asset imports
- Department bulk import auto-creates departments and locations
- Supports CSV and Excel (.xlsx, .xls) formats
- Max 10MB per file, 50MB total per upload batch

Legacy Next.js/React code and configs have been removed to simplify the migration.
