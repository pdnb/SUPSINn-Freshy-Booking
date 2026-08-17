---
paths:
  - 'resources/views/pages/storefront/**'
---

# Storefront Livewire loading

## Disable buttons while a request is in flight
Every storefront `wire:click` / `wire:submit` control must use `wire:loading.attr="disabled"` plus `wire:target` matching that action (include parameters when the click passes them). Do not leave a Livewire action tappable during a slow request.

## Primary CTAs show a spinner
Checkout `save`, payment `confirm`, and product `addToCart` swap label for `<x-icon name="arrow-path" />`. Secondary controls (cart qty, option chips) only disable — no spinner.

## One save binding on checkout
The checkout footer button is `type="button"` with `wire:click="save"`. Do not also put `wire:submit="save"` on the form.
