<?php

namespace PnShop\Sales\Console;

use Illuminate\Console\Command;
use PnShop\Sales\Exceptions\OrderException;
use PnShop\Sales\Models\Order;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\OrderStatus;
use PnShop\Sales\States\PaymentStatus;
use PnShop\Settings\Settings;

/**
 * Cancels orders that were never paid within the time set in Settings → Orders, so
 * abandoned or fake orders do not keep stock reserved. Cancelling goes through the order
 * workflow: the stock is released, open payments are cancelled and the customer is told.
 */
class CancelUnpaidOrdersCommand extends Command
{
    protected $signature = 'pnshop:orders:cancel-unpaid {--dry-run : Only list the orders}';

    protected $description = 'Cancel pending orders that stayed unpaid longer than the configured time';

    public function handle(Settings $settings, OrderWorkflow $workflow): int
    {
        $hours = (int) $settings->get('sales.cancel_unpaid_after_hours');

        if ($hours <= 0) {
            $this->line('Automatic cancellation is off (Settings → Orders).');

            return self::SUCCESS;
        }

        $orders = Order::query()
            ->where('status', OrderStatus::Pending)
            ->whereIn('payment_status', [PaymentStatus::Unpaid, PaymentStatus::Failed])
            ->where('created_at', '<', now()->subHours($hours))
            ->orderBy('id')
            ->lazyById(100);

        $count = 0;

        foreach ($orders as $order) {
            if ($this->option('dry-run')) {
                $this->line("Would cancel {$order->number}");
                $count++;

                continue;
            }

            try {
                $workflow->transition($order, OrderStatus::Cancelled, note: __('Cancelled automatically: not paid within :hours hours.', ['hours' => $hours]));
                $count++;
            } catch (OrderException $e) {
                $this->warn("{$order->number}: {$e->getMessage()}");
            }
        }

        $this->info(($this->option('dry-run') ? 'Would cancel' : 'Cancelled')." {$count} unpaid order(s).");

        return self::SUCCESS;
    }
}
