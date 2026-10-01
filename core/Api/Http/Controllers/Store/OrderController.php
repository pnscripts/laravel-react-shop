<?php

namespace PnShop\Api\Http\Controllers\Store;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PnShop\Api\Http\Controllers\ApiController;
use PnShop\Api\Http\Resources\OrderPresenter;
use PnShop\Returns\Models\ReturnRequest;
use PnShop\Returns\ReturnPresenter;
use PnShop\Returns\ReturnReason;
use PnShop\Returns\ReturnService;
use PnShop\Sales\Exceptions\OrderException;
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
    public function show(Request $request, Order $order, ReturnService $returns): array
    {
        $this->authorizeOrder($request, $order);

        $eligibility = $returns->eligibility($order);

        return ['data' => [
            ...OrderPresenter::detail($order->load(OrderPresenter::RELATIONS)),
            'returns' => ReturnRequest::query()->where('order_id', $order->id)->latest('id')->get()->map(fn (ReturnRequest $return) => ReturnPresenter::present($return))->all(),
            'returnable' => [
                'allowed' => $eligibility['allowed'],
                'reason' => $eligibility['reason'],
                'deadline' => $eligibility['deadline']?->toIso8601String(),
                // order item id => units that can still be returned
                'items' => (object) $eligibility['items'],
                'reasons' => ReturnReason::options(),
            ],
        ]];
    }

    /**
     * Request a return
     *
     * `items` maps order item ids to units (see the order's `returnable.items`); `reason` is
     * one of `returnable.reasons`. Same access as reading the order (keep the signed URL's
     * query string for guests).
     */
    public function requestReturn(Request $request, Order $order, ReturnService $returns): JsonResponse
    {
        $this->authorizeOrder($request, $order);

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['integer', 'min:0'],
            'reason' => ['required', Rule::enum(ReturnReason::class)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $return = $returns->request($order, $data['items'], ReturnReason::from($data['reason']), $data['note'] ?? null, $this->customer($request));
        } catch (OrderException $e) {
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }

        return response()->json(['data' => ReturnPresenter::present($return)], 201);
    }

    /**
     * The customer who placed it, or a valid signed link. Others get "not found".
     */
    private function authorizeOrder(Request $request, Order $order): void
    {
        $customer = $this->customer($request);
        $isOwner = $customer !== null && (int) $order->user_id === $customer->id;

        abort_unless($isOwner || $request->hasValidSignatureWhileIgnoring(['locale']), 404);
    }
}
