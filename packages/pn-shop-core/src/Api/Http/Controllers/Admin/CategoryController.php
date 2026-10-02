<?php

namespace PnShop\Api\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use PnShop\Api\Http\Resources\AdminCatalogPresenter;
use PnShop\Catalog\Models\Category;

class CategoryController extends AdminController
{
    /**
     * List categories
     *
     * Every category (also hidden ones) in tree order; `parent_id` gives the nesting.
     *
     * @return array<string, mixed>
     */
    public function index(Request $request): array
    {
        Gate::authorize('viewAny', Category::class);

        return ['data' => $this->updatedSince(Category::query()->defaultOrder()->with('allTranslations'), $request)->get()
            ->map(fn (Category $category) => AdminCatalogPresenter::category($category))
            ->values()
            ->all()];
    }

    /**
     * Show a category
     *
     * @return array<string, mixed>
     */
    public function show(Category $category): array
    {
        Gate::authorize('view', $category);

        return ['data' => AdminCatalogPresenter::category($category)];
    }

    /**
     * Create a category
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Category::class);

        $category = DB::transaction(fn () => $this->save(new Category, $request->validate($this->rules(null))));

        return response()->json(['data' => AdminCatalogPresenter::category($category)], 201);
    }

    /**
     * Update a category
     *
     * Changing `parent_id` moves the category with its subcategories.
     *
     * @return array<string, mixed>
     */
    public function update(Request $request, Category $category): array
    {
        Gate::authorize('update', $category);

        return ['data' => AdminCatalogPresenter::category(DB::transaction(fn () => $this->save($category, $request->validate($this->rules($category)))))];
    }

    /**
     * Delete a category
     */
    public function destroy(Category $category): JsonResponse
    {
        Gate::authorize('delete', $category);

        $category->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?Category $category): array
    {
        $sometimes = $category === null ? [] : ['sometimes'];

        return [
            'title' => [...$sometimes, 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('product_categories', 'slug')->ignore($category?->id)],
            'description' => ['sometimes', 'nullable', 'string'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            // Not the category itself or one of its subcategories.
            'parent_id' => ['sometimes', 'nullable', 'integer', Rule::exists('product_categories', 'id')->whereNull('deleted_at'), Rule::notIn($category?->subtreeIds() ?? [])],
            ...$this->translationRules([
                'title' => ['string', 'max:255'],
                'slug' => ['string', 'max:255', 'alpha_dash:ascii'],
                'description' => ['string'],
                'meta_title' => ['string', 'max:255'],
                'meta_description' => ['string', 'max:500'],
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function save(Category $category, array $data): Category
    {
        $this->fillTranslatable($category, $data)->save();

        return $category->refresh();
    }
}
