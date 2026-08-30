<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\RecoveryCode;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function confirmPassword(User $user): void
    {
        $this->actingAs($user)->post(route('password.confirm.store'), [
            'password' => 'CorrectHorse!42',
        ])->assertSessionHasNoErrors();
    }

    public function test_two_factor_authentication_can_be_enabled_and_confirmed(): void
    {
        $user = User::factory()->create(['password' => 'CorrectHorse!42']);
        $this->confirmPassword($user);

        $this->actingAs($user)->post(route('two-factor.enable'))->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        $validCode = app(Google2FA::class)->getCurrentOtp($secret);

        $this->actingAs($user)->post(route('two-factor.confirm'), ['code' => $validCode])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_login_challenges_for_two_factor_code_when_enabled(): void
    {
        $user = $this->userWithConfirmedTwoFactor();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'CorrectHorse!42',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.login'));
        $this->assertEquals($user->id, session('login.id'));
    }

    public function test_login_completes_with_a_valid_two_factor_code(): void
    {
        $user = $this->userWithConfirmedTwoFactor();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'CorrectHorse!42',
        ]);

        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        $validCode = app(Google2FA::class)->getCurrentOtp($secret);

        $response = $this->post(route('two-factor.login.store'), ['code' => $validCode]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');
    }

    public function test_login_completes_with_a_valid_recovery_code(): void
    {
        $user = $this->userWithConfirmedTwoFactor();
        $recoveryCode = $user->recoveryCodes()[0];

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'CorrectHorse!42',
        ]);

        $response = $this->post(route('two-factor.login.store'), ['recovery_code' => $recoveryCode]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');

        // Recovery codes are single-use.
        $this->assertNotContains($recoveryCode, $user->fresh()->recoveryCodes());
    }

    public function test_enabling_two_factor_requires_a_confirmed_password(): void
    {
        $user = User::factory()->create(['password' => 'CorrectHorse!42']);

        $response = $this->actingAs($user)->post(route('two-factor.enable'));

        $response->assertRedirect(route('password.confirm'));
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    /**
     * Builds a user with 2FA already confirmed directly via the model,
     * matching Laravel\Fortify\Actions\EnableTwoFactorAuthentication — not
     * through HTTP, since actingAs() in a setup helper would leave the test
     * client authenticated for the "fresh login" assertions that follow.
     */
    private function userWithConfirmedTwoFactor(): User
    {
        $user = User::factory()->create(['password' => 'CorrectHorse!42']);

        $secret = app(Google2FA::class)->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode([
                RecoveryCode::generate(),
                RecoveryCode::generate(),
            ])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user->fresh();
    }
}
