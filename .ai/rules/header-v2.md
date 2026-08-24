---
paths:
  - 'resources/views/components/storefront/header-v2.blade.php'
  - 'resources/views/pages/storefront/header-v2-preview.blade.php'
  - 'resources/views/pages/storefront/home.blade.php'
  - 'routes/web.php'
---

# Storefront header v2

Home uses `x-storefront.header-v2`. Other storefront pages keep `x-storefront.header`. The preview at `/preview/header-v2` stays unbound chrome.

Live search is home-only: pass `searchable`, `query`, and `results` from `BookingRoundService::searchStorefrontProducts()`. The searchable flag renders a dedicated `x-storefront.input` with `wire:model.live.debounce.300ms="search"`. Do not put `wire:model` on the `x-storefront.header-v2` tag, and do not put `@if` inside that input tag — both leave `<x-storefront.input>` uncompiled. Do not bind search on cart, checkout, or the preview page. Match product names of open-round items only. The home product grid is not filtered by search.

The storefront logo sits in the left circular home button (home icon if the logo is cleared); do not put the logo in the headline.
