<?php

namespace PnShop\Sales\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\OrderItem;

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
                                TextEntry::make('product_title')->label('Product')->placeholder('Product')
                                    ->state(fn (OrderItem $record) => $record->variant_label ? "{$record->product_title} ({$record->variant_label})" : $record->product_title),
                                TextEntry::make('product_sku')->label('SKU')->placeholder('—'),
                                TextEntry::make('quantity'),
                                TextEntry::make('line_total')
                                    ->label('Total')
                                    ->state(fn (OrderItem $record) => $record->lineTotal()->formatToLocale(app()->getLocale())),
                            ]),
                        TextEntry::make('subtotal')
                            ->state(fn (Order $record) => ($record->subtotal ?? $record->itemsTotal())->formatToLocale(app()->getLocale())),
                        TextEntry::make('totals_breakdown')
                            ->label('Adjustments')
                            ->state(fn (Order $record) => array_map(
                                fn (array $line) => $line['label'].': '.$line['amount']['formatted'].($line['included'] ? ' (included)' : ''),
                                $record->presentTotals()['lines'],
                            ))
                            ->listWithLineBreaks()
                            ->visible(fn (Order $record) => ($record->totals ?? []) !== []),
                        TextEntry::make('total')
                            ->state(fn (Order $record) => $record->grandTotal()->formatToLocale(app()->getLocale()))
                            ->weight('bold'),
                    ]),
                Section::make('Order')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('number')->label('Order number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('payment_status')->label('Payment status')->badge(),
                        TextEntry::make('fulfillment_status')->label('Fulfillment')->badge(),
                        TextEntry::make('paymentMethod.name')->label('Payment'),
                        TextEntry::make('shipping_method_name')->label('Delivery')->placeholder('—'),
                        TextEntry::make('created_at')->label('Placed')->dateTime(),
                        TextEntry::make('name')->label('Customer'),
                        TextEntry::make('email')->copyable(),
                        TextEntry::make('phone'),
                        TextEntry::make('shipping_address')
                            ->label('Shipping address')
                            ->state(fn (Order $record) => $record->shippingLines())
                            ->listWithLineBreaks(),
                        TextEntry::make('billing_address')
                            ->label('Billing address')
                            ->state(fn (Order $record) => $record->billingAddress?->toPostalAddress()->lines())
                            ->listWithLineBreaks()
                            ->placeholder('Same as shipping'),
                    ]),
            ]);
    }
}
