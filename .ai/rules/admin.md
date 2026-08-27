---
paths:
  - 'resources/views/pages/admin/**'
  - 'resources/views/layouts/admin.blade.php'
  - 'resources/views/components/admin/**'
  - 'resources/css/admin.css'
---

# Admin UI

## Stack
Livewire 4 view-based pages under `resources/views/pages/admin` (`pages::admin.*`; SFC by default, MFC for `product-edit` and `settings`), layout `layouts.admin`, CSS from `resources/css/admin.css` (ported `docs/references/ecommerce-admin/admin-common.css`). Do not add Filament or another admin kit. Do not port `admin-mockup` `tokens.css` / `shell.css`. Do not add new `app/Livewire/Admin` page classes.

## Page layout vs CSS
Screen IA, Thai copy, nav items, and flows come from `docs/references/admin-mockup`. Chrome markup and utility classes come from ecommerce-admin: `admin-app` / `sidebar` / `admin-frame` / `topbar` / `content` / `ds-table` / `nav-link` / `panel` / `kpi` / `pill` / `dialog`.

Nav: ภาพรวม, ออเดอร์, จัดส่ง, แพ็คของ, รับของ, สรุปยอด, สต็อก, สินค้า, รอบจอง, ผู้ใช้, ตั้งค่า. No Customers / Discounts / Analytics.

## Domain
Mutations go through application services (`OrderService`, `CatalogService`, `BookingRoundService`, `ShippingRateService`, `AdsBannerService`, `StorefrontLogoService`, `ProductionSummaryService`, `InventoryService`, `PackingChecklistService`). Inventory is on-hand vs confirmed orders and must not block checkout or change catalog SKU/stock rules.

## Type
Body and headings use Anuphan via `@fonts` (same as the storefront). Do not use IBM Plex Sans Thai or the ecommerce-admin serif display stack.

## Palette
Admin chrome is gray + white (neutral oklch, chroma 0). Primary actions and active nav use dark gray `--accent`, not the ecommerce-admin green or the storefront blue. Status pills use `--warn`, `--danger`, `--success`, and `--info` chroma; do not put those hues on nav. Semantic action buttons may use `--danger` (`.btn-danger`, destructive), `--success` (`.btn-success`, confirm slip), and `--warn` (`.btn-warning`, request reslip; dark `--fg` text on the yellow fill).

## Auth
Staff login at `/admin/login`. Keep `AdminUserSeeder`, the email/password form, and auth redirects. When Auth0 credentials are configured, add an Auth0 button under the form — do not replace the password form. Login and the pending-access page use the storefront layout (`layouts.app`) with a brand/form split (brand panel + form); the signed-in console uses `layouts.admin`. Keep brand-first copy (`config('app.name')`, currently SRU Shop), skip link to the form, lock icon beside the title, loading button state, and storefront tokens — do not restyle login with admin gray chrome or Inter/cyan “dashboard” palettes. Console routes require `is_admin`; pending Auth0 users land on `/admin/pending` until granted from the ผู้ใช้ page. Logout is app-session only (no Auth0 SLO).

## Icons
Admin CSS does not load Tailwind, so x-icon `size-*` classes have no effect. Size SVGs with `.nav-link svg`, `.icon-btn svg`, and `.btn svg` at 16px.

## Filters
Admin list/filter toolbars (orders, fulfillment, packing-checklist, pickup, production, products, inventory) hide visible field labels (placeholders / select options / `aria-label` only). Date filters use unlabeled `type="date"` inputs (`date_from` / `date_to`) against `orders.created_at` in app timezone. Other labeled filter rows use `.filters { align-items: flex-end }` so buttons without labels line up with the inputs. Every filter toolbar includes a ghost `ล้างตัวกรอง` button (`wire:click="clearFilters"`) that resets filters to that page’s defaults (order queue → pending review; fulfillment → active status + empty search/selection, keep channel tab; post active = confirmed plus shipped with null `parcel_number`, bookstore/hall active = confirmed; products → all/all; production/inventory/pickup/packing-checklist → empty). Product list filters: search, `ชนิด` (`type`: all/simple/bundle), and `สถานะ` (`status`: all/active/draft labeled เปิดขาย/ปิดขาย).

## Tables
`.ds-table th` uses body font (not mono) at ~12.5px. Keep uppercase / muted. Mono stays on `.meta`, `.num`, `.num-col`, and pills.

## Order guest column
Under the guest name in order queue and fulfillment tables, show `student_id` with `.meta` (12px mono muted), not `.muted` alone.

## Slip preview
`.slip-frame` is tall for portrait PromptPay screenshots (`min-height: 640px`, `height: min(72vh, 760px)`).

## Order item choices
On order detail, render choices as a `.choice-list` with one `<li>` per choice (`label · value`). Do not join with commas.

## Order detail summary
The สรุป panel shows the full guest block (ผู้จอง, รหัสนักศึกษา, คณะ, สาขาวิชา, โทร, วิธีรับ, ที่อยู่ with `white-space: pre-line`), line items with qty + line price, and totals (ยอดสินค้า / ค่าส่ง · rate name / ยอดสุทธิ). Do not collapse guest fields into a single paragraph.

## Status pills
`.pill` uses `align-self: flex-start` and `width: fit-content` so it does not stretch inside `.stack`. Map via `OrderStatus::pillClass()`: PendingReview `pill-pending` (warn), NeedReslip `pill-danger`, Confirmed and ReadyForPickup `pill-paid` (success), Shipped and Completed `pill-info`, Cancelled `pill-neutral`.

## CSS selectors
Do not target Livewire `wire:*` attributes in CSS. Colons in attribute selectors fail LightningCSS minify (`npm run build`). Use a class instead (e.g. `.media-card.is-sortable` for drag-reorder tiles).

## Product editor layout
Product create/edit uses ecommerce-admin `grid-2-1`: left stack of Basics + Pricing/options panels, right Images (`รูปภาพ`) panel with status pill. Page-head CTAs are `บันทึกฉบับร่าง` / `เผยแพร่` (not a footer save bar). Keep Thai domain fields (type radio cards, option groups / bundle components); do not add SKU, compare-at, or catalog inventory fields. Option group keys are not shown in the editor — auto-generate on save (keep existing key when loaded; otherwise slug the label or `option_N`). Option values use `x-admin.tag-input` (tag chips), not a comma-separated text field. Product images and settings banner uploads use `x-admin.dropzone` (drag-and-drop + click), not a plain file input. Banner dropzone is single-file (`:multiple="false"`). Banner `url` is optional (`nullable`); storefront renders a plain image when empty. Banner delete uses `x-admin.confirm-dialog` (`openDeleteBanner` → `confirmDeleteBanner`) before removing the row and image file. Settings has a third tab `โลโก้` (beside ค่าส่ง / แบนเนอร์) managed by `StorefrontLogoService`: single-file dropzone (`logo_image`), preview of current logo, and clear via `x-admin.confirm-dialog` (`openClearLogo` → `confirmClearLogo`). Clearing sets the setting to null so the storefront header shows brand text; the bundled default lives at `config('booking.default_storefront_logo')` (`images/subsinn-logo.png`) and must not be deleted from disk. Settings also has a `มัดจำ` tab managed by `DepositSettingService` (`deposit_amount` key): fixed baht for optional pickup deposit; `0` hides the storefront choice. Settings also has a `ปีการศึกษา` tab managed by `AcademicYearSettingService` (`academic_year` key): four-digit Buddhist year used as the `FB-{YY}-{NNNN}` order-number prefix. Banner reorder controls use `icon-btn` + chevron-up/down with `aria-label`, not text ขึ้น/ลง. Boolean enable flags (shipping rate `เปิดใช้`, booking round `เปิดใช้รอบนี้`) use `x-admin.switch`, not a plain checkbox. Shipping rate tier editors use `.field-row.tier-row` so เริ่ม / ถึง / บาท / ลบ stay on one row.

## Confirm dialogs
Destructive yes/no confirms (product delete, banner delete, logo clear, order cancel) use `x-admin.confirm-dialog`. Keep Livewire open/close/confirm methods on the page. Inventory adjust stays an inline form dialog, not this component.
