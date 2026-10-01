<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PnShop\Returns\ReturnReason;
use PnShop\Returns\ReturnService;
use PnShop\Sales\Exceptions\OrderException;
use PnShop\Sales\Models\Order;

class ReturnController extends Controller
{
    /**
     * A return request from the order page, by whoever may see the order.
     */
    public function store(Request $request, Order $order, ReturnService $returns): RedirectResponse
    {
        abort_unless(OrderController::canView($request, $order), 403);

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['integer', 'min:0'],
            'reason' => ['required', Rule::enum(ReturnReason::class)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $return = $returns->request($order, $data['items'], ReturnReason::from($data['reason']), $data['note'] ?? null, $request->user());
        } catch (OrderException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        }

        return back()->with('success', __('Return :number requested. We will email you when it is reviewed.', ['number' => $return->number]));
    }
}
