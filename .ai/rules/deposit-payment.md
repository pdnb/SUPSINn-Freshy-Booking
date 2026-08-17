---
paths:
  - 'app/Services/Checkout/**'
  - 'app/Services/Order/**'
  - 'resources/views/pages/storefront/checkout.blade.php'
  - 'resources/views/pages/storefront/payment.blade.php'
---

# Deposit payment

Optional fixed-baht deposit for pickup only (`bookstore` / `hall`). Admin sets `deposit_amount` via `DepositSettingService` (settings tab มัดจำ); `0` hides the choice. Guests pick full vs deposit when `total > deposit`. Post always pays full. PromptPay/slip uses `amount_due_now`; `amount_remaining` is collected cash/transfer at pickup via `OrderService::collectBalance()`. Block `Completed` / `markPickedUp` while `hasOutstandingBalance()`.
