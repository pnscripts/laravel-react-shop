<?php

namespace PnShop\Customer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PnShop\Customer\Factories\CustomerAddressFactory;
use PnShop\Customer\PostalAddress;

/**
 * An entry in a customer's address book.
 *
 * @property int $id
 * @property int $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $company
 * @property string $line1
 * @property string|null $line2
 * @property string $city
 * @property string|null $postcode
 * @property string|null $region
 * @property string $country_code
 * @property string|null $phone
 * @property bool $is_default_shipping
 * @property bool $is_default_billing
 */
class CustomerAddress extends Model
{
    /** @use HasFactory<CustomerAddressFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [...PostalAddress::FIELDS, 'is_default_shipping', 'is_default_billing'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_default_shipping' => 'boolean', 'is_default_billing' => 'boolean'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toPostalAddress(): PostalAddress
    {
        return PostalAddress::fromArray($this->only(PostalAddress::FIELDS));
    }

    protected static function newFactory(): CustomerAddressFactory
    {
        return CustomerAddressFactory::new();
    }
}
