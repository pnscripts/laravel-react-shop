<?php

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PnShop\Catalog\Models\Product;
use PnShop\Sales\Models\PaymentMethod;
use PnShop\Security\BotTrap;
use Tests\TestCase;

class InterfaceTranslationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_translations_for_the_current_language_are_shared(): void
    {
        $this->get('/bg/shop')->assertInertia(fn (Assert $page) => $page
            ->where('translations.Shop', 'Магазин')
            ->where('translations.:count in stock', 'Наличност: :count')
        );

        $this->get('/shop')->assertInertia(fn (Assert $page) => $page->where('translations', []));
    }

    public function test_every_bulgarian_translation_keeps_its_placeholders(): void
    {
        $translations = json_decode((string) file_get_contents(lang_path('bg.json')), true);

        foreach ($translations as $key => $value) {
            preg_match_all('/:[a-z_]+/', $key, $expected);
            preg_match_all('/:[a-z_]+/', $value, $actual);
            sort($expected[0]);
            sort($actual[0]);

            $this->assertSame($expected[0], $actual[0], "Placeholders differ for \"{$key}\".");
        }
    }

    public function test_server_messages_follow_the_language(): void
    {
        $product = Product::factory()->active()->create(['stock' => 2]);

        $this->from('/bg/shop')->post('/bg/cart', ['product_id' => $product->id, 'quantity' => 1])
            ->assertSessionHas('success', 'Добавено в количката.');

        $this->from('/bg/shop')->post('/bg/cart', ['product_id' => $product->id, 'quantity' => 5])
            ->assertSessionHasErrors(['quantity' => 'Налични са само 2 бр. от '.$product->title.'.']);

        $this->from('/bg/checkout')->post('/bg/checkout', BotTrap::fields(now()->subMinute()))
            ->assertSessionHasErrors(['email' => 'Полето имейл е задължително.']);
    }

    public function test_order_pages_show_localized_status_and_date(): void
    {
        $payment = PaymentMethod::factory()->create(['is_active' => true]);
        $product = Product::factory()->active()->create(['stock' => 2]);

        $this->post('/bg/cart', ['product_id' => $product->id, 'quantity' => 1]);
        $this->post('/bg/checkout', $this->checkoutData($payment->id, ['email' => 'ivan@example.com']))
            ->assertSessionHas('success', 'Благодарим Ви! Поръчката е приета.');

        $this->get('/bg/orders/1')->assertInertia(fn (Assert $page) => $page
            ->where('order.status', 'Очаква обработка')
            ->where('order.created_at', fn (string $date) => str_contains($date, now()->locale('bg')->isoFormat('MMMM')))
        );
    }
}
