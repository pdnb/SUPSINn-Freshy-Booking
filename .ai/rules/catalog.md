---
paths:
  - 'app/Services/Catalog/**'
---

# Catalog admin

## Duplicate and clone copy image files
`CatalogService::duplicate` and `cloneIntoRound` copy product image files to new public-disk paths. Do not reuse the source `path`. `ProductImageService::deleteImage` deletes the file from disk; a shared path would delete the original product's image.

## Clone attaches new products only
`cloneIntoRound` creates new products (inactive, name suffixed ` (สำเนา)`) and `syncWithoutDetaching` them onto the destination round. Source products and their round attachments stay unchanged. Product-to-round attachment for existing products still happens on the booking-round form.

## Do not change variant or stock rules
Keep option groups / components / `choices[]` as they are. No SKU, per-option price, or sellable stock on the catalog. On-hand inventory for ops lives in `InventoryService` and must not be read or written from checkout.
