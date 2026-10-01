<?php

namespace PnShop\Plugins\HandlingFee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PnShop\Customer\Models\CustomerGroup;

/**
 * A customer group that pays no handling fee.
 *
 * @property int $id
 * @property int $customer_group_id
 * @property string|null $note
 */
class Exemption extends Model
{
    protected $table = 'pnshop_handling_fee_exemptions';

    /** @var list<string> */
    protected $fillable = ['customer_group_id', 'note'];

    /**
     * @return BelongsTo<CustomerGroup, $this>
     */
    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }
}
