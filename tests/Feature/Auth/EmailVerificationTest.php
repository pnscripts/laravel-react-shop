<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PnShop\Security\BotTrap;
use PnShop\Settings\Settings;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(Settings::class)->set('customers', ['require_email_verification' => true]);
    }

    public function test_unverified_customers_are_asked_to_verify_only_when_the_setting_is_on(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));

        app(Settings::class)->set('customers', ['require_email_verification' => false]);
        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->sendEmailVerificationNotification();
        Notification::assertNothingSent();
    }

    public function test_registering_sends_the_verification_email_when_required(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Ana',
            'email' => 'ana@example.test',
            'password' => 'a-long-password',
            'password_confirmation' => 'a-long-password',
            ...BotTrap::fields(now()->subMinute()),
        ]);

        Notification::assertSentTo(User::query()->where('email', 'ana@example.test')->sole(), VerifyEmail::class);
    }

    public function test_email_verification_screen_can_be_rendered()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified()
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash()
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
