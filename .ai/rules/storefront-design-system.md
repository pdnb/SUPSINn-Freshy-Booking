---
paths:
  - 'resources/views/components/storefront/**'
  - 'resources/views/pages/storefront/**'
  - 'resources/css/app.css'
---

# Storefront design system

## Tokens live in app.css
Storefront color, radius, and font tokens are the `@theme` block in `resources/css/app.css`, sourced from `docs/references/mockup/brand-spec.md`. Do not introduce Tailwind palette colors (`bg-gray-*`, `bg-blue-*`, `text-indigo-*`) or raw hex on storefront pages. Keep PromptPay QR on `bg-white`.

## Use x-storefront primitives
Buttons, cards, prices, badges, chips, empty states, sticky bars, fields, inputs, selects, radio cards, step bars, and slip dropzones go through `x-storefront.*`. Do not paste raw `bg-accent px-4 font-medium` CTA classes, `rounded-brand border border-border bg-surface p-4` card shells, or `mt-1 min-h-11 w-full rounded-brand border border-border px-3` inputs into pages.

## Highlight is not a CTA
`--highlight` / `bg-highlight` is decoration (promo badge, current-step underline). Primary actions stay `variant="primary"` on `x-storefront.button` (accent purple).
