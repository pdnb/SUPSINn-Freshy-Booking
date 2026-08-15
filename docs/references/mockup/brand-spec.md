# Brand spec — มรส. ชุดเฟรชชี่

Blue + gray + white commerce UI with Anuphan for Thai-first mobile booking.

## Tokens

```css
--bg: oklch(98% 0.004 250);
--surface: oklch(100% 0 0);
--fg: oklch(24% 0.02 255);
--muted: oklch(48% 0.014 255);
--border: oklch(88% 0.01 250);
--accent: oklch(48% 0.13 255);
--accent-press: oklch(40% 0.13 255);
--accent-fg: oklch(99% 0.01 255);
--brand: oklch(48% 0.13 255);
--brand-press: oklch(40% 0.13 255);
--brand-fg: oklch(99% 0.01 255);
```

## Fonts

- Display: `"Anuphan", "Sarabun", system-ui, sans-serif`
- Body: `"Anuphan", "Sarabun", system-ui, sans-serif`
- Weights: 400 / 500–550 / 600 only

## Rules

1. Blue (`--accent`) is for primary CTAs, focus rings, and step “current” — at most two accent hits per screen.
2. Blue (`--brand`) is for the shop header brand plane (same blue family; not a second competing hue).
3. Surfaces stay white; neutrals are cool gray; chips use gray fills, not blue floods.
4. Green only for success / slip attached — never as brand chrome.
5. Hover/active must keep or improve contrast — never lighten text to muted.
6. Touch targets ≥ 44px; focus rings always visible via `:focus-visible`.
