<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PnShop\Customer\Models\CustomerAddress;
use PnShop\Customer\Models\CustomerGroup;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A customer account (storefront sign-in). Staff accounts are PnShop\Acl\Models\AdminUser.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property int|null $customer_group_id
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->customer_group_id ??= CustomerGroup::query()->where('is_default', true)->value('id');
        });
    }

    /**
     * @return HasMany<CustomerAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->orderByDesc('is_default_shipping')->latest();
    }

    /**
     * @return BelongsTo<CustomerGroup, $this>
     */
    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('customers')
            ->logOnly(['name', 'email', 'customer_group_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
