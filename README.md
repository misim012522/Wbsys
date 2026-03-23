# QueueLess

**Smart Appointment & Queue Management System for School Offices**

The person at the office (e.g. **enrollment officer**) **creates their own account**, picks their office, then **generates the QR code** for that office. End users (students/visitors) **scan the QR** to get a queue number or book an appointment. The officer **accepts appointments** and can **remind** visitors using the contact info they provided. No separate “admin” — the officer is the one in charge of the queue for their office.

## Flow

1. **Officer** (e.g. enrollment officer) goes to the site and **creates an account**: name, email, contact number, password, and **which office they’re in** (Registrar, Cashier, etc.).
2. After login, the officer sees **their office only**: **QR code** (display/print), **queue** (call next, update status), **appointments** (accept, complete, cancel), and **reports**. They use the stored contact info to **remind** end users.
3. **End users** scan the office QR → open the public page → enter details and get a queue number or book an appointment (no account). They get a reference code to track their position.
4. Officer **reminds** them via the email/phone they gave when scanning.

## Features

- **Officer self-registration** — Create account with name, email, contact number, password, and office
- **QR code** — Officer generates one QR for their office; scanning opens the public office page
- **Get queue number / Book appointment** — End user enters name, email/phone, type; officer sees contact info to remind
- **Track position** — Public page by reference code (no login)
- **Officer dashboard** — Queue (call next, complete), appointments (accept/complete/cancel), reports for their office

## Setup

**Requirements:** PHP 8.2+, Composer, MySQL (running), Node/npm for frontend assets.

1. Create a MySQL database (e.g. `final_app`).
2. Configure `.env`:

```bash
cd final-app
cp .env.example .env
php artisan key:generate
```

Set in `.env`:

- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1` (or your MySQL host)
- `DB_PORT=3306`
- `DB_DATABASE=final_app` (or your database name)
- `DB_USERNAME=` your MySQL user
- `DB_PASSWORD=` your MySQL password

3. Run migrations and seed:

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

Then open http://127.0.0.1:8000

## Getting started

1. Run migrations and seed (creates default offices: Registrar, Cashier, Guidance, Clinic).
2. Open the site and click **Create account**.
3. Register as the officer for one of the offices (name, email, phone, password, office).
4. Log in → you’ll see your office queue, **QR code**, and reports. Display or print the QR so visitors can scan.
5. End users do not log in; they scan the office QR code.

## Tenant App

- **Tenant app**: for end users (public). Entrypoint: `/tenant` (proxies to the public office pages like `/o/{slug}` and tracker `/t/{referenceCode}`).

You can continue to use the existing routes (`/admin`, `/o/{slug}`, `/t/{referenceCode}`) — `/tenant` is a lightweight entrypoint that groups the public experience.

Local subdomain / hosting notes

- The app supports tenant resolution by domain or subdomain via the `ResolveTenant` middleware (it binds `current_tenant` / `current_tenant_id`). To test subdomains locally, add entries in your `hosts` file like:

```text
127.0.0.1    default.localhost
127.0.0.1    acme.localhost
```

Then point your browser to `http://acme.localhost:8000/o/registrar` (or use the `subdomain` field on the tenant record). For more realistic hostnames, map `acme.yourapp.test` to `127.0.0.1`.

- When using subdomains, ensure your web server or local PHP server accepts the host header (the built-in `php artisan serve` does). 

ResolveTenant behavior

- Middleware: `app/Http/Middleware/ResolveTenant.php` resolves `Tenant::active()` by `domain` first or by the first subdomain segment and binds `current_tenant` and `current_tenant_id` to the container. Authenticated users also set the tenant via `EnsureTenantContext`.

Deployment & subdomain DNS examples

Below are common deployment patterns and example configs you can adapt. These aim to host a single Laravel application that serves tenant subdomains.

1) Wildcard subdomains (recommended for many tenants)

- DNS: create a wildcard A record for your domain pointing to your server:

```
*.example.com    A    203.0.113.12
example.com      A    203.0.113.12
```

- Nginx (example): single site serving the main app and tenant hosts. Use `server_name` with the apex domain and a wildcard.

```
server {
	listen 80;
server_name example.com *.example.com;
	root /var/www/yourapp/public;

	index index.php;

	location / {
		try_files $uri $uri/ /index.php?$query_string;
	}

	location ~ \.php$ {
		fastcgi_pass unix:/run/php/php8.2-fpm.sock;
		fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
		include fastcgi_params;
	}
}
```

- SSL: For wildcard certificates use Let's Encrypt with DNS challenge (ACME) or purchase a wildcard cert. Example: `certbot -d example.com -d "*.example.com" --manual --preferred-challenges dns`.

2) Session / cookie considerations

- If you use subdomains and want cross-subdomain authentication (single sign-on across subdomains), set `SESSION_DOMAIN=.example.com` in `.env` and ensure `APP_URL` uses your main domain (for example `https://example.com`). Be careful — setting session cookies for a wide domain may have security implications.

3) Trusted proxies & host header

- In containerized or proxied setups, configure `TRUSTED_PROXIES` and `TRUSTED_HOSTS` if needed, and ensure the reverse proxy forwards the original `Host` header so `ResolveTenant` can detect the requested host.

4) Let's Encrypt wildcard certificates

- Wildcard certificates require DNS validation. Use your DNS provider's API with Certbot or a DNS management tool to automate issuance and renewal.

5) Local testing with hosts file

- For local testing add entries to your hosts file (Windows: `C:\Windows\System32\drivers\etc\hosts`):

```
127.0.0.1    acme.localhost
127.0.0.1    default.localhost
```

Then use `php artisan serve` or your local webserver and hit `http://acme.localhost:8000/o/registrar` to simulate a tenant domain.

6) Common deployment checklist

- Run `php artisan migrate --force` during deploy.
- Seed plans and roles with `php artisan db:seed --class=\Database\Seeders\SaasSeeder` for initial plan data.
- Configure queue workers and schedulers (supervisord/systemd) for background jobs.
- Monitor logs and set up backup for uploads stored under tenant paths (use `Tenant::storagePath()` when storing uploads).



---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
