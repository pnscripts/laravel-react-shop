<?php

namespace PnShop\Sales\Models;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use PnShop\Inventory\OrderStockStatus;
use PnShop\Money\MoneyCast;
use PnShop\Money\MoneyPresenter;
use PnShop\Sales\Factories\OrderFactory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property string $currency ISO 4217 code the order was placed in
 * @property OrderStockStatus $stock_status
 * @property string|null $address free-text address of orders placed before structured addresses
 * @property Money|null $subtotal sum of the lines when the order was placed
 * @property Money|null $total what the customer pays
 * @property list<array{code: string, label: string, amount: int, included: bool}>|null $totals breakdown between subtotal and total, amounts in minor units
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'address',
        'phone',
        'email',
        'order_status_id',
        'payment_method_id',
        'currency',
        'stock_status',
        'subtotal',
        'total',
        'totals',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'subtotal' => MoneyCast::class.':currency',
        'total' => MoneyCast::class.':currency',
        'totals' => 'array',
        'stock_status' => OrderStockStatus::class,
    ];

    /**
     * Get the items for this order.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddress::class);
    }

    /**
     * @return HasOne<OrderAddress, $this>
     */
    public function shippingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type', OrderAddress::SHIPPING);
    }

    /**
     * @return HasOne<OrderAddress, $this>
     */
    public function billingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type', OrderAddress::BILLING);
    }

    /**
     * Display lines of the shipping address, or of the free-text address of older orders.
     *
     * @return list<string>
     */
    public function shippingLines(): array
    {
        return $this->shippingAddress?->toPostalAddress()->lines() ?? array_values(array_filter([$this->name, $this->address]));
    }

    /**
     * What the customer pays; older orders without stored totals fall back to their lines.
     */
    public function grandTotal(): Money
    {
        return $this->total ?? $this->itemsTotal();
    }

    /**
     * Subtotal, breakdown lines and total, ready for display.
     *
     * @return array{subtotal: array<string, mixed>|null, lines: list<array<string, mixed>>, total: array<string, mixed>|null}
     */
    public function presentTotals(): array
    {
        $subtotal = $this->subtotal ?? $this->itemsTotal();

        return [
            'subtotal' => MoneyPresenter::present($subtotal),
            'lines' => array_map(fn (array $line) => [
                'code' => $line['code'],
                'label' => $line['label'],
                'amount' => MoneyPresenter::present(Money::ofMinor($line['amount'], $this->currency)),
                'included' => $line['included'],
            ], $this->totals ?? []),
            'total' => MoneyPresenter::present($this->grandTotal()),
        ];
    }

    /**
     * Get the user that owns the order.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order status associated with the order.
     *
     * @return BelongsTo<OrderStatus, $this>
     */
    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class);
    }

    /**
     * Get the payment method associated with the order.
     *
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('sales')
            ->logOnly(['order_status_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Sum of the order lines in the order currency.
     */
    public function itemsTotal(): Money
    {
        return $this->items->reduce(
            fn (Money $total, OrderItem $item) => $total->plus($item->lineTotal()),
            Money::zero($this->currency),
        );
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
