<?php

namespace PnShop\Api\Http\Controllers\Store;

use Illuminate\Support\Collection;
use PnShop\Api\Http\Controllers\ApiController;
use PnShop\Catalog\Models\Brand;
use PnShop\Catalog\Models\Category;

class CategoryController extends ApiController
{
    /**
     * Category tree
     *
     * Active categories, nested under their parents in menu order.
     *
     * @return array<string, mixed>
     */
    public function index(): array
    {
        $categories = Category::query()->active()->defaultOrder()->get();

        return ['data' => $this->branch($categories, null)];
    }

    /**
     * List brands
     *
     * @return array<string, mixed>
     */
    public function brands(): array
    {
        return ['data' => Brand::query()->active()->orderBy('name')->get()
            ->map(fn (Brand $brand) => ['id' => $brand->id, 'name' => $brand->name, 'slug' => $brand->slug, 'description' => $brand->description])
            ->values()
            ->all()];
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return list<array<string, mixed>>
     */
    private function branch(Collection $categories, ?int $parentId): array
    {
        return array_values($categories->where('parent_id', $parentId)
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'title' => $category->title,
                'slug' => $category->slug,
                'description' => $category->description,
                'children' => $this->branch($categories, $category->id),
            ])
            ->all());
    }
}
