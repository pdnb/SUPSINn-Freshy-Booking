<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\Auth0Config;
use App\Services\Auth\Auth0UserProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class Auth0Controller extends Controller
{
    public function redirect(Auth0Config $auth0): RedirectResponse
    {
        if (! $auth0->isConfigured()) {
            return redirect()->route('login');
        }

        return Socialite::driver('auth0')->redirect();
    }

    public function callback(Request $request, Auth0Config $auth0, Auth0UserProvisioner $provisioner): RedirectResponse
    {
        if (! $auth0->isConfigured()) {
            return redirect()->route('login');
        }

        try {
            $oauthUser = Socialite::driver('auth0')->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')->withErrors([
                'email' => 'เข้าสู่ระบบด้วย Google ไม่สำเร็จ',
            ]);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors([
                'email' => 'เข้าสู่ระบบด้วย Google ไม่สำเร็จ',
            ]);
        }

        $sub = (string) $oauthUser->getId();
        $email = $oauthUser->getEmail();

        if ($sub === '' || ! is_string($email) || $email === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'เข้าสู่ระบบด้วย Google ไม่สำเร็จ เพราะบัญชีไม่มีอีเมล',
            ]);
        }

        try {
            $user = $provisioner->provision($sub, $email, (string) ($oauthUser->getName() ?? ''));
        } catch (ValidationException $e) {
            return redirect()->route('login')->withErrors($e->errors());
        }

        Auth::login($user);
        $request->session()->regenerate();

        if (! $user instanceof User || ! $user->canAccessAdmin()) {
            return redirect()->route('admin.pending');
        }

        return redirect()->intended(route('admin.dashboard'));
    }
}
