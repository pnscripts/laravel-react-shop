<?php

namespace PnShop\Tax\Contracts;

use PnShop\Tax\TaxRequest;
use PnShop\Tax\TaxResult;

/**
 * Calculates the tax on a cart or order. The core provider uses the rate tables
 * (Admin → Store → Tax); an extension can bind its own, e.g. a tax service API.
 */
interface TaxProvider
{
    public function calculate(TaxRequest $request): TaxResult;
}
