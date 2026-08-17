---
paths:
  - 'resources/views/pages/storefront/**'
  - 'resources/views/components/storefront/**'
  - 'resources/views/layouts/app.blade.php'
---

# Storefront CartService toasts

CartService `ValidationException` on storefront **actions** (add to cart, cart qty, checkout `save`) is dispatched as `storefront-toast` with the first message. Do not use `@error` for `cart`, `product`, `qty`, `options`, or `components`. Field validation (student_id, address, slip, etc.) stays inline under the input.

Do not toast from `render()` / page load. Checkout `quote()` failures while the page is sitting idle stay a silent catch; the shopper sees the toast when they tap **ไปชำระเงิน**. Host lives in `layouts.app` as `x-storefront.toast`.
