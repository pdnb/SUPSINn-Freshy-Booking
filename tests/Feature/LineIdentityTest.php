<?php

use App\Services\Line\LineIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.line.liff_id' => '2000000000-xxxxxxxx',
        'services.line.channel_id' => '2000000000',
    ]);
});

test('a valid line id token is verified and stored in the session', function () {
    Http::fake([
        'https://api.line.me/oauth2/v2.1/verify' => Http::response([
            'sub' => 'U0123456789abcdef0123456789abcdef',
            'aud' => '2000000000',
        ], 200),
    ]);

    $this->postJson(route('line.session'), [
        'id_token' => 'valid.jwt.token',
    ])
        ->assertSuccessful()
        ->assertJson([
            'ok' => true,
            'user_id' => 'U0123456789abcdef0123456789abcdef',
        ]);

    expect(session('line.user_id'))->toBe('U0123456789abcdef0123456789abcdef')
        ->and(app(LineIdentityService::class)->userId())->toBe('U0123456789abcdef0123456789abcdef');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.line.me/oauth2/v2.1/verify'
            && $request['id_token'] === 'valid.jwt.token'
            && $request['client_id'] === '2000000000';
    });
});

test('a line id token with a mismatched audience is rejected', function () {
    Http::fake([
        'https://api.line.me/oauth2/v2.1/verify' => Http::response([
            'sub' => 'U0123456789abcdef0123456789abcdef',
            'aud' => 'wrong-channel',
        ], 200),
    ]);

    $this->postJson(route('line.session'), [
        'id_token' => 'bad.aud.token',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id_token']);

    expect(session('line.user_id'))->toBeNull();
});

test('an invalid line id token from the verify api is rejected', function () {
    Http::fake([
        'https://api.line.me/oauth2/v2.1/verify' => Http::response([
            'error' => 'invalid_request',
            'error_description' => 'Invalid ID Token.',
        ], 400),
    ]);

    $this->postJson(route('line.session'), [
        'id_token' => 'expired.token',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id_token']);

    expect(session('line.user_id'))->toBeNull();
});

test('rememberFromIdToken requires a configured channel id', function () {
    config(['services.line.channel_id' => null]);

    expect(fn () => app(LineIdentityService::class)->rememberFromIdToken('any.token'))
        ->toThrow(ValidationException::class);

    expect(session('line.user_id'))->toBeNull();
});
