<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_active_sessions(): void
    {
        $user = User::factory()->create();

        DB::table('sessions')->insert([
            'id' => 'other-session-id',
            'user_id' => $user->id,
            'ip_address' => '10.0.0.5',
            'user_agent' => 'Some Other Browser',
            'payload' => 'x',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($user)->get(route('security.index'));

        $response->assertOk();
        $response->assertSee('Some Other Browser');
        $response->assertSee('10.0.0.5');
    }

    public function test_user_can_force_logout_a_specific_other_session(): void
    {
        $user = User::factory()->create(['password' => 'CorrectHorse!42']);
        $this->actingAs($user)->post(route('password.confirm.store'), ['password' => 'CorrectHorse!42']);

        DB::table('sessions')->insert([
            'id' => 'other-session-id',
            'user_id' => $user->id,
            'ip_address' => '10.0.0.5',
            'user_agent' => 'Some Other Browser',
            'payload' => 'x',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('security.sessions.destroy', 'other-session-id'));

        $response->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session-id']);
    }

    public function test_user_cannot_force_logout_another_users_session(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create(['password' => 'CorrectHorse!42']);
        $this->actingAs($attacker)->post(route('password.confirm.store'), ['password' => 'CorrectHorse!42']);

        DB::table('sessions')->insert([
            'id' => 'victim-session-id',
            'user_id' => $owner->id,
            'ip_address' => '10.0.0.5',
            'user_agent' => 'Victim Browser',
            'payload' => 'x',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($attacker)->delete(route('security.sessions.destroy', 'victim-session-id'));

        // The row must survive — an attacker scoping the query to their own
        // user_id must not be able to touch someone else's session row.
        $this->assertDatabaseHas('sessions', ['id' => 'victim-session-id']);
    }

    public function test_logout_other_devices_requires_the_current_password(): void
    {
        $user = User::factory()->create(['password' => 'CorrectHorse!42']);

        DB::table('sessions')->insert([
            'id' => 'other-session-id',
            'user_id' => $user->id,
            'ip_address' => '10.0.0.5',
            'user_agent' => 'Some Other Browser',
            'payload' => 'x',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($user)->post(route('security.sessions.logout-other'), [
            'password' => 'the-wrong-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseHas('sessions', ['id' => 'other-session-id']);
    }

    public function test_logout_other_devices_clears_other_sessions(): void
    {
        $user = User::factory()->create(['password' => 'CorrectHorse!42']);

        DB::table('sessions')->insert([
            'id' => 'other-session-id',
            'user_id' => $user->id,
            'ip_address' => '10.0.0.5',
            'user_agent' => 'Some Other Browser',
            'payload' => 'x',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($user)->post(route('security.sessions.logout-other'), [
            'password' => 'CorrectHorse!42',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session-id']);
    }
}
