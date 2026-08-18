---
paths:
  - 'app/Services/Order/**'
  - 'resources/views/pages/storefront/order-track.blade.php'
---

# Guest order lookup

Non-LIFF `/orders` finds bookings with exact `student_id` AND `phone` via `OrderService::findForGuestLookup()`. Do not use `LIKE`. A miss uses one generic message (`ไม่พบคำสั่งซื้อที่ตรงกับข้อมูลนี้`) and does not reveal which field was wrong. Throttle `search()` at 5 attempts per minute per IP (increment on miss, clear on hit), same pattern as admin login. LIFF sessions never see this form.

Guest tracking session still stores number/token for slip access. Only `auto_open: true` (set on `place()`) triggers a one-time redirect from `/orders` to confirmation; lookup or tracking-link views must not set `auto_open`, so returning to `/orders` shows the search form again.
