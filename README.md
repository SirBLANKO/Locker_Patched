# Locker

A small shared-secret shelf for one student organization. Members store door codes, social logins, and the odds and ends that used to live in a group chat. Each account only sees the items it stored.

Plain PHP, SQLite, no framework.

## Features

- Register with a `.edu` email, sign in, sign out
- Forgot-password / same-day reset token
- Personal vault: label, secret, notes
- Advisor account exists in the seed data for org-wide holds

## Run locally

PHP 8.2+ with the PDO SQLite driver (`php-sqlite3` on Ubuntu/Debian).

```bash
php -S 0.0.0.0:3000 -t public public/router.php
```

Open [http://localhost:3000](http://localhost:3000). The database is created and seeded on first request at `data/locker.db`.

## Run with Docker

```bash
docker compose up --build
```

The app listens on port 3000. Data lives in the `locker-data` volume.

## Demo accounts

| Email | Password | Notes |
|---|---|---|
| `maya@campus.edu` | `campus123` | Has a couple of club secrets |
| `devon@campus.edu` | `campus123` | Empty shelf |

Register your own account if you want; it just has to end in `.edu`.

## Project layout

```
public/index.php      Front controller
public/router.php     PHP built-in server router
public/styles.css
src/bootstrap.php
src/db.php            Schema + first-run seed
src/crypto.php        Password and reset-token helpers
src/auth.php
src/vault.php
templates/
```

## Assignment notes

Host this somewhere your classmates and instructor can reach. Walk the running app until you can explain:

- how a password becomes the value stored in SQLite
- what happens after someone clicks “Forgot password”
- how vault rows are scoped to an account
- who the advisor account is for, even if you cannot sign in as them yet

Then look for a security defect in the running system, document how to trigger it, and patch it without breaking normal sign-in. Submit the hosted URL, a short architecture sketch, the writeup, and the patched repo.
