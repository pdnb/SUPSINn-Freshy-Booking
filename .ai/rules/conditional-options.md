---
paths:
  - 'app/Services/Catalog/**'
  - 'app/Services/Cart/**'
  - 'app/Models/ProductOptionGroup.php'
  - 'resources/views/pages/admin/product-edit/**'
  - 'resources/views/components/admin/option-group-dependency.blade.php'
  - 'resources/views/pages/storefront/product-show.blade.php'
---

# Conditional option values

## One level only
A group may depend on at most one earlier group in the same scope (product-level for simple, component-level for bundle). The parent must appear earlier in the list and must not itself have a `depends_on_key`. Do not build A→B→C chains.

## Fail closed
`ProductOptionGroup::valuesAllowedFor(null)` returns no values while a parent is required but unset. An unmapped parent value also allows nothing. Storefront chips and `CartService` both use this; cart rejects invalid pairs with `ตัวเลือกที่เลือกไม่เข้ากัน กรุณาเลือกใหม่`.

## Persist and copy the map
Store `depends_on_key` + `depends_on_values` (JSON map of parent value → allowed child values) on `product_option_groups`. `CatalogService::groupsPayload` / `duplicate` / `cloneIntoRound` must copy both columns. Do not add SKU, per-option price, or sellable stock.

## Admin key remap
Option group keys stay hidden; `normalizedGroups()` may change keys on save. Remap each child's `depends_on_key` through the old→new key map before calling `CatalogService`. In the editor Blade, do not reuse a loop variable after nested `<x-admin.*>` components (Blade may overwrite it with the component instance) — read from `$optionGroups[$index]` / `$components[...]` instead.
