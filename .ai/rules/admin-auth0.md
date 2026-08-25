---
paths:
  - 'app/Auth/**'
  - 'app/Http/Controllers/Auth/**'
  - 'app/Http/Middleware/EnsureAdminAccess.php'
  - 'app/Services/Auth/**'
  - 'app/Models/User.php'
  - 'resources/views/pages/admin/login.blade.php'
  - 'resources/views/pages/admin/pending-access.blade.php'
  - 'resources/views/pages/admin/users.blade.php'
  - 'routes/web.php'
  - 'bootstrap/app.php'
  - 'config/services.php'
---

# Admin Auth0

Staff sign in with email/password and optionally Auth0 (Socialite OIDC). Do not install `auth0/login`. Keep Laravel's `web` guard and `POST /admin/logout` (app session only — no Auth0 SLO).

`users.is_admin` gates the console (`EnsureAdminAccess`). Existing/seeded users stay admins. New Auth0 users are JIT with `is_admin=false` and `password=null` until granted on ผู้ใช้. Match Auth0 `sub` first, then case-insensitive email (do not steal an account that already has a different `auth0_sub`). Auth0 accepts only emails whose host is exactly `sru.ac.th` (`Auth0Config::allowsEmail`, `config('services.auth0.allowed_email_domain')`); reject before lookup/login and do not create a user. Password login is not domain-limited. Hide the Auth0 button when domain/client id/secret are empty. Credentials live in `config/services.php` (`AUTH0_*` env). Auth0 tests reset those values in `beforeEach` so they do not inherit `.env`. Provisioning belongs in `Auth0UserProvisioner`, grant/revoke in `AdminAccessService` — not Livewire. Never revoke the last admin.
