<?php

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductImage;
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
     * @var list<array{key: string, label: string, values: list<string>}>
     */
    public array $optionGroups = [];

    /**
     * @var list<array{name: string, option_groups: list<array{key: string, label: string, values: list<string>}>}>
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

    public string $deleteConfirmTitle = '';

    public string $deleteConfirmMessage = '';

    public function mount(?Product $product = null): void
    {
        if ($product === null || ! $product->exists) {
            $this->optionGroups = [['key' => 'size', 'label' => 'ไซส์', 'values' => ['S', 'M', 'L']]];

            return;
        }

        $product->load(['components.optionGroups.values', 'optionGroups.values', 'images']);
        $this->productId = $product->id;
        $this->name = $product->name;
        $this->description = (string) $product->description;
        $this->type = $product->type->value;
        $this->price = (string) $product->price;
        $this->is_active = $product->is_active;
        $this->optionGroups = $product->optionGroups->map(fn ($group) => [
            'key' => $group->key,
            'label' => $group->label,
            'values' => $group->values->pluck('value')->all(),
        ])->all();
        $this->components = $product->components->map(fn ($component) => [
            'name' => $component->name,
            'option_groups' => $component->optionGroups->map(fn ($group) => [
                'key' => $group->key,
                'label' => $group->label,
                'values' => $group->values->pluck('value')->all(),
            ])->all(),
        ])->all();
    }

    public function addOptionGroup(): void
    {
        $this->optionGroups[] = ['key' => '', 'label' => '', 'values' => []];
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
        unset($this->optionGroups[$index]);
        $this->optionGroups = array_values($this->optionGroups);
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

        unset($this->optionGroups[$groupIndex]['values'][$valueIndex]);
        $this->optionGroups[$groupIndex]['values'] = array_values($this->optionGroups[$groupIndex]['values']);
    }

    public function addComponent(): void
    {
        $this->components[] = [
            'name' => '',
            'option_groups' => [['key' => 'size', 'label' => 'ไซส์', 'values' => ['S', 'M', 'L']]],
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

        $this->components[$componentIndex]['option_groups'][] = ['key' => '', 'label' => '', 'values' => []];
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

        unset($this->components[$componentIndex]['option_groups'][$groupIndex]);
        $this->components[$componentIndex]['option_groups'] = array_values(
            $this->components[$componentIndex]['option_groups'],
        );
    }

    public function closeDeleteConfirm(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteKind = '';
        $this->deleteComponentIndex = null;
        $this->deleteGroupIndex = null;
        $this->deleteConfirmTitle = '';
        $this->deleteConfirmMessage = '';
    }

    public function confirmDelete(): void
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

        unset($this->components[$componentIndex]['option_groups'][$groupIndex]['values'][$valueIndex]);
        $this->components[$componentIndex]['option_groups'][$groupIndex]['values'] = array_values(
            $this->components[$componentIndex]['option_groups'][$groupIndex]['values'],
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

    public function deleteImage(int $imageId, ProductImageService $images): void
    {
        $image = ProductImage::query()->findOrFail($imageId);
        $images->deleteImage($image);
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
     * @param  list<array{key: string, label: string, values: list<string>|string}>  $groups
     * @return list<array{key: string, label: string, values: list<string>}>
     */
    private function normalizedGroups(array $groups): array
    {
        $usedKeys = [];

        return collect($groups)
            ->map(fn (array $group): array => [
                'key' => (string) ($group['key'] ?? ''),
                'label' => trim((string) ($group['label'] ?? '')),
                'values' => $this->normalizeTagList($group['values'] ?? []),
            ])
            ->filter(fn (array $group): bool => $group['label'] !== '' && $group['values'] !== [])
            ->values()
            ->map(function (array $group, int $index) use (&$usedKeys): array {
                $group['key'] = $this->uniqueOptionGroupKey(
                    $group['key'],
                    $group['label'],
                    $index,
                    $usedKeys,
                );

                return $group;
            })
            ->all();
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

    /**
     * @return list<array{name: string, option_groups: list<array{key: string, label: string, values: list<string>}>}>
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
};
