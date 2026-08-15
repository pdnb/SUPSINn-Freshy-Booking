<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductImageService
{
    /**
     * @param  list<UploadedFile>  $files
     */
    public function addImages(Product $product, array $files): void
    {
        $payload = Validator::make(['images' => $files], [
            'images' => ['array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'images.*.image' => 'ไฟล์ที่อัปโหลดต้องเป็นรูปภาพ',
            'images.*.mimes' => 'รองรับเฉพาะไฟล์ jpg, jpeg, png, webp',
            'images.*.max' => 'รูปต้องมีขนาดไม่เกิน 4MB',
        ])->validate();

        $sortOrder = (int) $product->images()->max('sort_order');

        foreach ($payload['images'] ?? [] as $file) {
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
        $min = (int) ProductImage::query()
            ->where('product_id', $image->product_id)
            ->min('sort_order');

        if ($image->sort_order === $min) {
            return;
        }

        $image->update(['sort_order' => $min - 1]);
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
}
