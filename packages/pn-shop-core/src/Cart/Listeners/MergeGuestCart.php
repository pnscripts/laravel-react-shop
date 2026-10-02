<?php

namespace PnShop\Cart\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use PnShop\Cart\CartRepository;

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
