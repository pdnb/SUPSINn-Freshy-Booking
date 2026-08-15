<?php

namespace App\Services\Ads;

use App\Models\AdsBanner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdsBannerService
{
    /**
     * @return Collection<int, AdsBanner>
     */
    public function list(): Collection
    {
        return AdsBanner::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, AdsBanner>
     */
    public function activeForStorefront(): Collection
    {
        return AdsBanner::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AdsBanner
    {
        $payload = $this->validated($data, creating: true);

        return AdsBanner::query()->create([
            'image_path' => $this->storeImage($payload['image']),
            'url' => $payload['url'],
            'sort_order' => $payload['sort_order'] ?? $this->nextSortOrder(),
            'is_active' => $payload['is_active'] ?? true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AdsBanner $banner, array $data): AdsBanner
    {
        $payload = $this->validated($data, creating: false);

        $attributes = [
            'url' => $payload['url'],
            'sort_order' => $payload['sort_order'] ?? $banner->sort_order,
            'is_active' => $payload['is_active'] ?? $banner->is_active,
        ];

        if (isset($payload['image']) && $payload['image'] instanceof UploadedFile) {
            $this->deleteImage($banner->image_path);
            $attributes['image_path'] = $this->storeImage($payload['image']);
        }

        $banner->update($attributes);

        return $banner->fresh();
    }

    public function delete(AdsBanner $banner): void
    {
        $this->deleteImage($banner->image_path);
        $banner->delete();
    }

    public function setActive(AdsBanner $banner, bool $active): AdsBanner
    {
        $banner->update(['is_active' => $active]);

        return $banner->fresh();
    }

    public function move(AdsBanner $banner, int $direction): void
    {
        $swap = AdsBanner::query()
            ->when($direction < 0, fn ($query) => $query->where('sort_order', '<', $banner->sort_order)->orderByDesc('sort_order'))
            ->when($direction > 0, fn ($query) => $query->where('sort_order', '>', $banner->sort_order)->orderBy('sort_order'))
            ->first();

        if ($swap === null) {
            return;
        }

        $current = $banner->sort_order;
        $banner->update(['sort_order' => $swap->sort_order]);
        $swap->update(['sort_order' => $current]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validated(array $data, bool $creating): array
    {
        $data['url'] = filled($data['url'] ?? null) ? $data['url'] : null;

        return Validator::make($data, [
            'image' => [$creating ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'image.required' => 'กรุณาอัปโหลดรูปแบนเนอร์',
            'url.url' => 'ลิงก์ปลายทางไม่ถูกต้อง',
        ])->validate();
    }

    private function storeImage(UploadedFile $image): string
    {
        return $image->store('ads-banners', 'public');
    }

    private function deleteImage(string $path): void
    {
        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function nextSortOrder(): int
    {
        return (int) AdsBanner::query()->max('sort_order') + 1;
    }
}
