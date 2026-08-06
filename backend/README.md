# W3Mentors Laravel API

Open-source Laravel API replacing the W3Mentors Fatbit/IonCube monolith. **No license file or IonCube required.**

## Requirements

- PHP 8.2+
- Composer
- MySQL 8 (existing W3Mentors database)

## Setup

```bash
cd backend
copy .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

API base URL: `http://localhost:8000/api/v1`

## Environment

Copy `.env.example` to `.env` and set your MySQL credentials (same database as the legacy app).

`LEGACY_PASSWORD_SALT` must match `PASSWORD_SALT` in `conf/conf-common.php` so existing users can log in.

## API Endpoints

| Method | Endpoint | Auth |
|--------|----------|------|
| GET | `/api/v1/health` | No |
| POST | `/api/v1/auth/login` | No |
| POST | `/api/v1/auth/register` | No |
| POST | `/api/v1/auth/logout` | Bearer |
| GET | `/api/v1/auth/me` | Bearer |
| GET | `/api/v1/courses` | No |
| GET | `/api/v1/courses/slug/{slug}` | No |
| GET | `/api/v1/courses/{id}` | No |
| GET | `/api/v1/courses/{id}/curriculum` | Optional |
| GET | `/api/v1/courses/{id}/intended-learners` | No |
| GET | `/api/v1/courses/{id}/lectures/{lectureId}` | Optional |
| GET | `/api/v1/my/courses` | Bearer |
| GET | `/api/v1/my/courses/{id}` | Bearer |
| POST | `/api/v1/my/courses/{id}/start` | Bearer |
| POST | `/api/v1/my/courses/{id}/progress` | Bearer |
| GET | `/api/v1/teachers` | No |
| GET | `/api/v1/teachers/{id}` | No |
| GET | `/api/v1/lessons` | Bearer |
| POST | `/api/v1/admin/auth/login` | No |

## Migration status

Phase 1 (done): Auth, users, basic courses/teachers/lessons.

Phase 2 (done): Full courses module — curriculum, intended learners, lecture viewer, enrollment progress, my courses.

Phase 3 (pending): Orders, payments, cart, course purchase.

See `../MIGRATION.md` for full plan.
