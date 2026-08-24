---
paths:
  - 'resources/views/components/storefront/header.blade.php'
  - 'resources/views/components/storefront/header-v2.blade.php'
  - 'resources/views/pages/admin/login.blade.php'
  - 'resources/views/pages/admin/settings/**'
  - 'resources/views/components/admin/sidebar.blade.php'
  - 'resources/views/layouts/admin.blade.php'
  - 'resources/views/pages/storefront/home.blade.php'
  - 'config/app.php'
---

# Storefront brand name

User-facing shop chrome reads `config('app.name')` (APP_NAME, default SRU Shop). Do not hardcode the shop name in headers, login, admin sidebar/topbar, or settings logo copy. Product names, booking-round names, and PromptPay payee stay as catalog/payment data — they are not the shop brand.
