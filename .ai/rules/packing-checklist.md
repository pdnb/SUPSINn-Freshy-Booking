---
paths:
  - 'app/Services/Packing/**'
  - 'app/Http/Controllers/Admin/PackingExportController.php'
  - 'resources/views/pages/admin/packing-checklist.blade.php'
  - 'resources/views/admin/packing-checklist/**'
---

# Packing checklist (แพ็คของ)

Thai copy is **แพ็คของ** on nav, page title, and PDF title/filename. Do not use ใบแพ็ค or แพ็คสินค้า.

Query and PDF go through `PackingChecklistService` and `PackingChecklistExporter`. Do not overload `OrderService::queue()` or call `transition()`. Printing does not change order status.

Packable set: `confirmed`, `ready_for_pickup`, and post `shipped` with null `parcel_number`. Exclude `pending_review`, `need_reslip`, `cancelled`, `completed`, and shipped with a parcel number.

Sort: Post → Bookstore → Hall, then `faculty`, then `number`. Optional filters: booking round, channel, faculty (empty = all). PDF only (no CSV/Excel). One A4 page per order; checkbox per line-item row; no prices, totals, slip, or parcel number on the sheet. DomPDF only has Sarabun Regular — table headers and `h1` must use `font-weight: normal` or Thai glyphs become tofu.
