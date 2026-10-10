# TaskPesa

A micro-task and rewards earning platform where users complete simple
online tasks (surveys, app tests, quick jobs) and cash out their
earnings to M-Pesa. Built to match the system design in **Chapter 3:
Methodologies** — three-tier architecture, the four-table schema
(`users`, `tasks`, `earnings`, `withdrawals`), and the security
measures described in Section 3.5.

## Stack

- **Frontend:** HTML, CSS, vanilla JavaScript
- **Backend:** PHP 8+ (no framework — plain scripts, PDO for the database)
- **Database:** MySQL / MariaDB
- **Payments:** Safaricom M-Pesa Daraja API (B2C payouts)
- **Local dev environment:** XAMPP (Apache + MySQL + PHP), VS Code

## 1. Install with XAMPP

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL** from the control panel.
2. Copy the whole `taskpesa` folder into your XAMPP `htdocs` directory, e.g.:
   - Windows: `C:\xampp\htdocs\taskpesa`
   - macOS: `/Applications/XAMPP/htdocs/taskpesa`
   - Linux: `/opt/lampp/htdocs/taskpesa`
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`), create nothing manually — instead import the schema:
   - Click **Import** → choose `sql/schema.sql` → **Go**.
   - This creates the `taskpesa_db` database, all four tables, and a handful of sample tasks.
4. Visit `http://localhost/taskpesa/` in your browser. You should see the landing page.

The database connection in `config/database.php` defaults to XAMPP's
standard settings (`127.0.0.1`, user `root`, empty password), so no
further configuration is required for local development.

## 2. Configure environment variables (recommended for anything beyond local testing)

Rather than editing PHP files directly, set these as real environment
variables (in Apache's `httpd.conf`/vhost with `SetEnv`, in your
shell, or via your hosting provider's dashboard):

```
TASKPESA_DB_HOST=127.0.0.1
TASKPESA_DB_NAME=taskpesa_db
TASKPESA_DB_USER=root
TASKPESA_DB_PASS=

TASKPESA_MPESA_ENV=sandbox
TASKPESA_MPESA_CONSUMER_KEY=your_key
TASKPESA_MPESA_CONSUMER_SECRET=your_secret
TASKPESA_MPESA_SHORTCODE=your_shortcode
TASKPESA_MPESA_INITIATOR_NAME=your_initiator
TASKPESA_MPESA_INITIATOR_PASSWORD=your_initiator_password
TASKPESA_MPESA_B2C_SECURITY_CRED=your_encrypted_security_credential
TASKPESA_MPESA_CALLBACK_BASE_URL=https://yourdomain.com
```

Get sandbox credentials from the [Safaricom Developer Portal](https://developer.safaricom.co.ke/).
Note that `TASKPESA_MPESA_CALLBACK_BASE_URL` must be a **publicly
reachable HTTPS URL** — Daraja cannot call back to `localhost`. For
local testing, tunnel your XAMPP server with a tool like ngrok and use
the tunnel URL here.

If M-Pesa credentials are left unset, withdrawal requests are still
recorded in the database as `pending` — the app degrades gracefully
instead of crashing, which is useful while developing everything else.

## 3. Project structure

```
taskpesa/
├── config/
│   ├── database.php        PDO connection (env-driven)
│   └── mpesa_config.php    Daraja API credentials (env-driven)
├── includes/
│   ├── bootstrap.php       Secure session start + shared includes
│   ├── functions.php       Output escaping, CSRF, flash messages
│   ├── require_login.php   Auth guard for protected pages
│   ├── header.php / footer.php
├── mpesa/
│   └── MpesaClient.php     OAuth + B2C payout API client
├── assets/
│   ├── css/style.css
│   └── js/main.js
├── sql/
│   └── schema.sql          Tables + seed tasks
├── index.php                Landing page
├── register.php / login.php / logout.php
├── tasks.php                Task listing + "mark complete"
├── dashboard.php            Earnings ledger + threshold progress
├── profile.php               Edit account details / change password
├── withdraw.php              Request an M-Pesa payout + history
└── mpesa_callback.php        Daraja ResultURL / QueueTimeOutURL
```

## 4. Security measures implemented (Section 3.5)

| Measure | Where |
|---|---|
| Bcrypt password hashing | `register.php`, `profile.php`, `login.php` (`password_hash` / `password_verify`) |
| Prepared statements everywhere | All DB access via PDO, `PDO::ATTR_EMULATE_PREPARES => false` |
| Output escaping (XSS prevention) | `e()` helper in `includes/functions.php`, used on every echoed value |
| CSRF protection | `csrf_field()` / `verify_csrf()`, one token per session |
| Secure session management | `includes/bootstrap.php` — HttpOnly + Secure cookies, ID regeneration on login, idle timeout |
| HTTPS | `.htaccess` (enable the redirect block for production) |
| M-Pesa credential handling | `config/mpesa_config.php` reads only from environment variables, never hard-coded |
| Directory protection | `.htaccess` `Require all denied` in `config/`, `includes/`, `sql/`, `mpesa/` |

## 5. Default test flow

1. Register a new account on `/register.php`.
2. Log in, then open **Tasks** and mark a couple of the seeded tasks as complete.
3. Open **Dashboard** to watch the ledger and progress bar update.
4. The default withdrawal threshold is **KSh 500** (see `users.min_withdrawal_threshold`); complete enough tasks to cross it, then try **Withdraw**.
