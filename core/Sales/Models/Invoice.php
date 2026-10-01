<?php

namespace PnShop\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An issued invoice: a frozen copy of what it says.
 *
 * @property int $id
 * @property int $order_id
 * @property string $number
 * @property Carbon $issued_at
 * @property string $currency
 * @property string|null $locale
 * @property array{name: string, address: string|null, tax_number: string|null, email: string|null, phone: string|null, footer: string|null} $seller
 * @property array{name: string, lines: list<string>, email: string|null} $buyer
 * @property list<array{title: string, sku: string|null, quantity: int, unit: int, total: int, tax: int}> $lines amounts in minor units
 * @property array{subtotal: int, lines: list<array{code: string, label: string, amount: int, included: bool}>, total: int} $totals amounts in minor units
 */
class Invoice extends Model
{
    /** @var list<string> */
    protected $fillable = ['order_id', 'number', 'issued_at', 'currency', 'locale', 'seller', 'buyer', 'lines', 'totals'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'seller' => 'array',
            'buyer' => 'array',
            'lines' => 'array',
            'totals' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
