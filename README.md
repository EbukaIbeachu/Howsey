# HOWSEY

HOWSEY is a free white-label, multi-company property management SaaS built on Laravel 10. It supports company-specific onboarding, branding, role-based access, tenant-isolated data, and a PWA-style experience across desktop and mobile.

This codebase was originally a single-tenant property management system and has been upgraded into a shared-schema multi-tenant SaaS where each company operates in its own branded workspace.

## 1. What The System Does

HOWSEY manages the core workflows of a housing/property management business:

- Buildings
- Units
- Tenants
- Rentals / rent records
- Payments
- Maintenance requests
- Users and roles
- Accounting / ledger / exports
- Notifications
- Branded company settings

Each customer company gets its own:

- company profile
- company-specific login and registration URLs
- company branding
- company users
- company data scope
- notification settings
- document templates

## 2. Platform Model

HOWSEY now operates with two layers:

### Platform layer

This is the HOWSEY owner layer.

- controlled by `super_admin`
- can view and manage all companies
- can access cross-company administration
- can create companies
- can see platform-wide users and company records

### Company layer

This is the customer company layer.

- each company has its own workspace
- each company has its own admin and staff users
- each company only sees its own data
- company branding flows through the company portal, auth screens, and app shell

## 3. Multi-Tenant Architecture

HOWSEY uses a shared-schema tenancy model.

- core business records carry `company_id`
- company context is resolved by middleware
- tenant isolation is enforced through query scoping and access checks
- `super_admin` bypasses company restrictions when appropriate

Important implementation pieces:

- `companies` table
- `company_settings` table
- `company_id` on core records
- `ResolveCompanyContext` middleware
- `BelongsToCompany` trait
- permission gates and policies

## 4. Roles

Current major roles include:

- `super_admin`
- `admin`
- `manager`
- `accountant`
- `tenant`

### Super Admin

The HOWSEY owner/administers the entire SaaS as:

- `admin@howsey.local`

This account is platform-level, not company-level.

### Company Admin

Company admins are tied to one company and should only see users and records for that company.

### Manager

Managers are further restricted and mainly work with assigned tenant/building-related flows.

### Accountant

Accountants focus on accounting/reporting flows.

### Tenant

Tenants are limited users attached to tenant-specific functionality.

## 5. Company Registration And Access Flow

The current public onboarding flow is:

1. Visitor opens the HOWSEY public home page
2. Visitor creates a company from the public company registration flow
3. Company profile is created
4. First company admin is created
5. Company settings and branding are created
6. First company admin is automatically signed in
7. The company gets a dedicated company portal URL

After company creation, that company can distribute its own workspace URL to staff and other users.

## 6. Company-Specific URLs

### Public HOWSEY home

- `/`

### Public company registration

- `/company/register`

### Company portal

- `/c/{company}`

### Company login

- `/c/{company}/login`

### Company staff registration

- `/c/{company}/register`

### Company tenant registration

- `/c/{company}/register-tenant`

These company URLs stay within the same PWA-style visual experience and inherit company branding.

## 7. Branding And PWA Behavior

Each company can have:

- company name
- logo
- primary color
- email header
- receipt header
- address
- support email
- support phone

Branding is reused across:

- company portal
- login page
- staff registration page
- tenant registration page
- app shell / menu / navigation
- receipt/document surfaces
- manifest theme color
- PWA icon route

### Company logo

Company registration includes logo upload. That logo is then reused in the branded workspace and PWA-facing identity where available.

### Company color

Company creation now uses a dropdown of 12 preset brand colors rather than manual hex input.

### Device recognition

When a user visits a company portal on a device, the app remembers that company in session/cookie context. This helps generic public routes like `/`, `/login`, and `/register` reuse the correct company branding on that device.

## 8. User Visibility Rules

Important access rules now in place:

- non-super-admin users do not see `super_admin` in the Users page
- company admins only see users in their own company
- company admins cannot open/edit/delete/toggle users from other companies by URL
- managers remain narrower than admins

## 9. Local Entry Point

This project runs locally through the EasyPHP wrapper entry point:

- `http://127.0.0.1/housing/`

Do not use the blocked legacy project paths under the internal app directory.

Those internal paths are intentionally blocked by the current Apache/EasyPHP structure.

## 10. Important Local Constraints

These are important for this project:

- the physical filesystem folder still remains `okaro` for compatibility with the current EasyPHP wrapper
- visible product name is `HOWSEY`
- local database has been wired for EasyPHP MySQL
- test database is `howsey_test`
- session cookie is `howsey_session_v3_fixed`
- local environment uses `APP_URL=http://127.0.0.1/housing`

## 11. Environment Setup

Install dependencies:

```bash
composer install
```

Create the app key if needed:

```bash
copy .env.example .env
php artisan key:generate
```

Recommended local `.env` values:

```env
APP_NAME="HOWSEY"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1/housing

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=okaro_local
DB_USERNAME=root
DB_PASSWORD=

SESSION_SECURE_COOKIE=false
SESSION_COOKIE=howsey_session_v3_fixed
```

If mail is needed locally:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS=support@howsey.com
MAIL_FROM_NAME="${APP_NAME}"
```

## 12. Database Setup

Run migrations:

```bash
php artisan migrate
```

Seed baseline data:

```bash
php artisan db:seed --class=DatabaseSeeder
```

## 13. Platform Owner Account

The seeded platform owner account is:

- email: `admin@howsey.local`
- password: `ChangeMe123!`

This account should be used to administer HOWSEY at the platform level.

## 14. Local Company Isolation Demo

To quickly create two browser-testable local companies, run:

```bash
php artisan db:seed --class=Database\\Seeders\\LocalCompanyIsolationSeeder
```

This creates:

### Platform owner

- `admin@howsey.local`
- password: `ChangeMe123!`



### Suggested local verification

1. Sign in as `admin@company-a.howsey.local`
2. Verify you only see Company A data
3. Open a private/incognito window
4. Sign in as `admin@company-b.howsey.local`
5. Verify you only see Company B data
6. Confirm Buildings, Tenants, Rentals, Payments, and Users stay isolated

## 15. Public Onboarding Flow

The intended company onboarding journey is:

1. Open the public HOWSEY home page
2. Click `Get Started`
3. Create a company
4. Upload company logo
5. Choose one of the preset brand colors
6. Create the first admin account
7. Get redirected into the branded company workspace
8. Share the company URL with staff and other user roles

## 16. Auth Flow

### Generic public auth

- `/login`
- `/register`
- `/register-tenant`

These can reuse remembered company branding when a company was previously opened on the same device.

### Company-specific auth

Recommended for real company use:

- `/c/{company}/login`
- `/c/{company}/register`
- `/c/{company}/register-tenant`

These lock the user into the correct company workspace and preserve company branding.

## 17. Testing

This project is configured to use MySQL for tests, not SQLite.

Test database:

- `howsey_test`

Run tests:

```bash
php artisan test
```

If needed:

```bash
vendor\bin\phpunit
```

## 18. Backup And Safe Migration Workflow

Use the included MySQL clone helper before risky migration work:

```bash
php database/clone_mysql_database.php 127.0.0.1 3306 root "" okaro_local okaro_local_backup_YYYYMMDD --with-data
```

Note: the local database name still uses the legacy `okaro_local` identifier for compatibility with the current local setup, even though the product is branded as HOWSEY.

Then point the app at the backup DB before running experimental migration work.

## 19. Mobile / PWA Testing

To test from a phone:

1. Keep EasyPHP Apache running
2. Keep the phone on the same network as the PC
3. Open the LAN URL from the phone
4. Test a company portal URL like `/c/company-a`

If the phone shows stale branding:

1. refresh the page fully
2. reopen the PWA
3. if needed, reinstall the PWA shortcut so updated assets are loaded

## 20. Notable Project Files

Core files worth knowing:

- `app/Http/Middleware/ResolveCompanyContext.php`
- `app/Traits/BelongsToCompany.php`
- `app/Providers/AuthServiceProvider.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/RegisterController.php`
- `app/Http/Controllers/PublicCompanyRegistrationController.php`
- `app/Http/Controllers/CompanyController.php`
- `app/Http/Controllers/UserController.php`
- `app/Support/CompanyOnboardingService.php`
- `database/migrations/2026_08_05_000000_add_multi_tenant_saas_foundation.php`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/LocalCompanyIsolationSeeder.php`
- `resources/views/welcome.blade.php`
- `resources/views/layouts/app.blade.php`
- `service-worker.js`

## 21. Production Readiness Status

The system is strong for local testing and controlled demos, but it should still be treated as not fully production-ready yet.

Remaining hardening areas include:

- public signup abuse protection
- email verification and throttling
- fuller feature coverage for the public onboarding flow
- deployment hardening
- queue/scheduler/mail/backup/monitoring setup
- final security review

### Hostinger deployment

For the actual Hostinger shared-hosting rollout, use:

- [HOSTINGER_DEPLOYMENT.md](file:///c:/Program%20Files/EasyPHP-Devserver-17/eds-www/housing/okaro/HOSTINGER_DEPLOYMENT.md)

That guide covers:

- where the Laravel app folder should live
- what goes into `public_html`
- how to set `public_html/index.php`
- the production `.env`
- first-boot artisan commands
- PWA and notification requirements

## 22. Troubleshooting

### EasyPHP shows Forbidden

Use:

- `http://127.0.0.1/housing/`

Do not use blocked internal paths.

### MySQL access denied locally

Confirm `.env` is using local EasyPHP MySQL credentials, usually:

- host `127.0.0.1`
- username `root`
- blank password

### EasyPHP MySQL will not start

Common causes:

- `ibdata1` write permissions
- Apache/MySQL not started as Administrator
- firewall/network mismatch for mobile testing

### Branding looks stale on mobile

Likely causes:

- browser cache
- PWA cached assets

Refresh or reinstall the PWA shortcut after asset changes.

## 23. Summary

HOWSEY is now a company-aware, white-label property management SaaS with:

- platform `super_admin`
- company-specific onboarding
- branded company portals
- company-scoped auth
- company-scoped users
- tenant-isolated data
- PWA-style desktop/mobile experience

For local testing, start at:

- `http://127.0.0.1/housing/`
