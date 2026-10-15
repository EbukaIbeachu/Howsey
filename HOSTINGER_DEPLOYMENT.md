# HOWSEY Hostinger Deployment

This guide prepares the current Laravel 10 HOWSEY app for a standard Hostinger shared-hosting deployment.

## Recommended Hostinger Setup

- Hosting type: `Custom PHP / HTML website`
- PHP version: `8.2`
- Database: `MySQL`
- Domain: `howsey.site`

## Expected Production Layout

Use the standard Laravel split:

- application code lives outside `public_html`
- contents of `public/` go inside `public_html`

Suggested layout:

```text
home/
  howsey/                <- full Laravel app except public assets
  public_html/           <- contents of howsey/public
```

## Files Added For Production

This repo now includes production-facing public assets needed for Hostinger:

- `public/service-worker.js`
- `public/offline.html`
- `.env.example`

These are important for the PWA install flow and offline fallback in production.

## 1. Upload The App

Upload the Laravel app to a folder outside `public_html`, for example:

```text
/home/your-hostinger-user/howsey
```

Then upload the contents of `public/` into:

```text
/home/your-hostinger-user/public_html
```

Important:

- copy the **contents** of `public/`, not the `public` folder itself
- `index.php`, `.htaccess`, `service-worker.js`, `offline.html`, and asset files must end up directly inside `public_html`

## 2. Point `public_html/index.php` To The App Folder

Edit `public_html/index.php` so the two path lines point to the real app folder.

Example:

```php
require __DIR__.'/../howsey/vendor/autoload.php';

$app = require_once __DIR__.'/../howsey/bootstrap/app.php';
```

Adjust `howsey` to the real folder name you uploaded.

## 3. Create The Production `.env`

Copy `.env.example` to `.env` inside the app folder and fill in the real values.

Minimum required settings:

```env
APP_NAME="HOWSEY"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://howsey.site

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_hostinger_database
DB_USERNAME=your_hostinger_database_user
DB_PASSWORD=your_hostinger_database_password

CACHE_DRIVER=file
SESSION_DRIVER=file
FILESYSTEM_DISK=public
SESSION_SECURE_COOKIE=true
```

If you want mail and push notifications working fully, also set:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=support@howsey.site
MAIL_PASSWORD=your_hostinger_email_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=support@howsey.site
MAIL_FROM_NAME="${APP_NAME}"

WEB_PUSH_VAPID_PUBLIC_KEY=...
WEB_PUSH_VAPID_PRIVATE_KEY=...
WEB_PUSH_VAPID_SUBJECT=mailto:support@howsey.site
```

## 4. Run First-Boot Commands

From the Laravel app folder:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder --force
php artisan storage:link
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
```

## 5. Required Folder Permissions

Make sure these folders are writable:

- `storage/`
- `bootstrap/cache/`

If Hostinger file permissions need to be adjusted, start with:

- folders: `755`
- files: `644`

Only increase permissions if Hostinger specifically requires it.

## 6. PWA Notes

The app is now prepared so Hostinger serves:

- `/service-worker.js`
- `/offline.html`
- `/manifest.webmanifest`
- `/pwa-icon.png`

Expected production behavior:

- main HOWSEY root home stays clean and does not show the public install prompt
- company portals can show the PWA install flow
- authenticated app pages can show notification opt-in

## 7. Notifications

For PWA notifications to work in production:

1. the site must be on HTTPS
2. VAPID keys must be present in `.env`
3. browser notifications must be allowed by the user

Without the VAPID keys, the app still runs, but push subscription endpoints will report that web push is not configured.

## 8. Hostinger Pre-Go-Live Checklist

- `APP_URL` matches the final HTTPS domain exactly
- `.env` exists and has a valid `APP_KEY`
- database credentials are correct
- `storage:link` completed
- `public_html/index.php` points to the correct app folder
- `storage/` and `bootstrap/cache/` are writable
- `APP_DEBUG=false`
- VAPID keys added if notifications are required
- super admin login tested
- company portal login tested
- one lease PDF download tested
- one logo upload tested
- one PWA install tested from a company portal

For this deployment, the intended values are:

- domain: `https://howsey.site`
- app folder: `/home/your-hostinger-user/howsey`

## 9. First Smoke Test

After deployment, verify:

1. root home loads
2. `admin@howsey.local` can log in
3. companies index loads
4. company portal loads
5. logo upload works
6. lease agreement page opens
7. PWA install works from company portal
8. notification permission button appears only in installed/authenticated app flow

## 10. Recommended Next Hardening

Before a real public launch, the next worthwhile production tasks are:

- rate limiting public registration and login endpoints
- adding email verification and password reset mail validation
- setting up backups for the MySQL database
- configuring scheduled tasks if you later add queued reminders
- replacing the local `admin@howsey.local` password after first production login
