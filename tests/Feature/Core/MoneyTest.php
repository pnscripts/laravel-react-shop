<?php

namespace Tests\Feature\Core;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PnShop\Catalog\Models\Product;
use PnShop\Money\MoneyPresenter;
use PnShop\Sales\Models\OrderItem;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_prices_are_stored_as_minor_units(): void
    {
        $product = Product::factory()->create(['price' => '12.50', 'sale_price' => 9.99]);

        $this->assertSame(1250, DB::table('product_variants')->where('product_id', $product->id)->value('price'));
        $this->assertSame(999, DB::table('product_variants')->where('product_id', $product->id)->value('sale_price'));
        $this->assertTrue($product->fresh()->price->isEqualTo(Money::of('12.50', 'USD')));
    }

    public function test_amounts_are_rounded_half_up_to_the_currency_precision(): void
    {
        $product = Product::factory()->create(['price' => '10.005']);

        $this->assertSame('10.01', (string) $product->fresh()->price->getAmount());
    }

    public function test_money_in_another_currency_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Product::factory()->create(['price' => Money::of(10, 'EUR')]);
    }

    public function test_order_lines_use_their_own_currency(): void
    {
        $item = new OrderItem(['currency' => 'EUR', 'price' => '5.00', 'quantity' => 3]);

        $this->assertSame('EUR', $item->price->getCurrency()->getCurrencyCode());
        $this->assertSame('15.00', (string) $item->lineTotal()->getAmount());
    }

    public function test_serialized_models_carry_decimal_strings(): void
    {
        $product = Product::factory()->create(['price' => '12.50', 'sale_price' => null]);

        $variant = $product->fresh()->defaultVariant()->toArray();

        $this->assertSame('12.50', $variant['price']);
        $this->assertNull($variant['sale_price']);
    }

    public function test_money_is_formatted_for_the_visitors_language(): void
    {
        $money = Money::of('1234.50', 'USD');

        $this->assertSame('$1,234.50', MoneyPresenter::present($money, 'en')['formatted']);
        $this->assertSame("1234,50\u{00A0}щ.д.", MoneyPresenter::present($money, 'bg')['formatted']);

        Product::factory()->active()->create(['price' => '1234.50', 'sale_price' => null]);

        $this->get('/bg/shop')->assertInertia(fn ($page) => $page
            ->where('products.data.0.price.formatted', "1234,50\u{00A0}щ.д.")
            ->where('products.data.0.price.minor', 123450)
        );
    }
}
