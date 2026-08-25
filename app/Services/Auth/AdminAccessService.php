<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class AdminAccessService
{
    public function grant(User $user): void
    {
        $user->forceFill(['is_admin' => true])->save();
    }

    public function revoke(User $user): void
    {
        if ($user->is_admin && User::query()->where('is_admin', true)->count() <= 1) {
            throw ValidationException::withMessages([
                'users' => 'ต้องเหลือแอดมินอย่างน้อยหนึ่งคน',
            ]);
        }

        $user->forceFill(['is_admin' => false])->save();
    }
}
