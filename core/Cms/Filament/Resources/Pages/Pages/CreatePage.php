<?php

namespace PnShop\Cms\Filament\Resources\Pages\Pages;

use Filament\Resources\Pages\CreateRecord;
use PnShop\Cms\Filament\Resources\Pages\PageResource;
use PnShop\Cms\Models\Page;
use PnShop\Cms\PageRevisions;
use PnShop\Localization\Filament\SavesTranslations;

class CreatePage extends CreateRecord
{
    use SavesTranslations {
        afterCreate as saveTranslationsAfterCreate;
    }

    protected static string $resource = PageResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] = auth('admin')->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->saveTranslationsAfterCreate();

        /** @var Page $page */
        $page = $this->getRecord();
        app(PageRevisions::class)->record($page, auth('admin')->user());
    }
}
