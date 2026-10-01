<?php

namespace PnShop\Catalog\Filament\Resources\Attributes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use PnShop\Catalog\Filament\Resources\Attributes\AttributeResource;

class ListAttributes extends ListRecords
{
    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
