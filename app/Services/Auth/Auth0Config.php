<?php

namespace App\Services\Auth;

use Illuminate\Support\Str;

class Auth0Config
{
    public function isConfigured(): bool
    {
        return filled(config('services.auth0.domain'))
            && filled(config('services.auth0.client_id'))
            && filled(config('services.auth0.client_secret'));
    }

    public function allowedEmailDomain(): string
    {
        return Str::lower((string) config('services.auth0.allowed_email_domain', 'sru.ac.th'));
    }

    public function allowsEmail(string $email): bool
    {
        $email = trim($email);

        if ($email === '' || ! str_contains($email, '@')) {
            return false;
        }

        $domain = Str::lower(Str::afterLast($email, '@'));

        return $domain === $this->allowedEmailDomain();
    }
}
