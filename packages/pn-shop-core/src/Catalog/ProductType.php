<?php

namespace PnShop\Catalog;

enum ProductType: string
{
    /** One variant: price, SKU and stock are the product's own. */
    case Simple = 'simple';

    /** Several variants that combine option values (Size, Color, ...). */
    case Variable = 'variable';

    public function label(): string
    {
        return match ($this) {
            self::Simple => 'Simple product',
            self::Variable => 'Product with variants',
        };
    }
}
