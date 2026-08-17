---
paths:
  - 'resources/views/**'
---

# Views

## Use x-icon with Heroicons outline
UI icons go through <x-icon name="..." size="sm|md|lg|xl" /> which renders heroicon-o-* only. Do not inline SVG icons in Blade. blade-icons default component is renamed to x-svg-icon in config/blade-icons.php so x-icon stays our wrapper.

## Home banner carousel uses swipe, not prev/next buttons
`data-od-id="home-banner"` advances via pointer swipe (and optional arrow keys when focused). Do not render absolute prev/next buttons with aria-labels `แบนเนอร์ก่อนหน้า` / `แบนเนอร์ถัดไป`. Keep auto-advance `setInterval` when more than one banner is active; suppress link click after a swipe. Slides are absolutely stacked in an `aspect-[2/1]` frame with Alpine `x-transition` opacity fade on change (no layout shift).

## Product image gallery uses swipe and prev/next buttons
`data-od-id="product-gallery"` advances via pointer swipe, prev/next buttons (`รูปก่อนหน้า` / `รูปถัดไป`), and optional arrow keys when focused. Do not auto-advance. Slides are absolutely stacked in an `aspect-[16/9]` frame with Alpine `x-transition` opacity fade on change (no layout shift). Keep the 1/N counter when there is more than one image. Use `wire:ignore` so selecting options does not reset the swipe index. Stop pointer events on the buttons so a tap does not also count as a swipe.
