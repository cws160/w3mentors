# W3Mentors Migration Guide

## What changed

The legacy W3Mentors stack (PHP Fatbit MVC + IonCube + `license.txt`) is being replaced with:

| Layer | Old | New |
|-------|-----|-----|
| Backend | PHP 8.1 + Fatbit + IonCube (`library/core/`) | **Laravel 11 API** (`backend/`) |
| Frontend | PHP views + jQuery/Bootstrap | **React + Vite** (`frontend/`) |
| License | `license.txt` + IonCube validation | **Removed** — not used by new stack |
| Auth | Session + MD5 passwords | **Sanctum Bearer tokens** + legacy MD5 support |

## License & IonCube — important

- **`license.txt` has been deleted.** The new Laravel API does not read it.
- **IonCube files in `library/core/` cannot be decoded.** They are intentionally obfuscated by FATbit. Do not try to "break" them — use the Laravel API instead.
- The old PHP app (`public/`, `application/`, `manager/`) still depends on IonCube if you run it. Treat it as **legacy/deprecated**.

## Quick start

### 1. Install PHP 8.2+ and Composer

On Windows, use [Laragon](https://laragon.org/) or [XAMPP](https://www.apachefriends.org/) with PHP 8.2+.

### 2. Backend (Laravel API)

```bash
cd backend
copy .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

### 3. Frontend (React)

**Keep the Laravel API running** while you use the site. Vite proxies `/api` to `http://127.0.0.1:8000`. If only `npm run dev` is running, the home page and dashboards show “Cannot reach the API”.

```bash
cd frontend
npm install
npm run dev
```

Open http://localhost:5173

**Windows (both servers):** from the repo root, run `powershell -ExecutionPolicy Bypass -File scripts/dev.ps1` — it starts `php artisan serve` then Vite.

### 4. Database

Use your existing MySQL database (`database/sample.sql` already imported). Set credentials in `backend/.env`.

### Demo / reference users

Legacy W3Mentors demo sign-in prefills **lydia.deckow@dummyid.com** / **lydia@123** (teacher). The Laravel API exposes the same via `demo_login` on `/api/v1/site/bootstrap` when `DEMO_LOGIN_ENABLED=true` (default in `APP_ENV=local`).

```bash
cd backend
php artisan db:seed --class=ReferenceUsersSeeder
```

This upserts the teacher and learner (`zigepu@mailinator.com`, password `lydia@123` by default) with verified, active accounts. The React login modal prefills these fields and offers Teacher / Learner quick buttons.

## W3Mentors UI parity (React)

The React app uses **original W3Mentors CSS** copied from `application/views/css/`:

```powershell
cd frontend
npm run copy:css
npm run dev
```

### Implemented with W3Mentors design
- Header / footer / navigation (from DB via `/api/v1/site/bootstrap`)
- Home page sections (hero, categories, languages, courses, teachers, testimonials)
- Login **popup modal** (same markup classes as `login-form-popup.php`)
- Courses list, detail, lecture viewer, my courses
- Route map for all public pages (placeholders for not-yet-migrated modules)

### Still to migrate (same design, needs Laravel API + React pages)
- Checkout modal wizard (`cart-functions.js` — 4 steps)
- Teacher profile, group classes, blog, forum, FAQ, contact
- Dashboard (47 areas) + Admin manager (87 areas)
- Payment gateways, wallet, chat, quizzes, certificates

See `frontend/src/routes/AppRoutes.tsx` for full page inventory.

## API coverage

### Phase 1 — done
- User login / register / logout (legacy MD5 password compatible)
- Admin login
- Teachers list
- User lessons list (1:1 tutoring)
- Profile read/update

### Phase 2 — done (Courses)
- Course catalog with search & sort
- Course detail with enrollment status
- Curriculum (sections + lectures)
- Intended learners (learning outcomes, requirements, audience)
- Trial/preview lectures (public access)
- Lecture viewer with resources
- My courses (enrolled list)
- Progress tracking (mark lecture complete, % progress)

## Still to migrate (Phase 3+)

These exist in the old PHP app but are **not yet** in the Laravel API:

- Orders, cart, checkout, payments (Stripe, PayPal, etc.)
- Course purchase / enrollment flow
- Group classes & packages
- Quizzes, flashcards, certificates
- Forum, chat, notifications
- Wallet, withdrawals, subscriptions
- Admin panel (94 controllers)
- Meeting tools (Zoom, Jitsi)
- Cron jobs, reports, exports
- File uploads / media
- Multi-language labels

Estimate: **175 database tables**, **169 controllers**, **150 models** — plan phased migration by business priority.

## Recommended migration order

1. Auth & users ✅
2. Courses, sections, lectures ✅
3. Lessons & teacher availability
4. Orders & payments
5. Group classes
6. Admin APIs
7. Remaining modules

## Legacy folder status

| Folder | Status |
|--------|--------|
| `backend/` | **Active** — use this |
| `frontend/` | **Active** — use this |
| `application/`, `dashboard/`, `manager/`, `library/` | Legacy — keep for reference during migration |
| `license.txt` | **Removed** |
| `public/` (old entry) | Legacy — point web server to React build + Laravel `public/` instead |

## Deploying production

1. Build React: `cd frontend && npm run build`
2. Serve Laravel from `backend/public`
3. Optionally serve React static files from CDN or same domain
4. Set `FRONTEND_URL` and CORS in `backend/.env`
