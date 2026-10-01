<?php

namespace PnShop\Api\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use PnShop\Promotion\CouponCodes;
use PnShop\Promotion\Models\Coupon;
use PnShop\Promotion\Models\Promotion;
use PnShop\Promotion\PromotionRegistry;

/**
 * Promotions (cart rules) and their coupons. `conditions` and `actions` are lists of
 * {type, data}; the types and their settings are those of the admin's promotion builder.
 */
class PromotionController extends AdminController
{
    public function __construct(private PromotionRegistry $registry) {}

    /**
     * List promotions
     *
     * @return array<string, mixed>
     */
    public function index(Request $request): array
    {
        Gate::authorize('viewAny', Promotion::class);

        $promotions = $this->updatedSince(Promotion::query(), $request)->orderBy('id')->cursorPaginate($this->perPage($request))->withQueryString();

        return $this->paginated($promotions, fn (Promotion $promotion) => $this->present($promotion));
    }

    /**
     * Show a promotion
     *
     * With its coupons.
     *
     * @return array<string, mixed>
     */
    public function show(Promotion $promotion): array
    {
        Gate::authorize('view', $promotion);

        return ['data' => $this->present($promotion, withCoupons: true)];
    }

    /**
     * Create a promotion
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Promotion::class);

        $promotion = Promotion::query()->create($this->validated($request, null));

        return response()->json(['data' => $this->present($promotion->refresh())], 201);
    }

    /**
     * Update a promotion
     *
     * @return array<string, mixed>
     */
    public function update(Request $request, Promotion $promotion): array
    {
        Gate::authorize('update', $promotion);

        $promotion->update($this->validated($request, $promotion));

        return ['data' => $this->present($promotion->refresh())];
    }

    /**
     * Delete a promotion
     */
    public function destroy(Promotion $promotion): JsonResponse
    {
        Gate::authorize('delete', $promotion);

        $promotion->delete();

        return response()->json(null, 204);
    }

    /**
     * Generate coupon codes
     *
     * Unique random codes (with an optional prefix), e.g. one-use codes for a newsletter.
     */
    public function generateCoupons(Request $request, Promotion $promotion, CouponCodes $codes): JsonResponse
    {
        Gate::authorize('update', $promotion);

        $data = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:1000'],
            'prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]*$/'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        $created = $codes->generate($promotion, (int) $data['count'], (string) ($data['prefix'] ?? ''), isset($data['usage_limit']) ? (int) $data['usage_limit'] : null);

        return response()->json(['data' => ['codes' => $created]], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Promotion $promotion): array
    {
        $sometimes = $promotion === null ? [] : ['sometimes'];
        // Settings of each type are validated for the lists that are sent.
        $conditions = (array) $request->input('conditions', []);
        $actions = (array) $request->input('actions', []);

        $rules = [
            'name' => [...$sometimes, 'required', 'string', 'max:255'],
            'label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'requires_coupon' => ['sometimes', 'boolean'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after:starts_at'],
            'position' => ['sometimes', 'integer'],
            'stop_further' => ['sometimes', 'boolean'],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['sometimes', 'nullable', 'integer', 'min:1'],
            ...$this->registry->rulesFor($conditions, $actions),
        ];

        if ($promotion !== null) {
            $rules['conditions'] = ['sometimes', ...$rules['conditions']];
            $rules['actions'] = ['sometimes', ...$rules['actions']];
        }

        return $request->validate($rules);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Promotion $promotion, bool $withCoupons = false): array
    {
        return [
            ...$promotion->only(['id', 'name', 'label', 'description', 'is_active', 'requires_coupon', 'position', 'stop_further', 'usage_limit', 'usage_limit_per_customer', 'times_used']),
            'starts_at' => $promotion->starts_at?->toIso8601String(),
            'ends_at' => $promotion->ends_at?->toIso8601String(),
            'conditions' => array_values($promotion->conditions ?? []),
            'actions' => array_values($promotion->actions ?? []),
            ...($withCoupons ? ['coupons' => $promotion->coupons()->orderBy('id')->get()->map(fn (Coupon $coupon) => $coupon->only(['id', 'code', 'is_active', 'usage_limit', 'times_used']))->all()] : []),
            'updated_at' => $promotion->updated_at?->toIso8601String(),
        ];
    }
}
