# W3Mentors React Frontend

Modern React UI for the Laravel API.

## Setup

```bash
npm install
copy .env.example .env
npm run dev
```

App runs at http://localhost:5173

## Environment

| Variable | Default |
|----------|---------|
| `VITE_API_URL` | `http://localhost:8000/api/v1` |

## Pages

- `/` — Home
- `/courses` — Course catalog
- `/courses/:id` — Course detail
- `/teachers` — Teacher listing
- `/login`, `/register` — Auth
- `/dashboard` — User dashboard (protected)

## Build

```bash
npm run build
```

Output in `dist/` — deploy to any static host or serve behind nginx.
