<?php

/**
 * @return array<string, string>
 */
function adminPrototypeFiles(): array
{
    $root = base_path('docs/references/admin-mockup');

    return [
        'index.html' => $root.DIRECTORY_SEPARATOR.'index.html',
        'admin-overview.html' => $root.DIRECTORY_SEPARATOR.'admin-overview.html',
        'admin-orders.html' => $root.DIRECTORY_SEPARATOR.'admin-orders.html',
        'admin-order-detail.html' => $root.DIRECTORY_SEPARATOR.'admin-order-detail.html',
        'admin-products.html' => $root.DIRECTORY_SEPARATOR.'admin-products.html',
        'admin-product-edit.html' => $root.DIRECTORY_SEPARATOR.'admin-product-edit.html',
        'admin-pickup.html' => $root.DIRECTORY_SEPARATOR.'admin-pickup.html',
        'admin-production.html' => $root.DIRECTORY_SEPARATOR.'admin-production.html',
        'admin-fulfillment.html' => $root.DIRECTORY_SEPARATOR.'admin-fulfillment.html',
        'admin-rounds.html' => $root.DIRECTORY_SEPARATOR.'admin-rounds.html',
        'admin-settings.html' => $root.DIRECTORY_SEPARATOR.'admin-settings.html',
    ];
}

test('the admin prototype ships all eleven screens', function () {
    foreach (adminPrototypeFiles() as $path) {
        expect(is_file($path))->toBeTrue();
    }

    expect(is_file(base_path('docs/references/admin-mockup/admin-data.js')))->toBeTrue()
        ->and(is_file(base_path('docs/references/admin-mockup/assets/tokens.css')))->toBeTrue()
        ->and(is_file(base_path('docs/references/admin-mockup/assets/shell.css')))->toBeTrue()
        ->and(is_file(base_path('docs/references/admin-mockup/assets/chrome.js')))->toBeTrue();
});

test('the launcher links every admin prototype screen', function () {
    $launcher = file_get_contents(adminPrototypeFiles()['index.html']);

    expect($launcher)->not->toBeFalse();

    foreach (array_keys(adminPrototypeFiles()) as $name) {
        if ($name === 'index.html') {
            continue;
        }

        expect($launcher)->toContain($name);
    }
});

test('queue fulfillment and pickup share the same sample order ids', function () {
    $data = file_get_contents(base_path('docs/references/admin-mockup/admin-data.js'));
    $orders = file_get_contents(adminPrototypeFiles()['admin-orders.html']);
    $fulfillment = file_get_contents(adminPrototypeFiles()['admin-fulfillment.html']);
    $pickup = file_get_contents(adminPrototypeFiles()['admin-pickup.html']);

    expect($data)->not->toBeFalse();

    foreach (['FR-70021', 'FR-70024', 'FR-70026'] as $orderId) {
        expect($data)->toContain($orderId);
    }

    expect($orders)->toContain('admin-data.js')
        ->and($orders)->toContain('data-od-id="orders-table"')
        ->and($fulfillment)->toContain('admin-data.js')
        ->and($fulfillment)->toContain('data-od-id="fulfillment-table"')
        ->and($pickup)->toContain('admin-data.js')
        ->and($pickup)->toContain('data-od-id="pickup-search"');
});

test('shell screens expose required data-od-id hooks', function () {
    $overview = file_get_contents(adminPrototypeFiles()['admin-overview.html']);
    $detail = file_get_contents(adminPrototypeFiles()['admin-order-detail.html']);
    $products = file_get_contents(adminPrototypeFiles()['admin-products.html']);
    $settings = file_get_contents(adminPrototypeFiles()['admin-settings.html']);

    expect($overview)->toContain('data-od-id="shell"')
        ->and($overview)->toContain('data-od-id="cta-enter-queue"')
        ->and($overview)->toContain('[ตัวอย่าง]')
        ->and($detail)->toContain('data-od-id="cta-confirm"')
        ->and($products)->toContain('data-od-id="cta-add-product"')
        ->and($settings)->toContain('data-od-id="settings-tabs"');
});

test('the admin prototype is not the meridian storefront brief', function () {
    $root = base_path('docs/references/admin-mockup');
    $bundle = file_get_contents($root.DIRECTORY_SEPARATOR.'index.html')
        .file_get_contents($root.DIRECTORY_SEPARATOR.'admin-data.js')
        .file_get_contents($root.DIRECTORY_SEPARATOR.'admin-overview.html');

    expect($bundle)->not->toContain('Meridian')
        ->and($bundle)->toContain('ชุดเฟรชชี่')
        ->and($bundle)->toContain('FR-70021');
});
