<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('allows login with valid credentials', function () {
    $user = User::factory()->create([
        'role' => 'organizer',
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $response = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password')
        ->call('login');

    $response->assertHasNoErrors();
    expect(auth()->check())->toBeTrue();
});

it('rejects login with inactive account', function () {
    $user = User::factory()->create([
        'role' => 'organizer',
        'is_active' => false,
    ]);

    // Login first to check middleware
    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    // After login, middleware should log them out
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

it('redirects to change-password when must_change_password is true', function () {
    $user = User::factory()->create([
        'role' => 'organizer',
        'is_active' => true,
        'must_change_password' => true,
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertRedirect('/password/change');
});

it('allows access to change-password route without redirect loop', function () {
    $user = User::factory()->create([
        'role' => 'organizer',
        'is_active' => true,
        'must_change_password' => true,
    ]);

    $this->actingAs($user);

    // Should NOT redirect to change-password again (no redirect loop)
    $response = $this->get('/password/change');
    // The middleware check.active passes, force.password.change is not applied to this route
    // So we should get a response that is not a redirect to the same page
    expect($response->getStatusCode())->not->toBe(302);
});
