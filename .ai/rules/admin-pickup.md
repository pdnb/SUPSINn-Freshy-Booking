---
paths:
  - 'resources/views/pages/admin/pickup.blade.php'
  - 'resources/css/admin.css'
---

# Admin pickup desk

Search-first: an empty query shows no queue. Results come from `OrderService::queue()` with `status => null` (all statuses, any channel). Auto-select when exactly one hit; two or more hits stay unselected until staff click a row. `clearFilters` resets search and selection.

The screen is a pickup station (same visual language as แพ็คของ / จัดส่ง): unlabeled filter toolbar with a normal `.input` (not `.packing-scan-bar`) plus ghost `เคลียร์`, `.fulfill-split` results table + sticky detail, selected-row highlight (`.is-selected`), count in `panel-head`, `.fulfill-empty` empty states. Detail shows guest, จุดรับ, items with one choice per line, totals, remaining deposit, and timeline when present. Collect / `markPickedUp` stay in `.pickup-detail-action` pinned to the bottom of the detail panel so a long timeline does not hide the CTA. Collect remaining is required before `markPickedUp`. Do not add a KPI row or channel tabs. Filter toolbar still hides field labels.
