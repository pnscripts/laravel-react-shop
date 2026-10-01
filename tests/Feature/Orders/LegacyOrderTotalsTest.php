<?php

namespace Tests\Feature\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PnShop\Sales\Models\Order;
use Tests\TestCase;

class LegacyOrderTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfilled_totals_follow_the_charged_line_prices(): void
    {
        // A pre-upgrade line whose stored sale price is above the regular price was charged the sale price.
        $order = Order::factory()->create(['currency' => 'USD', 'subtotal' => '30.00', 'total' => '30.00', 'totals' => []]);
        $order->items()->create(['product_title' => 'Old', 'quantity' => 2, 'currency' => 'USD', 'price' => '15.00', 'sale_price' => '20.00']);
        $payment = $order->payments()->create(['gateway' => 'bank_transfer', 'currency' => 'USD', 'amount' => '30.00']);
        $payment->transactions()->create(['type' => 'import', 'outcome' => 'pending', 'currency' => 'USD', 'amount' => '30.00']);

        $untouched = Order::factory()->create(['currency' => 'USD', 'subtotal' => '10.00', 'total' => '15.00', 'totals' => [['code' => 'shipping', 'label' => 'Courier', 'amount' => 500, 'included' => false]]]);
        $untouched->items()->create(['product_title' => 'New', 'quantity' => 1, 'currency' => 'USD', 'price' => '10.00']);

        (require base_path('core/Sales/database/migrations/2026_10_05_150000_correct_backfilled_order_totals.php'))->up();

        $order->refresh();
        $this->assertSame('40.00', (string) $order->total->getAmount());
        $this->assertSame($order->itemsTotal()->getMinorAmount()->toInt(), $order->subtotal->getMinorAmount()->toInt());
        $this->assertSame(4000, (int) DB::table('payments')->where('id', $payment->id)->value('amount'));
        $this->assertSame('15.00', (string) $untouched->fresh()->total->getAmount());
    }
}
