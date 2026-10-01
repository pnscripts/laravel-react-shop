<?php

namespace PnShop\Sales\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use PnShop\Catalog\Models\Product;
use PnShop\Sales\Models\Order;
use PnShop\Sales\States\FulfillmentStatus;
use PnShop\Sales\States\OrderStatus;

class StoreStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth('admin')->user()?->can('sales.orders.view') ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Orders today', Order::query()->whereDate('created_at', today())->count()),
            Stat::make('Pending orders', Order::query()->where('status', OrderStatus::Pending)->count()),
            Stat::make('Awaiting shipment', Order::query()
                ->whereIn('status', [OrderStatus::Pending, OrderStatus::Processing])
                ->whereIn('fulfillment_status', [FulfillmentStatus::Unfulfilled, FulfillmentStatus::PartiallyFulfilled])
                ->count()),
            Stat::make('Visible products', Product::query()->active()->count()),
        ];
    }
}
