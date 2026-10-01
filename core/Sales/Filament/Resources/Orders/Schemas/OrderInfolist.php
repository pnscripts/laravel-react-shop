<?php

namespace PnShop\Sales\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use App\Models\OrderItem;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Items')
                    ->columnSpan(2)
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('product_title')->label('Product')->placeholder('Product'),
                                TextEntry::make('product_sku')->label('SKU')->placeholder('—'),
                                TextEntry::make('quantity'),
                                TextEntry::make('line_total')
                                    ->label('Total')
                                    ->state(fn (OrderItem $record) => $record->lineTotal()->formatToLocale(app()->getLocale())),
                            ]),
                        TextEntry::make('total')
                            ->state(fn (Order $record) => $record->itemsTotal()->formatToLocale(app()->getLocale()))
                            ->weight('bold'),
                    ]),
                Section::make('Order')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('orderStatus.name')->label('Status')->badge(),
                        TextEntry::make('paymentMethod.name')->label('Payment'),
                        TextEntry::make('created_at')->label('Placed')->dateTime(),
                        TextEntry::make('name')->label('Customer'),
                        TextEntry::make('email')->copyable(),
                        TextEntry::make('phone'),
                        TextEntry::make('address'),
                    ]),
            ]);
    }
}
