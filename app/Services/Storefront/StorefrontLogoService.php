<?php

namespace App\Services\Storefront;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StorefrontLogoService
{
    public const KEY = 'storefront_logo_path';

    public function path(): ?string
    {
        $setting = Setting::query()->where('key', self::KEY)->first();

        if ($setting === null) {
            return config('booking.default_storefront_logo');
        }

        return $setting->value;
    }

    public function url(): ?string
    {
        $path = $this->path();

        if ($path === null || $path === '') {
            return null;
        }

        if ($this->isBundledPath($path)) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }

    public function update(?UploadedFile $image): void
    {
        $payload = Validator::make(
            ['image' => $image],
            ['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096']],
            ['image.required' => 'กรุณาอัปโหลดโลโก้'],
        )->validate();

        $previous = $this->storedPath();
        /** @var UploadedFile $file */
        $file = $payload['image'];
        $path = $file->store('logos', 'public');

        $this->write($path);
        $this->deleteStoredImage($previous);
    }

    public function clear(): void
    {
        $previous = $this->storedPath();
        $this->write(null);
        $this->deleteStoredImage($previous);
    }

    private function write(?string $path): void
    {
        Setting::query()->updateOrCreate(
            ['key' => self::KEY],
            ['value' => $path],
        );
    }

    private function storedPath(): ?string
    {
        return Setting::query()->where('key', self::KEY)->value('value');
    }

    private function isBundledPath(string $path): bool
    {
        return str_starts_with($path, 'images/');
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path === null || $path === '' || $this->isBundledPath($path)) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
