<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Auth0UserProvisioner
{
    public function __construct(private Auth0Config $auth0) {}

    public function provision(string $sub, string $email, string $name): User
    {
        $sub = trim($sub);
        $email = trim($email);

        if ($sub === '' || $email === '') {
            throw ValidationException::withMessages([
                'email' => 'เข้าสู่ระบบด้วย Google ไม่สำเร็จ เพราะบัญชีไม่มีอีเมล',
            ]);
        }

        if (! $this->auth0->allowsEmail($email)) {
            throw ValidationException::withMessages([
                'email' => 'เข้าสู่ระบบด้วย Google ได้เฉพาะอีเมล @'.$this->auth0->allowedEmailDomain(),
            ]);
        }

        return DB::transaction(function () use ($sub, $email, $name): User {
            $bySub = User::query()->where('auth0_sub', $sub)->lockForUpdate()->first();

            if ($bySub instanceof User) {
                return $bySub;
            }

            $byEmail = User::query()
                ->whereRaw('lower(email) = ?', [Str::lower($email)])
                ->lockForUpdate()
                ->first();

            if ($byEmail instanceof User) {
                if (is_string($byEmail->auth0_sub) && $byEmail->auth0_sub !== '' && $byEmail->auth0_sub !== $sub) {
                    throw ValidationException::withMessages([
                        'email' => 'อีเมลนี้ผูกกับบัญชี Auth0 อื่นแล้ว',
                    ]);
                }

                $byEmail->forceFill(['auth0_sub' => $sub])->save();

                return $byEmail->refresh();
            }

            $displayName = trim($name);

            if ($displayName === '') {
                $displayName = Str::before($email, '@');
            }

            return User::query()->create([
                'name' => $displayName,
                'email' => $email,
                'password' => null,
                'auth0_sub' => $sub,
                'is_admin' => false,
            ]);
        });
    }
}
