<?php

namespace PnShop\Sales\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use PnShop\Catalog\Models\Product;
use PnShop\Sales\Models\Order;

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
            Stat::make('Pending orders', Order::query()->whereRelation('orderStatus', 'name', 'pending')->count()),
            Stat::make('Visible products', Product::query()->active()->count()),
        ];
    }
}
