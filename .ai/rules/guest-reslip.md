---
paths:
  - 'app/Services/Order/**'
  - 'resources/views/pages/storefront/order-confirmation.blade.php'
---

# Guest reslip on order confirmation

When an order is `NeedReslip`, the guest confirmation page (same tracking URL) shows PromptPay QR for `amount_due_now` plus `x-storefront.slip-dropzone` and a sticky CTA (`resubmit` → `OrderService::replaceSlip()`). Do not add a separate payment route.

`replaceSlip()` only accepts `NeedReslip`. It runs the same `SlipVerificationService::inspect()` rules as checkout `place()`, deletes the old slip file and row (no history), stores the new slip, sets status to `PendingReview`, and writes `order_status_changes` with `user_id` null. Do not call admin `transition()` for guest resubmit.

While `NeedReslip`, hide the read-only slip preview in the details `<dl>`. Other statuses keep the existing slip preview. Do not show admin rejection reasons or LINE notifications in this flow.
