# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Mason Construction Services Inc. website — a hybrid static frontend + PHP/MySQL backend. The frontend consists of static HTML5/Bootstrap 4 pages; the backend handles contact form submissions and provides an admin dashboard.

## Commands

```bash
# Install dependencies
npm install

# Development server with hot reload (http://localhost:8080)
npm run dev

# Production-like static server (http://localhost:3000)
npm start

# Minify CSS: css/styles.css → css/styles.min.css
npm run build:css
npm run build
```

**Database setup (requires MySQL/phpMyAdmin):**
```bash
# 1. Run SQL to create tables
#    Execute database/setup.sql in phpMyAdmin

# 2. Create an admin user
php database/create-admin.php <username> <password> <full_name> <email>
```

## Architecture

### Frontend
- Static HTML pages in the root directory (`index.html`, `about.html`, `services.html`, `contact.html`, service-specific portfolio pages, blog pages)
- CSS: `css/styles.css` is the editable source; `css/styles.min.css` is the minified build output (do not edit directly)
- `assets/css/style-liberty.css` contains Bootstrap 4 + template base styles
- `js/contact-api.js` handles contact form submission via `fetch()` to the PHP API

### Backend (PHP API)
Located in `api/` — requires a PHP + MySQL server (production: Bluehost shared hosting).

**Request flow for contact form:**
```
contact.html → js/contact-api.js → POST /api/submit-contact.php
    → api/classes/Contact.php (validation, rate limiting, DB insert, email)
    → JSON response
```

**Admin flow:**
```
/admin/index.html → POST /api/login.php → session → /admin/dashboard.html
    → GET /api/get-contacts.php, GET /api/analytics.php
    → POST /api/update-contact.php
```

**Key backend files:**
- `api/config.php` — DB credentials, CORS, session config, shared helper functions (`sendResponse`, `sendError`, `sanitize`, `requireAuth`)
- `api/classes/Database.php` — PDO singleton
- `api/classes/Contact.php` — contact submission logic (rate limit: 3/IP/hour)
- `api/classes/Admin.php` — bcrypt auth, session management

### Database
Two tables defined in `database/setup.sql`:
- `contact_submissions` — stores contact form data with status workflow: `new → read → in_progress → resolved/spam`
- `admin_users` — admin accounts with bcrypt password hashes

### Admin Dashboard
`/admin/` is password-protected via `.htaccess`. `admin/js/admin-script.js` drives the dashboard UI with Chart.js analytics charts.

## API Endpoints

| Method | Path | Auth | Purpose |
|--------|------|------|---------|
| POST | `/api/submit-contact.php` | None | Submit contact form |
| POST | `/api/login.php` | None | Admin login |
| POST | `/api/logout.php` | Session | Admin logout |
| GET | `/api/get-contacts.php` | Session | List submissions (params: `status`, `search`, `limit`, `offset`) |
| POST | `/api/update-contact.php` | Session | Update contact status/notes |
| GET | `/api/analytics.php` | Session | Aggregated stats |

## Deployment

- **Frontend-only** (no PHP): deploy root HTML/CSS/JS files to GitHub Pages, Netlify, or Vercel. Contact form will not function without a PHP host.
- **Full stack**: upload all files to Bluehost via FTP/cPanel; import `database/setup.sql` via phpMyAdmin; run `create-admin.php` once then delete it.
