<?php

namespace PnShop\Cms\Filament\Resources\Pages\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use PnShop\Cms\Filament\Resources\Pages\PageResource;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
