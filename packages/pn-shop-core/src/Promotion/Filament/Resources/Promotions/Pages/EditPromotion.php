<?php

namespace PnShop\Promotion\Filament\Resources\Promotions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use PnShop\Promotion\Filament\Resources\Promotions\PromotionResource;

class EditPromotion extends EditRecord
{
    protected static string $resource = PromotionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
