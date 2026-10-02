<?php

namespace PnShop\Seo\Filament\Resources\Redirects\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use PnShop\Seo\Filament\Resources\Redirects\RedirectResource;

class ManageRedirects extends ManageRecords
{
    protected static string $resource = RedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
