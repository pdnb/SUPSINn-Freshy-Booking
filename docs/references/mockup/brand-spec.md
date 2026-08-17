# Brand spec — มรส. ชุดเฟรชชี่

Purple + orange commerce UI from the SUBSINN SRU mark, with Anuphan for Thai-first mobile booking.

## Tokens

```css
--color-bg: oklch(98.5% 0.006 310);
--color-surface: oklch(100% 0 0);
--color-surface-2: oklch(96.5% 0.012 310);
--color-fg: oklch(24% 0.04 300);
--color-muted: oklch(48% 0.03 300);
--color-border: oklch(89% 0.014 310);
--color-brand: oklch(32% 0.19 300);
--color-brand-press: oklch(26% 0.17 300);
--color-brand-fg: oklch(99% 0.005 310);
--color-accent: oklch(42% 0.21 300);
--color-accent-press: oklch(35% 0.19 300);
--color-accent-fg: oklch(99% 0.005 310);
--color-highlight: oklch(70% 0.17 52);
--color-highlight-press: oklch(62% 0.18 45);
--color-highlight-fg: oklch(47% 0.16 45);
--color-highlight-soft: oklch(95% 0.035 65);
--color-success: oklch(52% 0.14 145);
--color-warn: oklch(62% 0.14 75);
--color-danger: oklch(45% 0.18 22);
--radius-brand: 14px;
--radius-brand-sm: 10px;
```

Mapped in `resources/css/app.css` `@theme` so utilities like `bg-brand` / `text-accent` / `bg-highlight-soft` exist.

## Fonts

- Display: `"Anuphan", "Sarabun", system-ui, sans-serif`
- Body: `"Anuphan", "Sarabun", system-ui, sans-serif`
- Weights: 400 / 500 / 600 only

## Type scale

- Page title (h1): `text-xl font-semibold`
- Section title (h2): `text-base font-semibold`
- Body: `text-sm`
- Caption: `text-xs`

## Color rules

1. Purple (`--accent`) is for primary CTAs, focus rings, and step “current” — at most two accent hits per screen besides numbered how-to steps.
2. Deep purple (`--brand`) is for the shop header chrome (icons, prices) — not a second competing hue.
3. Surfaces stay white; neutrals are cool purple-gray; chips use gray fills, not purple floods.
4. Orange/red (`--highlight`) is decoration only: promo badges, the current-step underline, brand lettering. Never a button or CTA — it collides with `--danger`. Text on white uses `--highlight-fg`.
5. Green only for success / slip attached — never as brand chrome.
6. `--danger` is paired with text or an icon — never color alone.
7. Hover/active must keep or improve contrast — never lighten text to muted.
8. Touch targets ≥ 44px (`min-h-11`); focus rings always visible via `:focus-visible`.
9. PromptPay QR keeps `bg-white` so the code stays scannable — do not replace with `bg-surface`.

## Contrast

These pairs must stay at least 4.5:1: accent on surface, brand on surface, muted on bg, highlight-fg on surface, accent-fg on accent, brand-fg on brand.
