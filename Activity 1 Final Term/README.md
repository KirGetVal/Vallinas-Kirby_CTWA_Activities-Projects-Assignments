# BPC Complaint Management System: Sign-in / Sign-up

Sign-in and sign-up for the Bulacan Polytechnic College Complaint Management System.
Built with PHP, MySQL, Bootstrap 5 and vanilla JavaScript.

**Live site:** https://bpc-cms.freedev.app/

## Features
- Register a new account (new accounts are always regular users)
- Sign in and go straight to the dashboard for your role
- Three roles: Administrator, Staff, User
- Passwords are hashed, forms are CSRF-protected, and repeated failed sign-ins are temporarily locked
- Light and dark mode
- Sign out from the account menu

## Dashboards
| Role | Page |
|---|---|
| Administrator | `admin/dashboard.php` |
| Staff | `staff/dashboard.php` |
| User | `user/dashboard.php` |

## Demo accounts
| Role | Email | Password |
|---|---|---|
| Staff | staff@cms.local | Staff123! |
| User | user@cms.local | User123! |

The administrator account is private and its password is not shared.

## Run it locally (XAMPP)
1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy this folder to `C:\xampp\htdocs\bpc-auth\`.
3. Open `http://localhost/phpmyadmin`, go to **Import**, choose `database.sql` and click **Import**.
4. Open `http://localhost/bpc-auth/`.

If your MySQL login is not the XAMPP default (`root` with no password), edit `config/database.php`.

## Database (`bpc_cms`)
| Table | Purpose |
|---|---|
| `users` | accounts: name, unique email, hashed password, role |
| `login_attempts` | failed-login counter and lockout |
| `activity_logs` | sign-in, sign-out and registration history |
| `settings` | school name and system name |

## Project structure
```
actions/    login_process.php, register_process.php
admin/ staff/ user/    one dashboard per role
config/     app.php, constants.php, database.php
core/       Auth, Database, Helpers, Validator, bootstrap
includes/   header, footer, layout, alerts
assets/     css, js, logo
database.sql   import this in phpMyAdmin
```

## Before going public
- Set `SEED_DEMO_DATA` to `false` in `config/app.php` once your real accounts exist. This also hides the demo credentials box on the sign-in page.
- Use a dedicated MySQL user with a strong password instead of `root`.
