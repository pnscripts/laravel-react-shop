<?php

namespace PnShop\Sales\Filament\Widgets;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use PnShop\Sales\Filament\Resources\Orders\OrderResource;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth('admin')->user()?->can('sales.orders.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->with(['orderStatus', 'paymentMethod'])->latest()->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('id')->label('#'),
                TextColumn::make('name')->label('Customer'),
                TextColumn::make('orderStatus.name')->label('Status')->badge(),
                TextColumn::make('paymentMethod.name')->label('Payment'),
                TextColumn::make('created_at')->label('Placed')->since(),
            ])
            ->recordActions([
                Action::make('view')->url(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
