# SecureAuth — PHP + MySQL Login System

A complete, self-contained registration/login system built on your schema and
secure-coding logic, with a full front end (HTML/CSS/JS) added on top.

## What's included

```
secure-auth/
├── config.php          # DB credentials + security constants (edit this first)
├── db.php              # PDO connection helper
├── schema.sql           # CREATE DATABASE / CREATE TABLE
├── includes/
│   └── helpers.php      # CSRF tokens, escaping, require_login()
├── register.php         # Registration page (form + handling combined)
├── login.php            # Login page, with attempt-tracking & lockout
├── dashboard.php         # Protected page — only reachable when logged in
├── logout.php            # Destroys the session
└── assets/
    ├── style.css        # Vault-themed UI
    └── script.js         # Password show/hide, strength meter, match check
```

## Setup

1. Create the database:
   ```bash
   mysql -u root -p < schema.sql
   ```
2. Open `config.php` and set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` to match
   your MySQL setup.
3. Serve the folder with PHP's built-in server (for local testing):
   ```bash
   php -S localhost:8000 -t secure-auth
   ```
   Then visit `http://localhost:8000/register.php`.
4. For production, serve over **HTTPS** and uncomment
   `ini_set('session.cookie_secure', '1');` in `config.php`.

## Security features included

- **Prepared statements everywhere** (PDO, real prepares — no SQL injection).
- **Passwords hashed** with `password_hash()` / verified with `password_verify()`.
- **CSRF tokens** on both the registration and login forms.
- **Account lockout**: after `MAX_FAILED_ATTEMPTS` (default 5) wrong passwords,
  the account locks for `LOCKOUT_MINUTES` (default 15), using the
  `failed_attempts` / `locked_until` columns from your schema.
- **Generic error messages** on login ("Invalid login details.") so an
  attacker can't tell whether a username/email exists.
- **Session hardening**: `session_regenerate_id(true)` on login, strict mode,
  HttpOnly + SameSite cookies, full session teardown on logout.
- **Output escaping** (`htmlspecialchars`) everywhere user data is echoed.
- **Server-side validation** for every field, with client-side checks in
  `script.js` purely as a UX layer (never trusted on their own).

## Notes / things to adapt for your project

- The lockout window resets `failed_attempts` to 0 on a successful login.
- `register.php` and `login.php` reuse the same CSS/JS so you get one
  consistent look; the dashboard page shares the stylesheet too.
- There's no email verification or password-reset flow yet — both are natural
  next additions if you need them (say the word and I can add them).
