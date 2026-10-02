<?php

namespace PnShop\Cart\Listeners;

use Illuminate\Auth\Events\Login;
use PnShop\Cart\CartRepository;
use PnShop\Customer\Models\User;

class MergeGuestCart
{
    public function __construct(private CartRepository $carts) {}

    public function handle(Login $event): void
    {
        if ($event->guard === 'web' && $event->user instanceof User) {
            $this->carts->mergeGuestCart($event->user, $this->carts->guestToken());
        }
    }
}
