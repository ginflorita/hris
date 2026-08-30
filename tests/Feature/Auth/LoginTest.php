<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'CorrectHorse!42']);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'CorrectHorse!42',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');

        $this->assertDatabaseHas('login_logs', [
            'user_id' => $user->id,
            'event' => 'login',
        ]);
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create(['password' => 'CorrectHorse!42']);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $this->assertSame(
            'These credentials do not match our records.',
            session('errors')->first('email')
        );

        $this->assertDatabaseHas('login_logs', [
            'email' => $user->email,
            'event' => 'failed_login',
        ]);
    }

    public function test_disabled_accounts_cannot_authenticate_and_get_the_same_generic_error(): void
    {
        $user = User::factory()->create([
            'password' => 'CorrectHorse!42',
            'disabled_at' => now(),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'CorrectHorse!42',
        ]);

        $this->assertGuest();
        $this->assertSame(
            'These credentials do not match our records.',
            session('errors')->first('email')
        );
    }

    public function test_login_is_rate_limited_after_too_many_attempts(): void
    {
        $email = 'throttle-target@example.test';
        RateLimiter::clear(mb_strtolower($email).'|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), [
                'email' => $email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('login.store'), [
            'email' => $email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many login attempts',
            session('errors')->first('email')
        );
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect('/');

        $this->assertDatabaseHas('login_logs', [
            'user_id' => $user->id,
            'event' => 'logout',
        ]);
    }
}
