<?php

namespace PnShop\Cart;

use Illuminate\Auth\Events\Login;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use PnShop\Cart\Console\PruneCartsCommand;
use PnShop\Cart\Listeners\MergeGuestCart;
use PnShop\Foundation\ModuleServiceProvider;

/**
 * Database carts for guests (cookie token) and customers (account).
 */
class CartServiceProvider extends ModuleServiceProvider
{
    protected function bootModule(): void
    {
        Event::listen(Login::class, MergeGuestCart::class);

        if ($this->app->runningInConsole()) {
            $this->commands([PruneCartsCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('pnshop:carts:prune')->daily()->withoutOverlapping();
        });
    }
}
