<?php

namespace PnShop\Promotion\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use PnShop\Promotion\Factories\PromotionFactory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A cart rule: when all its conditions hold, its actions give discounts. Automatic unless
 * it requires one of its coupons.
 *
 * @property int $id
 * @property string $name
 * @property string|null $label
 * @property string|null $description
 * @property bool $is_active
 * @property bool $requires_coupon
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int $position
 * @property bool $stop_further
 * @property int|null $usage_limit
 * @property int|null $usage_limit_per_customer
 * @property int $times_used
 * @property array<array-key, array<string, mixed>>|null $conditions [{type, data}], validated by the registered types
 * @property array<array-key, array<string, mixed>>|null $actions [{type, data}]
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory;

    use LogsActivity, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name', 'label', 'description', 'is_active', 'requires_coupon', 'starts_at', 'ends_at', 'position',
        'stop_further', 'usage_limit', 'usage_limit_per_customer', 'conditions', 'actions',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'requires_coupon' => 'boolean',
            'stop_further' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'position' => 'integer',
            'usage_limit' => 'integer',
            'usage_limit_per_customer' => 'integer',
            'times_used' => 'integer',
            'conditions' => 'array',
            'actions' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Admin builders key entries by random ids; store plain lists.
        static::saving(function (Promotion $promotion): void {
            $promotion->conditions = array_values($promotion->conditions ?? []);
            $promotion->actions = array_values($promotion->actions ?? []);
        });
    }

    /**
     * Active, within its dates and below its usage limit.
     *
     * @param  Builder<self>  $query
     */
    public function scopeRunning(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->where(fn (Builder $query) => $query->whereNull('usage_limit')->orWhereColumn('times_used', '<', 'usage_limit'));
    }

    /** What customers see next to the discount. */
    public function displayLabel(): string
    {
        return $this->label ?: $this->name;
    }

    /**
     * @return HasMany<Coupon, $this>
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    /**
     * @return HasMany<PromotionRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(PromotionRedemption::class);
    }

    protected static function newFactory(): PromotionFactory
    {
        return PromotionFactory::new();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('marketing')->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
