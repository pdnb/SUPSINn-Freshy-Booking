<?php

use App\Models\User;
use App\Services\Auth\Auth0UserProvisioner;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as OauthUser;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function resetAuth0Config(): void
{
    config([
        'services.auth0.domain' => null,
        'services.auth0.client_id' => null,
        'services.auth0.client_secret' => null,
        'services.auth0.redirect' => '/admin/auth/auth0/callback',
        'services.auth0.allowed_email_domain' => 'sru.ac.th',
    ]);
}

function configureAuth0(): void
{
    config([
        'services.auth0.domain' => 'example.auth0.com',
        'services.auth0.client_id' => 'test-client',
        'services.auth0.client_secret' => 'test-secret',
        'services.auth0.redirect' => '/admin/auth/auth0/callback',
        'services.auth0.allowed_email_domain' => 'sru.ac.th',
    ]);
}

beforeEach(function () {
    resetAuth0Config();
});

test('the storefront stays public', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('SRU Shop', false);
});

test('password login still reaches the admin dashboard', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
    ]);

    Livewire::test('pages::admin.login')
        ->set('email', 'admin@example.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);

    $this->get('/admin')
        ->assertOk()
        ->assertSee('ภาพรวม', false)
        ->assertSee('ผู้ใช้', false);
});

test('staff can log out of the app only', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('the login page hides Auth0 when credentials are missing', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertDontSee('เข้าสู่ระบบด้วย Google', false);
});

test('the login page shows Auth0 when credentials are set', function () {
    configureAuth0();

    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('เข้าสู่ระบบด้วย Google', false);
});

test('pending users are sent to the waiting page instead of the console', function () {
    $user = User::factory()->pending()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertRedirect(route('admin.pending'));

    $this->actingAs($user)
        ->get(route('admin.pending'))
        ->assertOk()
        ->assertSee('รออนุญาต', false)
        ->assertSee($user->email, false)
        ->assertDontSee('ภาพรวม', false)
        ->assertDontSee('ผู้ใช้', false);

    $this->actingAs($user)
        ->get(route('admin.users'))
        ->assertRedirect(route('admin.pending'));
});

test('a revoked password user lands on the waiting page', function () {
    User::factory()->pending()->create([
        'email' => 'staff@example.com',
    ]);

    Livewire::test('pages::admin.login')
        ->set('email', 'staff@example.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(route('admin.pending'));

    $this->get('/admin')->assertRedirect(route('admin.pending'));
});

test('Auth0 redirect is skipped when credentials are missing', function () {
    $this->get(route('admin.auth0.redirect'))
        ->assertRedirect(route('login'));
});

test('Auth0 redirect goes to the provider when configured', function () {
    configureAuth0();
    Socialite::fake('auth0');

    $this->get(route('admin.auth0.redirect'))
        ->assertRedirect('https://socialite.fake/auth0/authorize');
});

test('Auth0 links an existing user by email and enters the console', function () {
    configureAuth0();

    $user = User::factory()->create([
        'email' => 'admin@sru.ac.th',
    ]);

    Socialite::fake('auth0', OauthUser::fake([
        'id' => 'auth0|existing',
        'name' => 'Admin',
        'email' => 'admin@sru.ac.th',
    ]));

    $this->get(route('admin.auth0.callback'))
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user->fresh());

    expect($user->fresh()->auth0_sub)->toBe('auth0|existing');
});

test('Auth0 matches an existing user email case-insensitively', function () {
    configureAuth0();

    $user = User::factory()->create([
        'email' => 'Admin@SRU.AC.TH',
    ]);

    Socialite::fake('auth0', OauthUser::fake([
        'id' => 'auth0|case',
        'name' => 'Admin',
        'email' => 'admin@sru.ac.th',
    ]));

    $this->get(route('admin.auth0.callback'))
        ->assertRedirect(route('admin.dashboard'));

    expect($user->fresh()->auth0_sub)->toBe('auth0|case');
});

test('Auth0 creates a pending user for a new email', function () {
    configureAuth0();

    Socialite::fake('auth0', OauthUser::fake([
        'id' => 'auth0|new',
        'name' => 'New Staff',
        'email' => 'new@sru.ac.th',
    ]));

    $this->get(route('admin.auth0.callback'))
        ->assertRedirect(route('admin.pending'));

    $user = User::query()->where('email', 'new@sru.ac.th')->first();

    expect($user)->not->toBeNull()
        ->and($user->auth0_sub)->toBe('auth0|new')
        ->and($user->is_admin)->toBeFalse()
        ->and($user->password)->toBeNull();

    $this->assertAuthenticatedAs($user);
});

test('Auth0 rejects emails outside @sru.ac.th', function (string $email) {
    configureAuth0();

    Socialite::fake('auth0', OauthUser::fake([
        'id' => 'auth0|outside',
        'name' => 'Outside',
        'email' => $email,
    ]));

    $this->get(route('admin.auth0.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => 'เข้าสู่ระบบด้วย Google ได้เฉพาะอีเมล @sru.ac.th',
        ]);

    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
})->with([
    'gmail.com' => 'staff@gmail.com',
    'student subdomain' => 'name@student.sru.ac.th',
]);

test('Auth0 without an email does not create a user', function () {
    configureAuth0();

    Socialite::fake('auth0', OauthUser::fake([
        'id' => 'auth0|no-email',
        'name' => 'No Email',
        'email' => '',
    ]));

    $this->get(route('admin.auth0.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
});

test('Auth0 does not steal an email that already has a different sub', function () {
    configureAuth0();

    User::factory()->create([
        'email' => 'taken@sru.ac.th',
        'auth0_sub' => 'auth0|old',
    ]);

    Socialite::fake('auth0', OauthUser::fake([
        'id' => 'auth0|new',
        'name' => 'Other',
        'email' => 'taken@sru.ac.th',
    ]));

    $this->get(route('admin.auth0.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(User::query()->where('auth0_sub', 'auth0|new')->exists())->toBeFalse();
});

test('admins can grant and revoke console access', function () {
    $admin = User::factory()->create();
    $pending = User::factory()->pending()->create([
        'email' => 'wait@example.com',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->assertSee('wait@example.com', false)
        ->assertSee('รออนุญาต', false)
        ->call('toggleAccess', $pending->id)
        ->assertHasNoErrors();

    expect($pending->fresh()->is_admin)->toBeTrue();

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->call('toggleAccess', $pending->id)
        ->assertHasNoErrors();

    expect($pending->fresh()->is_admin)->toBeFalse();

    $this->actingAs($pending->fresh())
        ->get('/admin')
        ->assertRedirect(route('admin.pending'));
});

test('the last admin cannot be revoked', function () {
    $admin = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->call('toggleAccess', $admin->id)
        ->assertHasErrors('users');

    expect($admin->fresh()->is_admin)->toBeTrue();
});

test('the seeded admin is granted console access', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->where('email', config('booking.admin_email'))->first();

    expect($admin)->not->toBeNull()
        ->and($admin->is_admin)->toBeTrue();
});

test('the provisioner jit-creates a pending user', function () {
    $user = app(Auth0UserProvisioner::class)->provision(
        'auth0|svc',
        'svc@sru.ac.th',
        'Service User',
    );

    expect($user->email)->toBe('svc@sru.ac.th')
        ->and($user->auth0_sub)->toBe('auth0|svc')
        ->and($user->is_admin)->toBeFalse()
        ->and($user->password)->toBeNull();
});

test('the provisioner rejects emails outside the allowed domain', function () {
    expect(fn () => app(Auth0UserProvisioner::class)->provision(
        'auth0|outside',
        'staff@gmail.com',
        'Outside',
    ))->toThrow(ValidationException::class);

    expect(User::query()->count())->toBe(0);
});
