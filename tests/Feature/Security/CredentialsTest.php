<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Settings\Settings;
use Tests\TestCase;

/**
 * A new password ends every other way in: API tokens, other browser sessions, "remember me".
 */
class CredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_the_password_revokes_api_tokens_and_other_sessions(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password-1')]);
        $user->createToken('phone');

        // Another browser, signed in with the old password.
        $this->post('/login', ['email' => $user->email, 'password' => 'old-password-1']);
        $this->get('/dashboard')->assertOk();

        $user->forceFill(['password' => Hash::make('new-password-1')])->save();

        $this->assertSame(0, $user->tokens()->count());

        // The next request from that browser (a fresh request loads the user again).
        $this->app['auth']->forgetGuards();
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_the_browser_that_changes_the_password_stays_signed_in(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password-1')]);
        $remember = $user->remember_token;
        $this->post('/login', ['email' => $user->email, 'password' => 'old-password-1']);

        $this->put(route('password.update'), [
            'current_password' => 'old-password-1',
            'password' => 'new-password-12',
            'password_confirmation' => 'new-password-12',
        ])->assertSessionHasNoErrors();

        $this->app['auth']->forgetGuards();
        $this->get('/dashboard')->assertOk();
        $this->assertNotSame($remember, $user->fresh()->remember_token);
    }

    public function test_a_password_reset_and_account_deletion_revoke_api_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('phone');

        $this->post('/reset-password', [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => 'reset-password-1',
            'password_confirmation' => 'reset-password-1',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, $user->tokens()->count());

        $user->createToken('tablet');
        $user->delete();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_store_api_orders_need_a_verified_email_only_when_required(): void
    {
        $user = User::factory()->unverified()->create();
        $token = $user->createToken('app', ['store'])->plainTextToken;
        app(Settings::class)->set('customers', ['require_email_verification' => true]);

        $this->withToken($token)->postJson('/api/store/v1/checkout', collect($this->checkoutData(PaymentMethod::factory()->create(['gateway' => 'bank_transfer'])->id))->except(['website', 'form_started_at'])->all())
            ->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified');
    }
}
