<?php

namespace PnShop\Cart\Console;

use Illuminate\Console\Command;
use PnShop\Cart\Models\Cart;

class PruneCartsCommand extends Command
{
    protected $signature = 'pnshop:carts:prune
        {--guest-days=30 : Delete guest carts not changed for this many days}
        {--customer-days=180 : Delete customer carts not changed for this many days}';

    protected $description = 'Delete abandoned carts';

    public function handle(): int
    {
        $guests = Cart::query()->whereNull('user_id')->where('updated_at', '<', now()->subDays((int) $this->option('guest-days')))->delete();
        $customers = Cart::query()->whereNotNull('user_id')->where('updated_at', '<', now()->subDays((int) $this->option('customer-days')))->delete();

        $this->info("Deleted {$guests} guest and {$customers} customer cart(s).");

        return self::SUCCESS;
    }
}
