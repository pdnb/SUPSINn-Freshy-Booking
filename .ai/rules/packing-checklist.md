---
paths:
  - 'app/Services/Packing/**'
  - 'app/Http/Controllers/Admin/PackingExportController.php'
  - 'resources/views/pages/admin/packing-checklist.blade.php'
  - 'resources/views/admin/packing-checklist/**'
  - 'resources/views/pages/admin/fulfillment.blade.php'
---

# Packing checklist (แพ็คของ)

Thai copy is **แพ็คของ** on nav, page title, and PDF title/filename. Do not use ใบแพ็ค or แพ็คสินค้า.

Query, mark packed, and PDF go through `PackingChecklistService`, `PackingBarcode`, and `PackingChecklistExporter`. Do not overload `OrderService::queue()`. Printing does not change order status or `packed_at`.

Pickup (`bookstore` / `hall`) `markPacked` sets `packed_at` and, when status is `confirmed`, calls `OrderService::transition()` to `ReadyForPickup` with the acting staff user. `unmarkPacked` on pickup `ReadyForPickup` clears `packed_at` and reverts to `Confirmed`. Do not unmark `Completed`. Post packing only sets/clears `packed_at` — do not ship or change guest-visible status from the packing page.

Fulfillment has no พร้อมรับ button. Pickup ready is packing only. Post still uses จัดส่งแล้ว / เลขพัสดุ on fulfillment. Pickup desk still uses รับของแล้ว.

Packable print set: `confirmed`, `ready_for_pickup`, and post `shipped` with null `parcel_number`, **and** `packed_at` null. Exclude `pending_review`, `need_reslip`, `cancelled`, `completed`, shipped with a parcel number, and already packed.

`markPacked` / `unmarkPacked` lookup by `orders.number` (trim), ignore page filters, take the acting `User`. Already packed does not toggle. Packed today is `packed_at >= start of today` in app timezone, unfiltered.

The pack-number toolbar uses `.filters.filters-align-start` so แพ็คแล้ว / ยกเลิกแพ็คแล้ว stay aligned with the input when `@error` appears under the field. Default `.filters { align-items: flex-end }` drops the buttons next to the error.

Sort: Post → Bookstore → Hall, then `faculty`, then `number`. Optional filters: booking round, channel, faculty (empty = all). PDF only (no CSV/Excel). One A4 page per order; checkbox per line-item row; Code128 and QR of `orders.number` in the header; no prices, totals, slip, or parcel number on the sheet. DomPDF only has Sarabun Regular — table headers and `h1` must use `font-weight: normal` or Thai glyphs become tofu.
