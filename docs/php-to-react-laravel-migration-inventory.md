# PHP to React + Laravel Migration Inventory

This project still contains the legacy W3Mentors PHP application alongside the new React frontend and Laravel API. The goal is to retire the PHP runtime by converting each PHP feature into:

- React pages/components under `frontend/src`
- Laravel API routes/controllers/services/models under `backend/app` and `backend/routes/api.php`
- Static assets under `frontend/public` or Laravel storage/media endpoints

## Current Legacy PHP Scope

Approximate PHP files still present in legacy/runtime folders:

| Area | Count | Target |
| --- | ---: | --- |
| `application/controllers` | 28 | Public Laravel API controllers + React public routes |
| `dashboard/controllers` | 47 | Authenticated learner/teacher API controllers + React dashboard routes |
| `manager/controllers` | 94 | Admin API controllers + React admin routes |
| `application/models`, `manager/models` | 218 | Eloquent models, services, query builders, export jobs |
| `application/views`, `dashboard/views`, `manager/views`, `apiviews` | 788 | React components/pages/modals/tables/forms |
| `plugins`, `public`, `library` PHP | remaining | Laravel services, queues, integrations, or retired legacy-only code |

Total counted in legacy/runtime PHP folders: about `1260` PHP files.

## Migration Rules

1. Do not run or depend on IonCube/Fatbit runtime for new work.
2. Each old PHP controller action should become an API endpoint or be explicitly marked obsolete.
3. Each old PHP view should become either a React page, reusable component, modal, or table/list config.
4. Old model/search classes should become Eloquent models plus service/query classes.
5. Payment, meeting, media upload, email, cron, and export behavior must be migrated as Laravel services/jobs, not embedded in React.
6. A PHP file is only considered migrated when the equivalent React route/UI and Laravel API behavior exist and the old route is no longer required.

## Recommended Conversion Order

### Phase 0 - Stabilize Current React Build

The frontend currently has TypeScript compile errors. Fix this before adding more migration work so every conversion can be verified with `npm.cmd run build --prefix frontend`.

Known build blockers include:

- Invalid JSX custom `<date>` elements
- Unused imports/variables under strict TypeScript options
- Conflicting global declarations for legacy calendar helpers
- A few mismatched dashboard row types

### Phase 1 - Public Frontend Gaps

Convert remaining public PHP pages and detail flows:

| Legacy PHP | React target | Laravel target | Status |
| --- | --- | --- | --- |
| `application/views/cart/*` | Checkout/cart modal wizard | Cart/order/payment APIs | Pending |
| `application/views/payment/*` | Payment status pages | Payment gateway callbacks/services | Pending |
| `application/views/subscription-plans/index.php` | Subscription plans page | Subscription plan APIs | Placeholder exists |
| `application/views/group-classes/view.php` | Group class detail page | Group class detail/booking APIs | Placeholder exists |
| `application/views/teacher-request/form*.php` | Teacher application wizard | Teacher request APIs | Placeholder exists |
| `application/views/certificates/*` | Certificate view/evaluation | Certificate APIs | Pending |
| `application/views/videos/*` | Video listing/player | Video content APIs | Pending |
| `application/views/mobile`, `pwa`, `sitemap`, `maintenance` | React/static pages or Laravel endpoints | Content/system endpoints | Pending |

### Phase 2 - Checkout, Orders, Wallet, Payments

This is the most important business blocker. Paid course enrollment currently returns a message that checkout is not available.

Legacy areas:

- `application/controllers/CartController.php`
- `application/controllers/PaymentController.php`
- `application/views/cart/*`
- `application/views/payment/*`
- `plugins/payments/*`
- `dashboard/controllers/OrdersController.php`
- `dashboard/controllers/WalletController.php`
- `manager/controllers/OrdersController.php`
- `manager/controllers/PaymentMethodsController.php`
- `manager/controllers/WalletController.php`
- `manager/controllers/WithdrawRequestsController.php`

Laravel targets:

- `CartController`, `CheckoutController`, `PaymentController`
- `OrderService`, `CheckoutService`, `WalletService`, `PaymentGatewayService`
- Gateway adapters for Stripe, PayPal, Paystack, Payfast, Mpesa, PayGate, Authorize, Bank Transfer, Wallet
- Webhook/callback routes with signed verification

React targets:

- Cart/checkout modal
- Payment method selector
- Payment success/failure/cancel pages
- Dashboard wallet and withdrawal flows
- Dashboard/admin order details and invoice views

### Phase 3 - Learner/Teacher Dashboard Completion

Many dashboard list pages exist in React, but several are not fully editable yet.

Legacy areas:

- `dashboard/controllers/*`
- `dashboard/views/account/*`
- `dashboard/views/classes/*`
- `dashboard/views/courses/*`
- `dashboard/views/lessons/*`
- `dashboard/views/packages/*`
- `dashboard/views/plans/*`
- `dashboard/views/quizzes/*`
- `dashboard/views/questions/*`
- `dashboard/views/flashcards/*`
- `dashboard/views/forum/*`
- `dashboard/views/chats/*`

React targets:

- Complete CRUD forms for courses, lectures, resources, notes, classes, packages, lesson plans, quizzes, questions, flashcards
- Calendar views for lessons/classes/availability
- Chat thread/message UI
- Certificate and quiz attempt/review screens

Laravel targets:

- CRUD APIs for the same modules
- Upload/media APIs
- Validation and authorization policies

### Phase 4 - Admin Manager Migration

The admin area is the largest remaining section: `manager/controllers` has about 94 controllers.

Group admin modules into batches:

1. Admin auth, profile, permissions, dashboard
2. Users, teachers, teacher requests, GDPR
3. Content: CMS pages, content blocks, slides, testimonials, blog, FAQ
4. Taxonomy: categories, languages, labels, preferences, locations
5. Commerce: orders, course orders, refunds, subscriptions, commissions, payouts, wallet
6. Learning: courses, classes, packages, lessons, quizzes, questions, certificates
7. Forum/community: forum, tags, reports, abusive words
8. System: configurations, themes, navigation, meta tags, payment methods, meeting tools
9. Reports/exports: sales, settlements, teacher performance, hours taught, wallet balance, affiliate

React target:

- Create `frontend/src/admin` with admin shell, navigation, data tables, forms, detail pages, report/export screens.

Laravel target:

- Add `backend/app/Http/Controllers/Api/V1/Admin/*`
- Add admin policies/guards or role middleware
- Convert manager export models into queued export services

### Phase 5 - Integrations, Jobs, and System Services

Legacy integration code should move to Laravel services/jobs:

- `plugins/payments/*`
- `plugins/meetings/*`
- `plugins/videos/*`
- `plugins/translator/*`
- `plugins/conversion/*`
- `application/controllers/CronController.php`
- `application/utilities/GoogleCalendar.php`
- `application/utilities/FatMailer.php` behavior via mailables/services

Targets:

- Laravel queues/jobs for cron, reminders, reports, email, payouts, recordings
- Service interfaces for meeting/payment/video/translation providers
- Environment-driven configuration

## Definition of Done Per Module

A module is migrated when:

1. React route/page exists.
2. Laravel API endpoints exist.
3. Existing DB tables are read/written through Laravel services/models.
4. Auth, permissions, validation, empty states, loading states, and errors work.
5. Legacy PHP page/controller is no longer linked from the new UI.
6. `npm.cmd run build --prefix frontend` passes.
7. `php artisan route:list --path=api/v1` passes.
8. A smoke test of the main workflow succeeds.

## Immediate Next Work

1. Fix current React TypeScript build.
2. Replace public placeholders:
   - `group-classes/:slug`
   - `subscription-plans`
   - `teacher-request/form`
3. Build checkout/order/payment APIs and React checkout flow.
4. Continue dashboard CRUD modules.
5. Start admin manager React/Laravel area after checkout and dashboard essentials are stable.
