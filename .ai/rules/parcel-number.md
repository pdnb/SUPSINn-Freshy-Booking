---
paths:
  - 'app/Services/Order/**'
  - 'app/Models/Order.php'
  - 'resources/views/pages/admin/fulfillment.blade.php'
  - 'resources/views/pages/admin/order-detail.blade.php'
  - 'resources/views/pages/storefront/order-confirmation.blade.php'
---

# Postal parcel numbers

`orders.parcel_number` is the carrier tracking string guests see. Do not reuse `tracking_token` (secret for the guest confirmation URL). Empty input stores `null`. No format regex.

Ship and edit only through `OrderService::markShipped()` (post + confirmed) and `updateParcelNumber()` (post + shipped). Keep `transition()` status-only. Bookstore/hall do not get a parcel field. Shipped is the operational end for post in the UI — no complete button.

The จัดส่ง page is postage-only (no channel tabs). **รอดำเนินการ** is `awaiting_parcel`: confirmed, plus shipped with a null number. Pickup (bookstore/hall) does not appear on this page. Guest confirmation for post is จองแล้ว → ตรวจสลิป → จัดส่งแล้ว (shipped = all done) and must not promise a pickup receipt.

Guest confirmation **จุดรับสินค้า** shows only the order’s fulfillment method (bookstore or hall label + caption). Post hides that section. Do not list every pickup option.
