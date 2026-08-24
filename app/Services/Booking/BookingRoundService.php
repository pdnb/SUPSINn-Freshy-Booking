<?php

namespace App\Services\Booking;

use App\Models\BookingRound;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BookingRoundService
{
    /**
     * @return Collection<int, BookingRound>
     */
    public function list(): Collection
    {
        return BookingRound::query()
            ->with('products')
            ->orderByDesc('starts_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): BookingRound
    {
        $payload = $this->validated($data);

        return DB::transaction(function () use ($payload) {
            $round = BookingRound::query()->create([
                'name' => $payload['name'],
                'starts_at' => $payload['starts_at'],
                'ends_at' => $payload['ends_at'],
                'is_enabled' => $payload['is_enabled'] ?? true,
            ]);

            $round->products()->sync($payload['product_ids'] ?? []);

            return $round->fresh('products');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BookingRound $round, array $data): BookingRound
    {
        $payload = $this->validated($data);

        return DB::transaction(function () use ($round, $payload) {
            $round->update([
                'name' => $payload['name'],
                'starts_at' => $payload['starts_at'],
                'ends_at' => $payload['ends_at'],
                'is_enabled' => $payload['is_enabled'] ?? $round->is_enabled,
            ]);

            $round->products()->sync($payload['product_ids'] ?? []);

            return $round->fresh('products');
        });
    }

    public function isOpen(BookingRound $round, ?Carbon $at = null): bool
    {
        return $round->isOpenAt($at);
    }

    /**
     * @return Collection<int, BookingRound>
     */
    public function openRounds(?Carbon $at = null): Collection
    {
        $at ??= now();

        return BookingRound::query()
            ->with('products')
            ->where('is_enabled', true)
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>=', $at)
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function storefrontProducts(?Carbon $at = null): Collection
    {
        $at ??= now();

        return Product::query()
            ->where('is_active', true)
            ->whereHas('bookingRounds', function ($query) use ($at): void {
                $query->where('is_enabled', true)
                    ->where('starts_at', '<=', $at)
                    ->where('ends_at', '>=', $at);
            })
            ->with(['optionGroups.values', 'components.optionGroups.values', 'coverImage'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function searchStorefrontProducts(string $query, ?Carbon $at = null): Collection
    {
        $needle = mb_strtolower(trim($query));

        if ($needle === '') {
            return collect();
        }

        return $this->storefrontProducts($at)
            ->filter(fn (Product $product): bool => str_contains(mb_strtolower($product->name), $needle))
            ->values();
    }

    public function isProductAvailable(Product $product, ?Carbon $at = null): bool
    {
        return $this->storefrontProducts($at)->contains('id', $product->id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validated(array $data): array
    {
        $payload = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
            'is_enabled' => ['sometimes', 'boolean'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ])->validate();

        if (Carbon::parse($payload['ends_at'])->lte(Carbon::parse($payload['starts_at']))) {
            throw ValidationException::withMessages([
                'ends_at' => 'เวลาสิ้นสุดต้องหลังเวลาเริ่ม',
            ]);
        }

        $payload['product_ids'] = $payload['product_ids'] ?? [];

        return $payload;
    }
}
