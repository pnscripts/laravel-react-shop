<?php

namespace PnShop\Catalog\Filament\Resources\Options\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use PnShop\Catalog\Filament\Resources\Options\OptionResource;

class ListOptions extends ListRecords
{
    protected static string $resource = OptionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
