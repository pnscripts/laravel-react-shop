<?php

namespace PnShop\Catalog\Filament\Resources\Attributes\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use PnShop\Catalog\Filament\Resources\Attributes\AttributeResource;
use PnShop\Localization\Filament\SavesTranslations;

class EditAttribute extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
