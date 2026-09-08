# Run: HamQadam backend (Laravel) on local XAMPP

This project is NOT a `php artisan serve` app locally — it runs under XAMPP's
Apache + MySQL, served from the repo root via the root `.htaccess`
(index.php sits at the project root, not in `public/`).

## How to reproduce the artifacts

1. **Start XAMPP Apache + MySQL** (XAMPP Manager, or `sudo
   /Applications/XAMPP/xamppfiles/manager-osx` / `ctlscript.sh start`).
   Check: `ps aux | grep httpd` shows a master process owned by `daemon`.
2. **MySQL**: root with empty password, database `hamqadam`
   (see `.env`: DB_HOST=127.0.0.1, DB_DATABASE=hamqadam, DB_USERNAME=root).
3. **Storage permissions** — Apache runs as user `daemon`, so after any
   fresh checkout run:
   `chmod -R a+rwX storage bootstrap/cache`
   without this every error is a blank 500 (log writes fail).
4. **PHP version caveat**: XAMPP ships PHP 8.2.4. Laravel 12's *vendor*
   `config/database.php` uses the PHP 8.4+ `\Pdo\Mysql::ATTR_SSL_CA`
   constant and fatals on boot. The vendor file is already patched in this
   checkout with
   `(PHP_VERSION_ID >= 80500 ? \Pdo\Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA)`
   in the mysql + mariadb blocks. The app-level `config/database.php` uses
   the classic constant outright. If `composer install` regenerates vendor,
   re-apply the vendor patch.
5. **Migrations**: `php artisan migrate` (local DB only; do NOT run pending
   legacy migrations blindly — use `--path=` for specific ones).
6. **Admin login (local)**: `admin@gmail.com` / `admin123` (password was
   reset locally to this on 2026-09-08).

## How to run the server

Nothing to start from inside the repo — Apache owns port 443/80 globally:

- URL: **https://localhost/hamqadam_live/** (app root, 200)
- Admin panel: https://localhost/hamqadam_live/admin/login
- Help Center Chats (admin): https://localhost/hamqadam_live/admin/help-chat
- API base for the Flutter app: https://localhost/hamqadam_live/api/v1

If Apache is NOT running (master httpd absent), start it via XAMPP Manager
and wait for `ps -p $(pgrep -x httpd | head -1)` to show a daemon-owned
master with parent pid 1. Apache is fully detached by design — do not
`nohup` it from a thread shell.
