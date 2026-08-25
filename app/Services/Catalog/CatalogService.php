<?php

namespace App\Services\Catalog;

use App\Enums\ProductType;
use App\Models\BookingRound;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductOptionGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CatalogService
{
    public function __construct(private ProductImageService $images) {}

    /**
     * @return Collection<int, Product>
     */
    public function list(): Collection
    {
        return Product::query()
            ->with(['components.optionGroups.values', 'optionGroups.values'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array{search?: string|null, type?: string|null, is_active?: bool|null, booking_round_id?: int|null}  $filters
     * @return Collection<int, Product>
     */
    public function adminList(array $filters = []): Collection
    {
        return $this->adminFilteredQuery($filters)->get();
    }

    /**
     * @param  array{search?: string|null, type?: string|null, is_active?: bool|null, booking_round_id?: int|null}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function adminPaginate(array $filters = [], int $perPage = 9): LengthAwarePaginator
    {
        return $this->adminFilteredQuery($filters)->paginate($perPage);
    }

    /**
     * @param  array{search?: string|null, type?: string|null, is_active?: bool|null, booking_round_id?: int|null}  $filters
     * @return Builder<Product>
     */
    private function adminFilteredQuery(array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $type = $filters['type'] ?? null;
        $isActive = $filters['is_active'] ?? null;
        $roundId = $filters['booking_round_id'] ?? null;

        return Product::query()
            ->with(['coverImage', 'bookingRounds'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->when(is_string($type) && $type !== '', fn ($query) => $query->where('type', $type))
            ->when(is_bool($isActive), fn ($query) => $query->where('is_active', $isActive))
            ->when(is_int($roundId), fn ($query) => $query->whereHas(
                'bookingRounds',
                fn ($query) => $query->where((new BookingRound)->getTable().'.id', $roundId),
            ))
            ->orderBy('name');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        $payload = $this->validated($data);

        return DB::transaction(function () use ($payload) {
            $product = Product::query()->create([
                'name' => $payload['name'],
                'slug' => 'tmp-'.Str::lower(Str::random(12)),
                'description' => $payload['description'] ?? null,
                'type' => $payload['type'],
                'price' => $payload['price'],
                'is_active' => $payload['is_active'] ?? true,
            ]);

            $product->update(['slug' => 'product-'.$product->id]);
            $this->syncComposition($product, $payload);

            return $product->fresh(['components.optionGroups.values', 'optionGroups.values']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        $payload = $this->validated($data);

        return DB::transaction(function () use ($product, $payload) {
            $product->update([
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'type' => $payload['type'],
                'price' => $payload['price'],
                'is_active' => $payload['is_active'] ?? $product->is_active,
            ]);

            $this->syncComposition($product, $payload);

            return $product->fresh(['components.optionGroups.values', 'optionGroups.values']);
        });
    }

    public function setActive(Product $product, bool $active): Product
    {
        $product->update(['is_active' => $active]);

        return $product->fresh();
    }

    public function duplicate(Product $product): Product
    {
        $product->loadMissing(['components.optionGroups.values', 'optionGroups.values', 'images']);

        return DB::transaction(function () use ($product) {
            $copy = $this->create($this->duplicatePayload($product));
            $this->images->copyImagesTo($product, $copy);

            return $copy->fresh(['components.optionGroups.values', 'optionGroups.values', 'images']);
        });
    }

    /**
     * @param  list<int>  $productIds
     * @return Collection<int, Product>
     */
    public function cloneIntoRound(BookingRound $source, BookingRound $destination, array $productIds): Collection
    {
        if ($source->is($destination)) {
            throw ValidationException::withMessages([
                'source_round_id' => 'เลือกรอบต้นทางคนละรอบกับรอบปลายทาง',
            ]);
        }

        $ids = array_values(array_unique(array_map('intval', $productIds)));

        if ($ids === []) {
            throw ValidationException::withMessages([
                'product_ids' => 'เลือกสินค้าอย่างน้อย 1 รายการ',
            ]);
        }

        $products = $source->products()
            ->whereIn((new Product)->getTable().'.id', $ids)
            ->with(['components.optionGroups.values', 'optionGroups.values', 'images'])
            ->orderBy('name')
            ->get();

        if ($products->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'product_ids' => 'เลือกได้เฉพาะสินค้าที่อยู่ในรอบต้นทาง',
            ]);
        }

        return DB::transaction(function () use ($products, $destination) {
            $copies = $products->map(fn (Product $product) => $this->duplicate($product));
            $destination->products()->syncWithoutDetaching($copies->pluck('id')->all());

            return $copies;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function duplicatePayload(Product $product): array
    {
        return [
            'name' => $product->name.' (สำเนา)',
            'description' => $product->description,
            'type' => $product->type->value,
            'price' => $product->price,
            'is_active' => false,
            'option_groups' => $product->type === ProductType::Simple
                ? $this->groupsPayload($product->optionGroups)
                : [],
            'components' => $product->type === ProductType::Bundle
                ? $product->components->map(fn (ProductComponent $component) => [
                    'name' => $component->name,
                    'option_groups' => $this->groupsPayload($component->optionGroups),
                ])->all()
                : [],
        ];
    }

    /**
     * @param  Collection<int, ProductOptionGroup>  $groups
     * @return list<array{key: string, label: string, values: list<string>, depends_on_key: string|null, depends_on_values: array<string, list<string>>|null}>
     */
    private function groupsPayload(Collection $groups): array
    {
        return $groups->map(fn (ProductOptionGroup $group) => [
            'key' => $group->key,
            'label' => $group->label,
            'values' => $group->values->pluck('value')->all(),
            'depends_on_key' => $group->depends_on_key,
            'depends_on_values' => $group->depends_on_values,
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validated(array $data): array
    {
        $payload = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::enum(ProductType::class)],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'option_groups' => ['nullable', 'array'],
            'option_groups.*.key' => ['required_with:option_groups', 'string', 'max:64'],
            'option_groups.*.label' => ['required_with:option_groups', 'string', 'max:255'],
            'option_groups.*.values' => ['required_with:option_groups', 'array', 'min:1'],
            'option_groups.*.values.*' => ['required', 'string', 'max:64'],
            'option_groups.*.depends_on_key' => ['nullable', 'string', 'max:64'],
            'option_groups.*.depends_on_values' => ['nullable', 'array'],
            'option_groups.*.depends_on_values.*' => ['array', 'min:1'],
            'option_groups.*.depends_on_values.*.*' => ['string', 'max:64'],
            'components' => ['nullable', 'array'],
            'components.*.name' => ['required_with:components', 'string', 'max:255'],
            'components.*.option_groups' => ['nullable', 'array'],
            'components.*.option_groups.*.key' => ['required', 'string', 'max:64'],
            'components.*.option_groups.*.label' => ['required', 'string', 'max:255'],
            'components.*.option_groups.*.values' => ['required', 'array', 'min:1'],
            'components.*.option_groups.*.values.*' => ['required', 'string', 'max:64'],
            'components.*.option_groups.*.depends_on_key' => ['nullable', 'string', 'max:64'],
            'components.*.option_groups.*.depends_on_values' => ['nullable', 'array'],
            'components.*.option_groups.*.depends_on_values.*' => ['array', 'min:1'],
            'components.*.option_groups.*.depends_on_values.*.*' => ['string', 'max:64'],
        ])->validate();

        $type = ProductType::from($payload['type']);
        $components = $payload['components'] ?? [];
        $optionGroups = $payload['option_groups'] ?? [];

        if ($type === ProductType::Bundle && $components === []) {
            throw ValidationException::withMessages([
                'components' => 'สินค้าแบบชุดต้องมีส่วนประกอบอย่างน้อย 1 รายการ',
            ]);
        }

        if ($type === ProductType::Simple && $components !== []) {
            throw ValidationException::withMessages([
                'components' => 'สินค้าแบบแยกชิ้นใส่ส่วนประกอบชุดไม่ได้',
            ]);
        }

        if ($type === ProductType::Simple) {
            $this->assertValidOptionGroupDependencies($optionGroups, 'option_groups');
        } else {
            foreach ($components as $componentIndex => $component) {
                $this->assertValidOptionGroupDependencies(
                    $component['option_groups'] ?? [],
                    "components.{$componentIndex}.option_groups",
                );
            }
        }

        $payload['type'] = $type;
        $payload['components'] = $components;
        $payload['option_groups'] = $optionGroups;

        return $payload;
    }

    /**
     * @param  list<array{key: string, label: string, values: list<string>, depends_on_key?: string|null, depends_on_values?: array<string, list<string>>|null}>  $groups
     */
    private function assertValidOptionGroupDependencies(array $groups, string $errorPrefix): void
    {
        $keysByIndex = [];
        $groupsByKey = [];

        foreach ($groups as $index => $group) {
            $key = (string) ($group['key'] ?? '');
            $keysByIndex[$index] = $key;
            $groupsByKey[$key] = $group;
        }

        foreach ($groups as $index => $group) {
            $dependsOnKey = $group['depends_on_key'] ?? null;

            if (! is_string($dependsOnKey) || $dependsOnKey === '') {
                continue;
            }

            $field = "{$errorPrefix}.{$index}.depends_on_key";
            $ownKey = (string) ($group['key'] ?? '');

            if ($dependsOnKey === $ownKey) {
                throw ValidationException::withMessages([
                    $field => 'กลุ่มตัวเลือกขึ้นกับตัวเองไม่ได้',
                ]);
            }

            $parentIndex = array_search($dependsOnKey, $keysByIndex, true);

            if ($parentIndex === false) {
                throw ValidationException::withMessages([
                    $field => 'กลุ่มต้นทางต้องอยู่ในกลุ่มตัวเลือกเดียวกัน',
                ]);
            }

            if ($parentIndex >= $index) {
                throw ValidationException::withMessages([
                    $field => 'กลุ่มต้นทางต้องอยู่ก่อนกลุ่มนี้',
                ]);
            }

            $parent = $groupsByKey[$dependsOnKey];
            $parentDependsOn = $parent['depends_on_key'] ?? null;

            if (is_string($parentDependsOn) && $parentDependsOn !== '') {
                throw ValidationException::withMessages([
                    $field => 'ขึ้นกับกลุ่มที่ขึ้นกับกลุ่มอื่นไม่ได้ (ได้แค่หนึ่งชั้น)',
                ]);
            }

            $map = $group['depends_on_values'] ?? null;

            if (! is_array($map) || $map === []) {
                throw ValidationException::withMessages([
                    "{$errorPrefix}.{$index}.depends_on_values" => 'ต้องระบุค่าที่อนุญาตสำหรับแต่ละค่าต้นทาง',
                ]);
            }

            $parentValues = $parent['values'] ?? [];
            $childValues = $group['values'] ?? [];

            foreach ($map as $parentValue => $allowed) {
                if (! in_array((string) $parentValue, $parentValues, true)) {
                    throw ValidationException::withMessages([
                        "{$errorPrefix}.{$index}.depends_on_values" => 'ค่าต้นทางต้องอยู่ในกลุ่มต้นทาง',
                    ]);
                }

                if (! is_array($allowed) || $allowed === []) {
                    throw ValidationException::withMessages([
                        "{$errorPrefix}.{$index}.depends_on_values" => 'แต่ละค่าต้นทางต้องมีค่าที่อนุญาตอย่างน้อย 1 ค่า',
                    ]);
                }

                foreach ($allowed as $allowedValue) {
                    if (! in_array((string) $allowedValue, $childValues, true)) {
                        throw ValidationException::withMessages([
                            "{$errorPrefix}.{$index}.depends_on_values" => 'ค่าที่อนุญาตต้องอยู่ในกลุ่มนี้',
                        ]);
                    }
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncComposition(Product $product, array $payload): void
    {
        $product->components()->delete();
        ProductOptionGroup::query()->where('product_id', $product->id)->delete();

        if ($product->type === ProductType::Simple) {
            $this->syncOptionGroups($product, null, $payload['option_groups'] ?? []);

            return;
        }

        foreach ($payload['components'] as $index => $componentData) {
            $component = $product->components()->create([
                'name' => $componentData['name'],
                'sort_order' => $index,
            ]);

            $this->syncOptionGroups($product, $component, $componentData['option_groups'] ?? []);
        }
    }

    /**
     * @param  list<array{key: string, label: string, values: list<string>, depends_on_key?: string|null, depends_on_values?: array<string, list<string>>|null}>  $groups
     */
    private function syncOptionGroups(Product $product, ?ProductComponent $component, array $groups): void
    {
        foreach ($groups as $index => $groupData) {
            $dependsOnKey = $groupData['depends_on_key'] ?? null;
            $dependsOnValues = $groupData['depends_on_values'] ?? null;

            if (! is_string($dependsOnKey) || $dependsOnKey === '') {
                $dependsOnKey = null;
                $dependsOnValues = null;
            }

            $group = ProductOptionGroup::query()->create([
                'product_id' => $product->id,
                'product_component_id' => $component?->id,
                'key' => $groupData['key'],
                'label' => $groupData['label'],
                'depends_on_key' => $dependsOnKey,
                'depends_on_values' => $dependsOnValues,
                'sort_order' => $index,
            ]);

            foreach ($groupData['values'] as $valueIndex => $value) {
                $group->values()->create([
                    'value' => $value,
                    'label' => $value,
                    'sort_order' => $valueIndex,
                ]);
            }
        }
    }
}
