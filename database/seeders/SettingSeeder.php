<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'storefront_logo_path'],
            ['value' => config('booking.default_storefront_logo')],
        );

        Setting::query()->updateOrCreate(
            ['key' => 'deposit_amount'],
            ['value' => '0.00'],
        );

        Setting::query()->updateOrCreate(
            ['key' => 'academic_year'],
            ['value' => '2569'],
        );
    }
}
