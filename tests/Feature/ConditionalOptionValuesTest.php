<?php

use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Catalog\CatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array{key: string, label: string, values: list<string>, depends_on_key: string|null, depends_on_values: array<string, list<string>>|null}
 */
function skirtTypeGroup(): array
{
    return [
        'key' => 'skirt_type',
        'label' => 'ประเภทกระโปรง',
        'values' => ['ทรงเอ', 'จีบรอบ'],
        'depends_on_key' => null,
        'depends_on_values' => null,
    ];
}

/**
 * @return array{key: string, label: string, values: list<string>, depends_on_key: string|null, depends_on_values: array<string, list<string>>|null}
 */
function skirtSizeGroup(): array
{
    return [
        'key' => 'skirt_size',
        'label' => 'ไซส์กระโปรง',
        'values' => ['S', 'M', 'L', 'XL'],
        'depends_on_key' => 'skirt_type',
        'depends_on_values' => [
            'ทรงเอ' => ['S', 'M', 'L'],
            'จีบรอบ' => ['M', 'L', 'XL'],
        ],
    ];
}

function skirtProduct(): Product
{
    return app(CatalogService::class)->create([
        'name' => 'ชุดนักศึกษาหญิง',
        'type' => 'simple',
        'price' => '890',
        'option_groups' => [skirtTypeGroup(), skirtSizeGroup()],
    ]);
}

function skirtBundleProduct(): Product
{
    return app(CatalogService::class)->create([
        'name' => 'คอมโบนักศึกษาหญิง',
        'type' => 'bundle',
        'price' => '1290',
        'components' => [
            [
                'name' => 'กระโปรง',
                'option_groups' => [skirtTypeGroup(), skirtSizeGroup()],
            ],
        ],
    ]);
}

test('cart rejects a child value that the chosen parent does not allow', function () {
    $product = skirtProduct();
    openBookingRound([$product]);

    try {
        app(CartService::class)->add($product, [
            'options' => [
                'skirt_type' => 'ทรงเอ',
                'skirt_size' => 'XL',
            ],
        ]);
        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['options'][0] ?? null)
            ->toBe('ตัวเลือกที่เลือกไม่เข้ากัน กรุณาเลือกใหม่');
    }
});

test('cart accepts an allowed parent and child pair', function () {
    $product = skirtProduct();
    openBookingRound([$product]);

    $item = app(CartService::class)->add($product, [
        'options' => [
            'skirt_type' => 'ทรงเอ',
            'skirt_size' => 'M',
        ],
    ]);

    expect($item['choices'])->toBe([
        ['label' => 'ประเภทกระโปรง', 'value' => 'ทรงเอ'],
        ['label' => 'ไซส์กระโปรง', 'value' => 'M'],
    ]);
});

test('product page shows a prompt before the parent is chosen and only allowed chips after', function () {
    $product = skirtProduct();
    openBookingRound([$product]);

    Livewire::test('pages::storefront.product-show', ['product' => $product])
        ->assertSee('เลือกตัวเลือกก่อนหน้าก่อน', false)
        ->assertDontSeeHtml("wire:click=\"selectOption('skirt_size', 'S')\"")
        ->call('selectOption', 'skirt_type', 'ทรงเอ')
        ->assertSeeHtml("wire:click=\"selectOption('skirt_size', 'S')\"")
        ->assertSeeHtml("wire:click=\"selectOption('skirt_size', 'M')\"")
        ->assertSeeHtml("wire:click=\"selectOption('skirt_size', 'L')\"")
        ->assertDontSeeHtml("wire:click=\"selectOption('skirt_size', 'XL')\"");
});

test('switching the parent clears a now-invalid child selection', function () {
    $product = skirtProduct();
    openBookingRound([$product]);

    Livewire::test('pages::storefront.product-show', ['product' => $product])
        ->call('selectOption', 'skirt_type', 'ทรงเอ')
        ->call('selectOption', 'skirt_size', 'S')
        ->assertSet('options.skirt_size', 'S')
        ->call('selectOption', 'skirt_type', 'จีบรอบ')
        ->assertSet('options.skirt_type', 'จีบรอบ')
        ->assertSet('options', ['skirt_type' => 'จีบรอบ']);
});

test('duplicate preserves option group dependencies', function () {
    $source = skirtProduct();

    $copy = app(CatalogService::class)->duplicate($source);
    $sizeGroup = $copy->optionGroups->firstWhere('key', 'skirt_size');

    expect($sizeGroup)->not->toBeNull()
        ->and($sizeGroup->depends_on_key)->toBe('skirt_type')
        ->and($sizeGroup->depends_on_values)->toBe([
            'ทรงเอ' => ['S', 'M', 'L'],
            'จีบรอบ' => ['M', 'L', 'XL'],
        ]);
});

test('renaming a parent group label keeps the dependency after save', function () {
    $staff = User::factory()->create();
    $product = skirtProduct();

    Livewire::actingAs($staff)
        ->test('pages::admin.product-edit', ['product' => $product])
        ->set('optionGroups.0.label', 'แบบกระโปรง')
        ->call('saveDraft')
        ->assertRedirect(route('admin.products'));

    $fresh = $product->fresh(['optionGroups.values']);
    $parent = $fresh->optionGroups->firstWhere('key', 'skirt_type');
    $child = $fresh->optionGroups->firstWhere('key', 'skirt_size');

    expect($parent?->label)->toBe('แบบกระโปรง')
        ->and($child?->depends_on_key)->toBe('skirt_type')
        ->and($child?->depends_on_values)->toBe([
            'ทรงเอ' => ['S', 'M', 'L'],
            'จีบรอบ' => ['M', 'L', 'XL'],
        ]);
});

test('cart rejects an invalid pair inside a bundle component', function () {
    $product = skirtBundleProduct();
    openBookingRound([$product]);
    $componentId = $product->components->first()->id;

    try {
        app(CartService::class)->add($product, [
            'components' => [
                $componentId => [
                    'skirt_type' => 'จีบรอบ',
                    'skirt_size' => 'S',
                ],
            ],
        ]);
        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['components'][0] ?? null)
            ->toBe('ตัวเลือกที่เลือกไม่เข้ากัน กรุณาเลือกใหม่');
    }
});

test('cart accepts an allowed pair inside a bundle component', function () {
    $product = skirtBundleProduct();
    openBookingRound([$product]);
    $componentId = $product->components->first()->id;

    $item = app(CartService::class)->add($product, [
        'components' => [
            $componentId => [
                'skirt_type' => 'จีบรอบ',
                'skirt_size' => 'XL',
            ],
        ],
    ]);

    expect($item['choices'])->toBe([
        ['label' => 'กระโปรง · ประเภทกระโปรง', 'value' => 'จีบรอบ'],
        ['label' => 'กระโปรง · ไซส์กระโปรง', 'value' => 'XL'],
    ]);
});
