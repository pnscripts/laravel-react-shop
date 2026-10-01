<?php

namespace PnShop\Cms;

use Filament\Support\Contracts\HasLabel;

enum MenuItemType: string implements HasLabel
{
    case Home = 'home';
    case Shop = 'shop';
    case Page = 'page';
    case Category = 'category';
    case Brand = 'brand';
    case Product = 'product';
    case Url = 'url';
    case Heading = 'heading';

    public function getLabel(): string
    {
        return match ($this) {
            self::Home => 'Homepage',
            self::Shop => 'All products',
            self::Page => 'Page',
            self::Category => 'Category',
            self::Brand => 'Brand',
            self::Product => 'Product',
            self::Url => 'Address (URL)',
            self::Heading => 'Heading without a link',
        };
    }

    public function hasTarget(): bool
    {
        return in_array($this, [self::Page, self::Category, self::Brand, self::Product], true);
    }
}
