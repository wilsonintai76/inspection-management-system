# Inspectable (Vue + PHP + MySQL)

This workspace now targets a local desktop setup using:

- Frontend: Vue 3 + Vite (in `vue-frontend/`)
- Backend: PHP (WAMP/Apache) + MySQL (in `php-backend/`)

See `MIGRATION.md` for full instructions to set up WAMP, import the schema, configure the PHP API, and run the Vue app.

Key entry points:
- Vue app dev: `vue-frontend/` — run `npm install` then `npm run dev`
- PHP API: `php-backend/public/` — copy to `C:\wamp64\www\inspectable-api` (or use a VirtualHost)
- MySQL schema: `php-backend/sql/schema.sql`

Legacy Next.js/React code and configs have been removed to simplify the migration.
