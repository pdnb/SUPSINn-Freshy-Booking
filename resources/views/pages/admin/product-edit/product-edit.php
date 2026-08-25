<?php

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOptionGroup;
use App\Services\Catalog\CatalogService;
use App\Services\Catalog\ProductImageService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Layout('layouts.admin')]
#[Title('แก้ไขสินค้า')]
class extends Component
{
    use WithFileUploads;

    public ?int $productId = null;

    public string $name = '';

    public string $description = '';

    public string $type = 'simple';

    public string $price = '';

    public bool $is_active = false;

    /**
     * @var list<array{key: string, label: string, values: list<string>, depends_on_key: string|null, depends_on_values: array<string, list<string>>|null}>
     */
    public array $optionGroups = [];

    /**
     * @var list<array{name: string, option_groups: list<array{key: string, label: string, values: list<string>, depends_on_key: string|null, depends_on_values: array<string, list<string>>|null}>}>
     */
    public array $components = [];

    /**
     * @var list<TemporaryUploadedFile>
     */
    public array $uploads = [];

    public bool $showDeleteConfirm = false;

    public string $deleteKind = '';

    public ?int $deleteComponentIndex = null;

    public ?int $deleteGroupIndex = null;

    public ?int $pendingImageId = null;

    public string $deleteConfirmTitle = '';

    public string $deleteConfirmMessage = '';

    public function mount(?Product $product = null): void
    {
        if ($product === null || ! $product->exists) {
            $this->optionGroups = [$this->blankOptionGroup('size', 'ไซส์', ['S', 'M', 'L'])];

            return;
        }

        $product->load(['components.optionGroups.values', 'optionGroups.values', 'images']);
        $this->productId = $product->id;
        $this->name = $product->name;
        $this->description = (string) $product->description;
        $this->type = $product->type->value;
        $this->price = (string) $product->price;
        $this->is_active = $product->is_active;
        $this->optionGroups = $product->optionGroups->map(fn ($group) => $this->groupFromModel($group))->all();
        $this->components = $product->components->map(fn ($component) => [
            'name' => $component->name,
            'option_groups' => $component->optionGroups->map(fn ($group) => $this->groupFromModel($group))->all(),
        ])->all();
    }

    public function addOptionGroup(): void
    {
        $this->optionGroups[] = $this->blankOptionGroup();
    }

    public function askRemoveOptionGroup(int $index): void
    {
        if (! isset($this->optionGroups[$index])) {
            return;
        }

        $label = trim((string) ($this->optionGroups[$index]['label'] ?? ''));
        $this->openDeleteConfirm(
            kind: 'option_group',
            title: 'ลบตัวเลือก',
            message: $label !== ''
                ? "ต้องการลบตัวเลือก «{$label}» หรือไม่?"
                : 'ต้องการลบตัวเลือกนี้หรือไม่?',
            componentIndex: null,
            groupIndex: $index,
        );
    }

    public function removeOptionGroup(int $index): void
    {
        if (! isset($this->optionGroups[$index])) {
            return;
        }

        $removedKey = (string) ($this->optionGroups[$index]['key'] ?? '');
        unset($this->optionGroups[$index]);
        $this->optionGroups = array_values($this->optionGroups);
        $this->clearDependenciesOnKey($this->optionGroups, $removedKey);
    }

    public function pushOptionGroupValues(int $groupIndex, string $raw): void
    {
        if (! isset($this->optionGroups[$groupIndex])) {
            return;
        }

        $this->optionGroups[$groupIndex]['values'] = $this->mergeTags(
            $this->optionGroups[$groupIndex]['values'] ?? [],
            $raw,
        );
    }

    public function removeOptionGroupValue(int $groupIndex, int $valueIndex): void
    {
        if (! isset($this->optionGroups[$groupIndex]['values'][$valueIndex])) {
            return;
        }

        $removed = (string) $this->optionGroups[$groupIndex]['values'][$valueIndex];
        unset($this->optionGroups[$groupIndex]['values'][$valueIndex]);
        $this->optionGroups[$groupIndex]['values'] = array_values($this->optionGroups[$groupIndex]['values']);
        $this->pruneValueFromDependencies($this->optionGroups, $groupIndex, $removed);
    }

    public function setOptionGroupParent(int $groupIndex, string $parentKey): void
    {
        if (! isset($this->optionGroups[$groupIndex])) {
            return;
        }

        $this->applyParent($this->optionGroups[$groupIndex], $parentKey);
    }

    public function toggleOptionGroupAllowedValue(int $groupIndex, string $parentValue, string $childValue): void
    {
        if (! isset($this->optionGroups[$groupIndex])) {
            return;
        }

        $this->toggleAllowed($this->optionGroups[$groupIndex], $parentValue, $childValue);
    }

    public function addComponent(): void
    {
        $this->components[] = [
            'name' => '',
            'option_groups' => [$this->blankOptionGroup('size', 'ไซส์', ['S', 'M', 'L'])],
        ];
    }

    public function askRemoveComponent(int $index): void
    {
        if (! isset($this->components[$index])) {
            return;
        }

        $name = trim((string) ($this->components[$index]['name'] ?? ''));
        $this->openDeleteConfirm(
            kind: 'component',
            title: 'ลบสินค้า',
            message: $name !== ''
                ? "ต้องการลบ «{$name}» ออกจากชุดหรือไม่?"
                : 'ต้องการลบสินค้านี้ออกจากชุดหรือไม่?',
            componentIndex: $index,
            groupIndex: null,
        );
    }

    public function removeComponent(int $index): void
    {
        unset($this->components[$index]);
        $this->components = array_values($this->components);
    }

    public function addComponentOptionGroup(int $componentIndex): void
    {
        if (! isset($this->components[$componentIndex])) {
            return;
        }

        $this->components[$componentIndex]['option_groups'][] = $this->blankOptionGroup();
    }

    public function askRemoveComponentOptionGroup(int $componentIndex, int $groupIndex): void
    {
        if (! isset($this->components[$componentIndex]['option_groups'][$groupIndex])) {
            return;
        }

        $label = trim((string) ($this->components[$componentIndex]['option_groups'][$groupIndex]['label'] ?? ''));
        $this->openDeleteConfirm(
            kind: 'component_option_group',
            title: 'ลบตัวเลือก',
            message: $label !== ''
                ? "ต้องการลบตัวเลือก «{$label}» หรือไม่?"
                : 'ต้องการลบตัวเลือกนี้หรือไม่?',
            componentIndex: $componentIndex,
            groupIndex: $groupIndex,
        );
    }

    public function removeComponentOptionGroup(int $componentIndex, int $groupIndex): void
    {
        if (! isset($this->components[$componentIndex]['option_groups'][$groupIndex])) {
            return;
        }

        $removedKey = (string) ($this->components[$componentIndex]['option_groups'][$groupIndex]['key'] ?? '');
        unset($this->components[$componentIndex]['option_groups'][$groupIndex]);
        $this->components[$componentIndex]['option_groups'] = array_values(
            $this->components[$componentIndex]['option_groups'],
        );
        $this->clearDependenciesOnKey($this->components[$componentIndex]['option_groups'], $removedKey);
    }

    public function setComponentOptionGroupParent(int $componentIndex, int $groupIndex, string $parentKey): void
    {
        if (! isset($this->components[$componentIndex]['option_groups'][$groupIndex])) {
            return;
        }

        $this->applyParent($this->components[$componentIndex]['option_groups'][$groupIndex], $parentKey);
    }

    public function toggleComponentOptionGroupAllowedValue(
        int $componentIndex,
        int $groupIndex,
        string $parentValue,
        string $childValue,
    ): void {
        if (! isset($this->components[$componentIndex]['option_groups'][$groupIndex])) {
            return;
        }

        $this->toggleAllowed(
            $this->components[$componentIndex]['option_groups'][$groupIndex],
            $parentValue,
            $childValue,
        );
    }

    public function closeDeleteConfirm(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteKind = '';
        $this->deleteComponentIndex = null;
        $this->deleteGroupIndex = null;
        $this->pendingImageId = null;
        $this->deleteConfirmTitle = '';
        $this->deleteConfirmMessage = '';
    }

    public function confirmDelete(ProductImageService $images): void
    {
        match ($this->deleteKind) {
            'component' => $this->deleteComponentIndex !== null
                ? $this->removeComponent($this->deleteComponentIndex)
                : null,
            'component_option_group' => $this->deleteComponentIndex !== null && $this->deleteGroupIndex !== null
                ? $this->removeComponentOptionGroup($this->deleteComponentIndex, $this->deleteGroupIndex)
                : null,
            'option_group' => $this->deleteGroupIndex !== null
                ? $this->removeOptionGroup($this->deleteGroupIndex)
                : null,
            'image' => $this->pendingImageId !== null
                ? $images->deleteImage($this->currentProductImage($this->pendingImageId))
                : null,
            default => null,
        };

        $this->closeDeleteConfirm();
    }

    private function openDeleteConfirm(
        string $kind,
        string $title,
        string $message,
        ?int $componentIndex,
        ?int $groupIndex,
    ): void {
        $this->deleteKind = $kind;
        $this->deleteConfirmTitle = $title;
        $this->deleteConfirmMessage = $message;
        $this->deleteComponentIndex = $componentIndex;
        $this->deleteGroupIndex = $groupIndex;
        $this->showDeleteConfirm = true;
    }

    public function reorderImages(int $imageId, int $position, ProductImageService $images): void
    {
        if ($this->productId === null) {
            return;
        }

        $images->moveToPosition($this->currentProductImage($imageId), $position);
    }

    public function setCover(int $imageId, ProductImageService $images): void
    {
        if ($this->productId === null) {
            return;
        }

        $images->setAsCover($this->currentProductImage($imageId));
    }

    public function askDeleteImage(int $imageId): void
    {
        if ($this->productId === null) {
            return;
        }

        $this->currentProductImage($imageId);
        $this->pendingImageId = $imageId;
        $this->openDeleteConfirm(
            kind: 'image',
            title: 'ลบรูปภาพ',
            message: 'ต้องการลบรูปนี้หรือไม่?',
            componentIndex: null,
            groupIndex: null,
        );
    }

    public function removePendingUpload(int $index): void
    {
        if (! isset($this->uploads[$index])) {
            return;
        }

        unset($this->uploads[$index]);
        $this->uploads = array_values($this->uploads);
    }

    public function pushComponentOptionGroupValues(int $componentIndex, int $groupIndex, string $raw): void
    {
        if (! isset($this->components[$componentIndex]['option_groups'][$groupIndex])) {
            return;
        }

        $this->components[$componentIndex]['option_groups'][$groupIndex]['values'] = $this->mergeTags(
            $this->components[$componentIndex]['option_groups'][$groupIndex]['values'] ?? [],
            $raw,
        );
    }

    public function removeComponentOptionGroupValue(int $componentIndex, int $groupIndex, int $valueIndex): void
    {
        if (! isset($this->components[$componentIndex]['option_groups'][$groupIndex]['values'][$valueIndex])) {
            return;
        }

        $removed = (string) $this->components[$componentIndex]['option_groups'][$groupIndex]['values'][$valueIndex];
        unset($this->components[$componentIndex]['option_groups'][$groupIndex]['values'][$valueIndex]);
        $this->components[$componentIndex]['option_groups'][$groupIndex]['values'] = array_values(
            $this->components[$componentIndex]['option_groups'][$groupIndex]['values'],
        );
        $this->pruneValueFromDependencies(
            $this->components[$componentIndex]['option_groups'],
            $groupIndex,
            $removed,
        );
    }

    public function saveDraft(CatalogService $catalog, ProductImageService $images): void
    {
        $this->is_active = false;
        $this->persist($catalog, $images, 'บันทึกฉบับร่างแล้ว');
    }

    public function publish(CatalogService $catalog, ProductImageService $images): void
    {
        $this->is_active = true;
        $this->persist($catalog, $images, 'เผยแพร่สินค้าแล้ว');
    }

    public function save(CatalogService $catalog, ProductImageService $images): void
    {
        $this->persist(
            $catalog,
            $images,
            filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN) ? 'เผยแพร่สินค้าแล้ว' : 'บันทึกฉบับร่างแล้ว',
        );
    }

    private function persist(CatalogService $catalog, ProductImageService $images, string $message): void
    {
        try {
            $pendingUploads = $this->uploads !== [] ? $images->validated($this->uploads) : [];

            $payload = [
                'name' => $this->name,
                'description' => $this->description !== '' ? $this->description : null,
                'type' => $this->type,
                'price' => $this->price,
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
                'option_groups' => $this->type === ProductType::Simple->value
                    ? $this->normalizedGroups($this->optionGroups)
                    : [],
                'components' => $this->type === ProductType::Bundle->value
                    ? $this->normalizedComponents()
                    : [],
            ];

            $product = $this->productId === null
                ? $catalog->create($payload)
                : $catalog->update(Product::query()->findOrFail($this->productId), $payload);

            if ($pendingUploads !== []) {
                $images->addImages($product, $pendingUploads);
                $this->uploads = [];
            }

            session()->flash('status', $message);
            $this->redirect(route('admin.products'));
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        }
    }

    public function render()
    {
        $product = $this->productId === null
            ? null
            : Product::query()->with('images')->find($this->productId);

        return $this->view([
            'product' => $product,
        ]);
    }

    /**
     * @param  list<array{key: string, label: string, values: list<string>|string, depends_on_key?: string|null, depends_on_values?: array<string, list<string>>|null}>  $groups
     * @return list<array{key: string, label: string, values: list<string>, depends_on_key: string|null, depends_on_values: array<string, list<string>>|null}>
     */
    private function normalizedGroups(array $groups): array
    {
        $usedKeys = [];
        $keyMap = [];

        $normalized = collect($groups)
            ->map(function (array $group) use (&$usedKeys, &$keyMap): array {
                $oldKey = (string) ($group['key'] ?? '');
                $label = trim((string) ($group['label'] ?? ''));
                $values = $this->normalizeTagList($group['values'] ?? []);
                $newKey = $this->uniqueOptionGroupKey(
                    $oldKey,
                    $label,
                    count($usedKeys),
                    $usedKeys,
                );

                if ($oldKey !== '') {
                    $keyMap[$oldKey] = $newKey;
                }

                return [
                    'key' => $newKey,
                    'label' => $label,
                    'values' => $values,
                    'depends_on_key' => $group['depends_on_key'] ?? null,
                    'depends_on_values' => is_array($group['depends_on_values'] ?? null)
                        ? $group['depends_on_values']
                        : null,
                ];
            })
            ->filter(fn (array $group): bool => $group['label'] !== '' && $group['values'] !== [])
            ->values()
            ->all();

        foreach ($normalized as $index => $group) {
            $dependsOnKey = $group['depends_on_key'] ?? null;

            if (! is_string($dependsOnKey) || $dependsOnKey === '') {
                $normalized[$index]['depends_on_key'] = null;
                $normalized[$index]['depends_on_values'] = null;

                continue;
            }

            $mappedParent = $keyMap[$dependsOnKey] ?? $dependsOnKey;
            $normalized[$index]['depends_on_key'] = $mappedParent;
            $normalized[$index]['depends_on_values'] = $this->sanitizeDependsOnValues(
                $group['depends_on_values'] ?? null,
                $group['values'],
            );
        }

        return $normalized;
    }

    /**
     * @param  array<string, list<string>>|null  $map
     * @param  list<string>  $childValues
     * @return array<string, list<string>>|null
     */
    private function sanitizeDependsOnValues(?array $map, array $childValues): ?array
    {
        if ($map === null || $map === []) {
            return null;
        }

        $sanitized = [];

        foreach ($map as $parentValue => $allowed) {
            if (! is_array($allowed)) {
                continue;
            }

            $filtered = array_values(array_filter(
                $allowed,
                fn (mixed $value): bool => is_string($value) && in_array($value, $childValues, true),
            ));

            if ($filtered !== []) {
                $sanitized[(string) $parentValue] = $filtered;
            }
        }

        return $sanitized === [] ? null : $sanitized;
    }

    /**
     * @param  list<string>|string  $values
     * @return list<string>
     */
    private function normalizeTagList(array|string $values): array
    {
        $items = is_array($values) ? $values : (preg_split('/[,，]/u', $values) ?: []);

        return collect($items)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $existing
     * @return list<string>
     */
    private function mergeTags(array $existing, string $raw): array
    {
        foreach ($this->normalizeTagList($raw) as $tag) {
            if (! in_array($tag, $existing, true)) {
                $existing[] = $tag;
            }
        }

        return array_values($existing);
    }

    /**
     * @param  array<string, true>  $usedKeys
     */
    private function uniqueOptionGroupKey(string $existingKey, string $label, int $index, array &$usedKeys): string
    {
        $slug = Str::slug($label);
        $base = $existingKey !== ''
            ? $existingKey
            : ($slug !== '' ? $slug : 'option_'.($index + 1));
        $base = Str::lower(Str::limit($base, 64, ''));
        $key = $base;
        $suffix = 2;

        while (isset($usedKeys[$key])) {
            $key = Str::limit($base, 60, '').'_'.$suffix;
            $suffix++;
        }

        $usedKeys[$key] = true;

        return $key;
    }

    private function currentProductImage(int $imageId): ProductImage
    {
        return ProductImage::query()
            ->where('product_id', $this->productId)
            ->findOrFail($imageId);
    }

    /**
     * @return list<array{name: string, option_groups: list<array{key: string, label: string, values: list<string>, depends_on_key: string|null, depends_on_values: array<string, list<string>>|null}>}>
     */
    private function normalizedComponents(): array
    {
        return collect($this->components)
            ->map(fn (array $component): array => [
                'name' => $component['name'],
                'option_groups' => $this->normalizedGroups($component['option_groups'] ?? []),
            ])
            ->filter(fn (array $component): bool => $component['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array{key: string, label: string, values: list<string>, depends_on_key: string|null, depends_on_values: array<string, list<string>>|null}
     */
    private function blankOptionGroup(string $key = '', string $label = '', array $values = []): array
    {
        if ($key === '') {
            $key = 'option_'.Str::lower(Str::random(6));
        }

        return [
            'key' => $key,
            'label' => $label,
            'values' => $values,
            'depends_on_key' => null,
            'depends_on_values' => null,
        ];
    }

    /**
     * @param  ProductOptionGroup  $group
     * @return array{key: string, label: string, values: list<string>, depends_on_key: string|null, depends_on_values: array<string, list<string>>|null}
     */
    private function groupFromModel($group): array
    {
        return [
            'key' => $group->key,
            'label' => $group->label,
            'values' => $group->values->pluck('value')->all(),
            'depends_on_key' => $group->depends_on_key,
            'depends_on_values' => $group->depends_on_values,
        ];
    }

    /**
     * @param  array{key: string, label: string, values: list<string>, depends_on_key: string|null, depends_on_values: array<string, list<string>>|null}  $group
     */
    private function applyParent(array &$group, string $parentKey): void
    {
        $parentKey = trim($parentKey);

        if ($parentKey === '' || $parentKey === ($group['key'] ?? '')) {
            $group['depends_on_key'] = null;
            $group['depends_on_values'] = null;

            return;
        }

        $group['depends_on_key'] = $parentKey;
        $group['depends_on_values'] = [];
    }

    /**
     * @param  array{key: string, label: string, values: list<string>, depends_on_key: string|null, depends_on_values: array<string, list<string>>|null}  $group
     */
    private function toggleAllowed(array &$group, string $parentValue, string $childValue): void
    {
        if (($group['depends_on_key'] ?? null) === null) {
            return;
        }

        $map = is_array($group['depends_on_values'] ?? null) ? $group['depends_on_values'] : [];
        $allowed = is_array($map[$parentValue] ?? null) ? $map[$parentValue] : [];

        if (in_array($childValue, $allowed, true)) {
            $allowed = array_values(array_filter($allowed, fn (string $value): bool => $value !== $childValue));
        } else {
            $allowed[] = $childValue;
        }

        if ($allowed === []) {
            unset($map[$parentValue]);
        } else {
            $map[$parentValue] = $allowed;
        }

        $group['depends_on_values'] = $map;
    }

    /**
     * @param  list<array{key: string, label: string, values: list<string>, depends_on_key: string|null, depends_on_values: array<string, list<string>>|null}>  $groups
     */
    private function clearDependenciesOnKey(array &$groups, string $removedKey): void
    {
        if ($removedKey === '') {
            return;
        }

        foreach ($groups as $index => $group) {
            if (($group['depends_on_key'] ?? null) === $removedKey) {
                $groups[$index]['depends_on_key'] = null;
                $groups[$index]['depends_on_values'] = null;
            }
        }
    }

    /**
     * @param  list<array{key: string, label: string, values: list<string>, depends_on_key: string|null, depends_on_values: array<string, list<string>>|null}>  $groups
     */
    private function pruneValueFromDependencies(array &$groups, int $changedIndex, string $removedValue): void
    {
        $changedKey = (string) ($groups[$changedIndex]['key'] ?? '');

        if (isset($groups[$changedIndex]['depends_on_values']) && is_array($groups[$changedIndex]['depends_on_values'])) {
            foreach ($groups[$changedIndex]['depends_on_values'] as $parentValue => $allowed) {
                if (! is_array($allowed)) {
                    continue;
                }

                $filtered = array_values(array_filter(
                    $allowed,
                    fn (string $value): bool => $value !== $removedValue,
                ));

                if ($filtered === []) {
                    unset($groups[$changedIndex]['depends_on_values'][$parentValue]);
                } else {
                    $groups[$changedIndex]['depends_on_values'][$parentValue] = $filtered;
                }
            }
        }

        if ($changedKey === '') {
            return;
        }

        foreach ($groups as $index => $group) {
            if (($group['depends_on_key'] ?? null) !== $changedKey) {
                continue;
            }

            $map = is_array($group['depends_on_values'] ?? null) ? $group['depends_on_values'] : [];
            unset($map[$removedValue]);
            $groups[$index]['depends_on_values'] = $map;
        }
    }
};
