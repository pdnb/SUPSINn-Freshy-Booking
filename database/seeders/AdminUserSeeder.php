<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => config('booking.admin_email')],
            [
                'name' => config('booking.admin_name'),
                'password' => config('booking.admin_password'),
                'is_admin' => true,
            ],
        );
    }
}
