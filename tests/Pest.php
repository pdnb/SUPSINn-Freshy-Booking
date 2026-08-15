<?php

use App\Models\BookingRound;
use App\Models\Product;
use App\Services\Booking\BookingRoundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * @param  list<Product|int>  $products
 * @param  array<string, mixed>  $overrides
 */
function openBookingRound(array $products, array $overrides = []): BookingRound
{
    return app(BookingRoundService::class)->create([
        'name' => $overrides['name'] ?? 'รอบทดสอบ',
        'starts_at' => $overrides['starts_at'] ?? now()->subHour()->toDateTimeString(),
        'ends_at' => $overrides['ends_at'] ?? now()->addWeek()->toDateTimeString(),
        'is_enabled' => $overrides['is_enabled'] ?? true,
        'product_ids' => collect($products)->map(
            fn (mixed $product): int => $product instanceof Product ? $product->id : (int) $product
        )->all(),
    ]);
}
