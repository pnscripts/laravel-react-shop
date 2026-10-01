<?php

namespace PnShop\Promotion\Filament\Resources\Promotions\Pages;

use Filament\Resources\Pages\CreateRecord;
use PnShop\Promotion\Filament\Resources\Promotions\PromotionResource;

class CreatePromotion extends CreateRecord
{
    protected static string $resource = PromotionResource::class;

    protected function getRedirectUrl(): string
    {
        // Straight to editing, where coupons are added.
        return PromotionResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
