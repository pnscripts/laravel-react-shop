<?php

namespace PnShop\Sales\Filament\Resources\Orders;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use PnShop\Payment\Filament\RelationManagers\PaymentsRelationManager;
use PnShop\Payment\Filament\RelationManagers\RefundsRelationManager;
use PnShop\Sales\Filament\Resources\Orders\Pages\ListOrders;
use PnShop\Sales\Filament\Resources\Orders\Pages\ViewOrder;
use PnShop\Sales\Filament\Resources\Orders\RelationManagers\HistoryRelationManager;
use PnShop\Sales\Filament\Resources\Orders\Schemas\OrderInfolist;
use PnShop\Sales\Filament\Resources\Orders\Tables\OrdersTable;
use PnShop\Sales\Models\Order;
use PnShop\Shipping\Filament\RelationManagers\ShipmentsRelationManager;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'number';

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [ShipmentsRelationManager::class, PaymentsRelationManager::class, RefundsRelationManager::class, HistoryRelationManager::class];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
