---
paths:
  - 'resources/views/pages/**'
  - 'app/Livewire/**'
---

# Livewire pages

## Full-page components
Storefront and admin screens are Livewire 4 view-based pages under `resources/views/pages/{storefront,admin}` (`pages::storefront.*`, `pages::admin.*`). Default format is SFC (`make_command.type = sfc`, `emoji = false`). Use MFC only for oversized pages (`admin/product-edit`, `admin/settings`). Route with `Route::livewire(..., 'pages::...')` and test with `Livewire::test('pages::...')`.

## Do not recreate class-based pages
Do not add new page classes under `app/Livewire` or views under `resources/views/livewire`. Shared PHP traits for pages are allowed only if truly reused; prefer keeping page logic in the SFC/MFC.

## Chrome vs pages
Storefront header/tabbar are Blade components (`x-storefront.header`, `x-storefront.tabbar`). Admin UI widgets stay as `x-admin.*` under `resources/views/components/admin`. Do not put full-page Livewire components under `resources/views/components/` (avoids colliding with Blade components).
