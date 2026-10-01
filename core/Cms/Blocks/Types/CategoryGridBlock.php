<?php

namespace PnShop\Cms\Blocks\Types;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use PnShop\Catalog\Filament\Resources\Categories\CategoryResource;
use PnShop\Catalog\Models\Category;
use PnShop\Cms\Blocks\BlockType;
use PnShop\Cms\Blocks\Concerns\BlockHelpers;

final class CategoryGridBlock extends BlockType
{
    use BlockHelpers;

    public function key(): string
    {
        return 'category_grid';
    }

    public function label(): string
    {
        return 'Categories';
    }

    public function icon(): string
    {
        return 'heroicon-o-rectangle-group';
    }

    public function fields(): array
    {
        return [
            TextInput::make('heading')->maxLength(160),
            Select::make('category_ids')->label('Categories')->multiple()->required()
                ->options(fn () => CategoryResource::parentOptions(null)),
        ];
    }

    public function props(array $data): ?array
    {
        $ids = array_map('intval', (array) ($data['category_ids'] ?? []));
        $categories = Category::query()->active()->whereKey($ids)->get()->keyBy('id');

        $items = collect($ids)->map(fn (int $id) => $categories->get($id))->filter()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'title' => $category->title,
                'url' => $this->localUrl('/shop?category='.rawurlencode($category->slug)),
            ])->values()->all();

        return $items === [] ? null : ['heading' => $this->text($data['heading'] ?? null), 'categories' => $items];
    }
}
