<?php

namespace Tests\Feature\Security;

use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PnShop\Catalog\Models\Product;
use PnShop\Security\BotTrap;
use PnShop\Security\Captcha\CaptchaVerifier;
use Tests\TestCase;

class BotTrapTest extends TestCase
{
    use RefreshDatabase;

    private PaymentMethod $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = PaymentMethod::factory()->create(['is_active' => true]);
        $product = Product::factory()->active()->create(['stock' => 5]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function automatedSubmissions(): array
    {
        return [
            'honeypot filled' => [['contact_website' => 'http://spam.example']],
            'no time token' => [['form_started' => null]],
            'forged time token' => [['form_started' => '1700000000']],
        ];
    }

    #[DataProvider('automatedSubmissions')]
    public function test_automated_checkouts_are_refused(array $overrides): void
    {
        $data = array_filter($this->checkoutData($this->payment->id, $overrides), fn ($value) => $value !== null);

        $this->post(route('checkout.store'), $data)->assertSessionHasErrors('form');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_forms_submitted_faster_than_a_person_could_are_refused(): void
    {
        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id, BotTrap::fields(now())))
            ->assertSessionHasErrors('form');

        $this->travel(3)->seconds();

        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id, BotTrap::fields(now()->subSeconds(3))))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, Order::query()->count());
    }

    public function test_a_bound_captcha_verifier_is_consulted(): void
    {
        $this->app->bind(CaptchaVerifier::class, fn () => new class implements CaptchaVerifier
        {
            public function verify(Request $request): bool
            {
                return $request->input('captcha') === 'ok';
            }
        });

        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id))->assertSessionHasErrors('form');
        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id, ['captcha' => 'ok']))->assertSessionHasNoErrors();
    }

    public function test_registration_is_protected(): void
    {
        $this->post('/register', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'password' => 'a-long-password',
            'password_confirmation' => 'a-long-password',
        ])->assertSessionHasErrors('form');

        $this->assertGuest();
    }

    public function test_pages_issue_the_trap_fields(): void
    {
        $this->get(route('checkout.create'))->assertInertia(fn ($page) => $page
            ->where('botTrap.contact_website', '')
            ->whereType('botTrap.form_started', 'string')
        );
        $this->post('/logout');
        $this->get('/register')->assertInertia(fn ($page) => $page->has('botTrap.form_started'));
    }
}
