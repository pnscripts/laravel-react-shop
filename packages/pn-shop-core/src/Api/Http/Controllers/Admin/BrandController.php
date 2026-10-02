<?php

namespace PnShop\Api\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use PnShop\Api\Http\Resources\AdminCatalogPresenter;
use PnShop\Catalog\Models\Brand;

class BrandController extends AdminController
{
    /**
     * List brands
     *
     * @return array<string, mixed>
     */
    public function index(Request $request): array
    {
        Gate::authorize('viewAny', Brand::class);

        $brands = $this->updatedSince(Brand::query()->with('allTranslations'), $request)->orderBy('id')->cursorPaginate($this->perPage($request))->withQueryString();

        return $this->paginated($brands, fn (Brand $brand) => AdminCatalogPresenter::brand($brand));
    }

    /**
     * Show a brand
     *
     * @return array<string, mixed>
     */
    public function show(Brand $brand): array
    {
        Gate::authorize('view', $brand);

        return ['data' => AdminCatalogPresenter::brand($brand)];
    }

    /**
     * Create a brand
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Brand::class);

        $brand = new Brand;
        $this->fillTranslatable($brand, $request->validate($this->rules(null)))->save();

        return response()->json(['data' => AdminCatalogPresenter::brand($brand->refresh())], 201);
    }

    /**
     * Update a brand
     *
     * @return array<string, mixed>
     */
    public function update(Request $request, Brand $brand): array
    {
        Gate::authorize('update', $brand);

        $this->fillTranslatable($brand, $request->validate($this->rules($brand)))->save();

        return ['data' => AdminCatalogPresenter::brand($brand->refresh())];
    }

    /**
     * Delete a brand
     */
    public function destroy(Brand $brand): JsonResponse
    {
        Gate::authorize('delete', $brand);

        $brand->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?Brand $brand): array
    {
        return [
            'name' => [...($brand === null ? [] : ['sometimes']), 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('brands', 'slug')->ignore($brand?->id)],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            ...$this->translationRules([
                'name' => ['string', 'max:255'],
                'slug' => ['string', 'max:255', 'alpha_dash:ascii'],
                'description' => ['string'],
            ]),
        ];
    }
}
