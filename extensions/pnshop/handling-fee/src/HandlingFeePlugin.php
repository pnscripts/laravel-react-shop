<?php

namespace PnShop\Plugins\HandlingFee;

use Illuminate\Support\Facades\Gate;
use PnShop\Cart\Totals\CartCalculator;
use PnShop\Extension\Plugin;
use PnShop\Foundation\Extension\PipelineRegistry;
use PnShop\Plugins\HandlingFee\Models\Exemption;
use PnShop\Plugins\HandlingFee\Policies\ExemptionPolicy;

class HandlingFeePlugin extends Plugin
{
    protected function bootPlugin(): void
    {
        $this->app->make(PipelineRegistry::class)->stage(CartCalculator::PIPELINE, ApplyHandlingFee::class, 300);

        Gate::policy(Exemption::class, ExemptionPolicy::class);
    }

    public function uninstall(bool $keepData): void
    {
        // Everything the plugin stores is in its own table, which the manager drops
        // (with its migration) when the data is not kept. Nothing else to clean up.
    }
}
