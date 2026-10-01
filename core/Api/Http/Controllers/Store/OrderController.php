<?php

namespace PnShop\Api\Http\Controllers\Store;

use Illuminate\Http\Request;
use PnShop\Api\Http\Controllers\ApiController;
use PnShop\Api\Http\Resources\OrderPresenter;
use PnShop\Sales\Models\Order;

class OrderController extends ApiController
{
    /**
     * Show an order
     *
     * For the customer who placed it (token), or anyone with the signed link returned when
     * the order was placed (guests).
     *
     * @return array<string, mixed>
     */
    public function show(Request $request, Order $order): array
    {
        $customer = $this->customer($request);
        $isOwner = $customer !== null && (int) $order->user_id === $customer->id;

        // Only the signature counts for others; the token-less check must not tell orders apart.
        abort_unless($isOwner || $request->hasValidSignatureWhileIgnoring(['locale']), 404);

        return ['data' => OrderPresenter::detail($order->load(OrderPresenter::RELATIONS))];
    }
}
