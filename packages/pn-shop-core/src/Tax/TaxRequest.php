<?php

namespace PnShop\Tax;

use PnShop\Customer\Models\User;

final readonly class TaxRequest
{
    /**
     * @param  list<TaxableLine>  $lines
     * @param  bool  $pricesIncludeTax  amounts are gross (tax is extracted) or net (tax is added)
     */
    public function __construct(
        public array $lines,
        public string $currency,
        public string $countryCode,
        public ?string $postcode,
        public bool $pricesIncludeTax,
        public ?User $customer = null,
    ) {}
}
