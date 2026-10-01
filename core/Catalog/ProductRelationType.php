<?php

namespace PnShop\Catalog;

enum ProductRelationType: string
{
    /** Similar products, shown on the product page. */
    case Related = 'related';

    /** Better or pricier alternatives, shown on the product page first. */
    case Upsell = 'upsell';

    /** Complementary products, suggested in the cart. */
    case CrossSell = 'cross_sell';

    public function label(): string
    {
        return match ($this) {
            self::Related => 'Related products',
            self::Upsell => 'Upsells',
            self::CrossSell => 'Cross-sells',
        };
    }

    public function relationName(): string
    {
        return match ($this) {
            self::Related => 'relatedProducts',
            self::Upsell => 'upsellProducts',
            self::CrossSell => 'crossSellProducts',
        };
    }
}
