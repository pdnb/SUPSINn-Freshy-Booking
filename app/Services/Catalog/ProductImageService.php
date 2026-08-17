<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductImageService
{
    /**
     * @param  list<UploadedFile>  $files
     * @return list<UploadedFile>
     */
    public function validated(array $files): array
    {
        /** @var array{images?: list<UploadedFile>} $payload */
        $payload = Validator::make(['images' => $files], [
            'images' => ['array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'images.*.image' => 'ไฟล์ที่อัปโหลดต้องเป็นรูปภาพ',
            'images.*.mimes' => 'รองรับเฉพาะไฟล์ jpg, jpeg, png, webp',
            'images.*.max' => 'รูปต้องมีขนาดไม่เกิน 5 MB',
        ])->validate();

        return array_values($payload['images'] ?? []);
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function addImages(Product $product, array $files): void
    {
        $files = $this->validated($files);
        $sortOrder = (int) $product->images()->max('sort_order');

        foreach ($files as $file) {
            $product->images()->create([
                'path' => $file->store('product-images', 'public'),
                'sort_order' => ++$sortOrder,
            ]);
        }
    }

    public function deleteImage(ProductImage $image): void
    {
        $this->deleteFile($image->path);
        $image->delete();
    }

    public function move(ProductImage $image, int $direction): void
    {
        $swap = ProductImage::query()
            ->where('product_id', $image->product_id)
            ->when($direction < 0, fn ($query) => $query->where('sort_order', '<', $image->sort_order)->orderByDesc('sort_order'))
            ->when($direction > 0, fn ($query) => $query->where('sort_order', '>', $image->sort_order)->orderBy('sort_order'))
            ->first();

        if ($swap === null) {
            return;
        }

        $current = $image->sort_order;
        $image->update(['sort_order' => $swap->sort_order]);
        $swap->update(['sort_order' => $current]);
    }

    public function setAsCover(ProductImage $image): void
    {
        $images = $this->orderedImages($image->product_id);

        if ($images->isEmpty() || $images->first()?->is($image)) {
            return;
        }

        $orderedIds = $images
            ->reject(fn (ProductImage $candidate): bool => $candidate->is($image))
            ->prepend($image)
            ->pluck('id')
            ->all();

        $this->reorder($image->product, $orderedIds);
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(Product $product, array $orderedIds): void
    {
        $images = $this->orderedImages($product->id);

        if ($images->isEmpty()) {
            return;
        }

        $orderedIds = collect($orderedIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->values();

        $remainingIds = $images
            ->pluck('id')
            ->reject(fn (int $id): bool => $orderedIds->contains($id))
            ->values();

        $finalIds = $orderedIds
            ->filter(fn (int $id): bool => $images->contains('id', $id))
            ->merge($remainingIds)
            ->values();

        foreach ($finalIds as $index => $id) {
            ProductImage::query()
                ->whereKey($id)
                ->update(['sort_order' => $index]);
        }
    }

    public function moveToPosition(ProductImage $image, int $position): void
    {
        $images = $this->orderedImages($image->product_id);
        $currentIndex = $images->search(fn (ProductImage $candidate): bool => $candidate->is($image));

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = max(0, min($position, $images->count() - 1));

        if ($currentIndex === $targetIndex) {
            return;
        }

        $orderedIds = $images->pluck('id')->all();
        $movedId = $orderedIds[$currentIndex];

        unset($orderedIds[$currentIndex]);
        $orderedIds = array_values($orderedIds);
        array_splice($orderedIds, $targetIndex, 0, [$movedId]);

        $this->reorder($image->product, $orderedIds);
    }

    public function copyImagesTo(Product $source, Product $target): void
    {
        $source->loadMissing('images');

        foreach ($source->images as $image) {
            $target->images()->create([
                'path' => $this->copyFile($image->path),
                'sort_order' => $image->sort_order,
            ]);
        }
    }

    private function copyFile(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $newPath = 'product-images/'.Str::uuid().($extension !== '' ? '.'.$extension : '');

        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->copy($path, $newPath);
        }

        return $newPath;
    }

    private function deleteFile(string $path): void
    {
        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return Collection<int, ProductImage>
     */
    private function orderedImages(int $productId)
    {
        return ProductImage::query()
            ->where('product_id', $productId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
