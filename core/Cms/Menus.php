<?php

namespace PnShop\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use PnShop\Catalog\Models\Brand;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Product;
use PnShop\Cms\Models\Menu;
use PnShop\Cms\Models\MenuItem;
use PnShop\Cms\Models\Page;
use PnShop\Localization\Localization;

/**
 * Menus as storefront trees: labels and links in the current language. Items whose
 * target is gone or hidden are left out. Cached per menu and language until a menu,
 * item or linked record changes, and for at most ten minutes (scheduled pages).
 */
final class Menus
{
    private const VERSION_KEY = 'pnshop.menus.version';

    /**
     * @return list<array{label: string, url: string|null, new_tab: bool, children: list<array<string, mixed>>}>
     */
    public static function tree(string $code): array
    {
        $locale = app()->getLocale();
        $version = (int) Cache::get(self::VERSION_KEY, 0);

        return Cache::remember("pnshop.menu.{$code}.{$locale}.{$version}", now()->addMinutes(10), fn () => self::build($code));
    }

    /** Forget every cached menu. */
    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 0) + 1);
    }

    /**
     * @return list<array{label: string, url: string|null, new_tab: bool, children: list<array<string, mixed>>}>
     */
    private static function build(string $code): array
    {
        $menu = Menu::query()->where('code', $code)->first();

        if ($menu === null) {
            return [];
        }

        $items = $menu->items()->get();
        $targets = self::targets($items);

        return self::branch($items, null, $targets);
    }

    /**
     * @param  Collection<int, MenuItem>  $items
     * @param  array<string, array<int|string, Model>>  $targets
     * @return list<array{label: string, url: string|null, new_tab: bool, children: list<array<string, mixed>>}>
     */
    private static function branch(Collection $items, ?int $parentId, array $targets): array
    {
        $branch = [];

        foreach ($items->where('parent_id', $parentId) as $item) {
            $resolved = self::resolve($item, $targets);

            if ($resolved === null) {
                continue;
            }

            $branch[] = [...$resolved, 'children' => self::branch($items, $item->id, $targets)];
        }

        return $branch;
    }

    /**
     * @param  array<string, array<int|string, Model>>  $targets
     * @return array{label: string, url: string|null, new_tab: bool}|null
     */
    private static function resolve(MenuItem $item, array $targets): ?array
    {
        $prefix = app(Localization::class)->prefix(app()->getLocale());
        $local = fn (string $path): string => $prefix === '' ? $path : ($path === '/' ? $prefix : $prefix.$path);
        $label = trim((string) $item->label);
        $target = $item->type->hasTarget() ? ($targets[$item->type->value][(int) $item->target_id] ?? null) : null;

        if ($item->type->hasTarget() && $target === null) {
            return null;
        }

        [$url, $name] = match ($item->type) {
            MenuItemType::Home => [$local('/'), __('Home')],
            MenuItemType::Shop => [$local('/shop'), __('Shop')],
            MenuItemType::Page => [$local('/'.$target?->getAttribute('slug')), $target?->getAttribute('title')],
            MenuItemType::Category => [$local('/shop?category='.rawurlencode((string) $target?->getAttribute('slug'))), $target?->getAttribute('title')],
            MenuItemType::Brand => [$local('/shop?brand='.rawurlencode((string) $target?->getAttribute('slug'))), $target?->getAttribute('name')],
            MenuItemType::Product => [$local('/shop/'.$target?->getAttribute('slug')), $target?->getAttribute('title')],
            MenuItemType::Url => [self::url($item->url, $local), null],
            MenuItemType::Heading => [null, null],
        };

        $label = $label !== '' ? $label : (string) $name;

        if ($label === '' || ($url === null && $item->type !== MenuItemType::Heading)) {
            return null;
        }

        return ['label' => $label, 'url' => $url, 'new_tab' => $item->new_tab];
    }

    /**
     * @param  \Closure(string): string  $local
     */
    private static function url(?string $url, \Closure $local): ?string
    {
        $url = trim((string) $url);

        return match (true) {
            preg_match('#^(https?://|mailto:|tel:)#i', $url) === 1 => $url,
            str_starts_with($url, '/') && ! str_starts_with($url, '//') => $local($url),
            default => null,
        };
    }

    /**
     * Load every linked page, category, brand and product in one query per type.
     *
     * @param  Collection<int, MenuItem>  $items
     * @return array<string, array<int|string, Model>>
     */
    private static function targets(Collection $items): array
    {
        $ids = fn (MenuItemType $type) => $items->where('type', $type)->pluck('target_id')->filter()->all();

        return [
            MenuItemType::Page->value => Page::query()->live()->whereKey($ids(MenuItemType::Page))->get()->getDictionary(),
            MenuItemType::Category->value => Category::query()->active()->whereKey($ids(MenuItemType::Category))->get()->getDictionary(),
            MenuItemType::Brand->value => Brand::query()->active()->whereKey($ids(MenuItemType::Brand))->get()->getDictionary(),
            MenuItemType::Product->value => Product::query()->active()->whereKey($ids(MenuItemType::Product))->get()->getDictionary(),
        ];
    }
}
