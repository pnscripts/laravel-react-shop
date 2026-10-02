<?php

namespace PnShop\Sales;

use PnShop\Sales\Models\Order;
use PnShop\Settings\Settings;

/**
 * The human order number: the configured prefix and the order id, zero-padded
 * (ORD-000042). Assigned once when the order is created, so changing the settings
 * only affects new orders.
 */
final class OrderNumber
{
    public static function for(Order $order): string
    {
        $settings = app(Settings::class);

        $prefix = (string) $settings->get('sales.order_number_prefix');
        $digits = max(1, min(12, (int) $settings->get('sales.order_number_digits')));

        return $prefix.str_pad((string) $order->id, $digits, '0', STR_PAD_LEFT);
    }
}
