---
paths:
  - 'app/Services/Checkout/**'
  - 'resources/views/pages/storefront/checkout.blade.php'
---

# Checkout shipping

Guests do not pick a shipping rate. When fulfillment is post, `CheckoutService::quote()` applies the first active rate by `sort_order`, then `ShippingRateService::amountForQty()` using `CartService::count()` (sum of every line qty). Matching is the last tier whose `min_qty <= cartQty` — overflow and gaps use that previous/last tier. `max_qty` is stored for staff editing later; the storefront ignores it. Show the calculated amount in the summary only — no rate `<select>` on the storefront.
