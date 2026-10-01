<?php

namespace PnShop\Media\Filament\Resources\Media\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use PnShop\Localization\Filament\SavesTranslations;
use PnShop\Media\Filament\Resources\Media\MediaResource;

class EditMedia extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
