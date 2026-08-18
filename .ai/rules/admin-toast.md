---
paths:
  - 'resources/views/pages/admin/**'
  - 'resources/views/components/admin/**'
  - 'resources/views/layouts/admin.blade.php'
---

# Admin success toasts

Same-page admin mutations dispatch `admin-toast` with the success message. Redirects flash `session('status')` instead — the host in `layouts.admin` (`x-admin.toast`) shows that on full page load. Auto-dismiss is ~4 seconds. Do not toast validation; field errors stay inline under the input.
