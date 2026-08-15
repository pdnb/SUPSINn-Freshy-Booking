<?php

namespace App\Services\Shipping;

use App\Models\ShippingRate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorContract;

class ShippingRateService
{
    /**
     * @return Collection<int, ShippingRate>
     */
    public function list(): Collection
    {
        return ShippingRate::query()->orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, ShippingRate>
     */
    public function active(): Collection
    {
        return ShippingRate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ShippingRate
    {
        return ShippingRate::query()->create($this->validated($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ShippingRate $rate, array $data): ShippingRate
    {
        $rate->update($this->validated($data, $rate));

        return $rate->fresh();
    }

    public function setActive(ShippingRate $rate, bool $active): ShippingRate
    {
        $rate->update(['is_active' => $active]);

        return $rate->fresh();
    }

    public function amountForQty(ShippingRate $rate, int $qty): string
    {
        $tiers = collect($this->tiersFor($rate))->sortBy('min_qty')->values();

        /** @var array{min_qty: int, max_qty: int|null, amount: string}|null $match */
        $match = $tiers->last(fn (array $tier): bool => $tier['min_qty'] <= $qty);

        if ($match === null) {
            /** @var array{min_qty: int, max_qty: int|null, amount: string} $match */
            $match = $tiers->first();
        }

        return $this->formatMoney($match['amount']);
    }

    /**
     * @return list<array{min_qty: int, max_qty: int|null, amount: string}>
     */
    public function tiersFor(ShippingRate $rate): array
    {
        $tiers = $rate->tiers;

        if (! is_array($tiers) || $tiers === []) {
            return [$this->openEndedTier($rate->amount)];
        }

        return $this->normalizeTiers($tiers);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validated(array $data, ?ShippingRate $rate = null): array
    {
        $data = $this->blankMaxQtyToNull($data);

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255', Rule::unique('shipping_rates', 'name')->ignore($rate)],
            'amount' => ['required_without:tiers', 'numeric', 'min:0'],
            'tiers' => ['required_without:amount', 'array', 'min:1'],
            'tiers.*.min_qty' => ['required', 'integer', 'min:1'],
            'tiers.*.max_qty' => ['nullable', 'integer', 'min:1'],
            'tiers.*.amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ])->after(function (ValidatorContract $validator) use ($data): void {
            $this->assertUniqueMins($validator, $data['tiers'] ?? null);
            $this->assertMaxIsAtLeastMin($validator, $data['tiers'] ?? null);
        })->validate();

        $tiers = isset($validated['tiers'])
            ? $this->normalizeTiers($validated['tiers'])
            : [$this->openEndedTier($validated['amount'])];

        $validated['tiers'] = $tiers;
        $validated['amount'] = $tiers[0]['amount'];

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function blankMaxQtyToNull(array $data): array
    {
        if (! isset($data['tiers']) || ! is_array($data['tiers'])) {
            return $data;
        }

        $data['tiers'] = array_map(function (mixed $tier): mixed {
            if (is_array($tier) && array_key_exists('max_qty', $tier) && $tier['max_qty'] === '') {
                $tier['max_qty'] = null;
            }

            return $tier;
        }, $data['tiers']);

        return $data;
    }

    /**
     * @param  list<array<string, mixed>>|null  $tiers
     */
    private function assertUniqueMins(ValidatorContract $validator, mixed $tiers): void
    {
        if (! is_array($tiers)) {
            return;
        }

        $seen = [];

        foreach ($tiers as $index => $tier) {
            if (! isset($tier['min_qty']) || ! is_numeric($tier['min_qty'])) {
                continue;
            }

            $min = (int) $tier['min_qty'];

            if (in_array($min, $seen, true)) {
                $validator->errors()->add("tiers.{$index}.min_qty", 'ช่วงจำนวนเริ่มต้นซ้ำกัน');
            }

            $seen[] = $min;
        }
    }

    /**
     * @param  list<array<string, mixed>>|null  $tiers
     */
    private function assertMaxIsAtLeastMin(ValidatorContract $validator, mixed $tiers): void
    {
        if (! is_array($tiers)) {
            return;
        }

        foreach ($tiers as $index => $tier) {
            $max = $this->nullableMaxQty($tier['max_qty'] ?? null);

            if ($max === null || ! isset($tier['min_qty']) || ! is_numeric($tier['min_qty'])) {
                continue;
            }

            if ($max < (int) $tier['min_qty']) {
                $validator->errors()->add("tiers.{$index}.max_qty", 'จำนวนสูงสุดต้องไม่น้อยกว่าจำนวนเริ่มต้น');
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $tiers
     * @return list<array{min_qty: int, max_qty: int|null, amount: string}>
     */
    private function normalizeTiers(array $tiers): array
    {
        return collect($tiers)
            ->map(fn (array $tier): array => [
                'min_qty' => (int) $tier['min_qty'],
                'max_qty' => $this->nullableMaxQty($tier['max_qty'] ?? null),
                'amount' => $this->formatMoney($tier['amount']),
            ])
            ->sortBy('min_qty')
            ->values()
            ->all();
    }

    /**
     * @return array{min_qty: int, max_qty: int|null, amount: string}
     */
    private function openEndedTier(mixed $amount): array
    {
        return [
            'min_qty' => 1,
            'max_qty' => null,
            'amount' => $this->formatMoney($amount),
        ];
    }

    private function nullableMaxQty(mixed $max): ?int
    {
        if ($max === null || $max === '') {
            return null;
        }

        return (int) $max;
    }

    private function formatMoney(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
