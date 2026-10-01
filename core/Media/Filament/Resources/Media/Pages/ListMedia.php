<?php

namespace PnShop\Media\Filament\Resources\Media\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use PnShop\Media\Filament\Resources\Media\MediaResource;
use PnShop\Media\MediaLibrary;
use PnShop\Media\Models\Media;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Upload')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->authorize(fn () => auth('admin')->user()?->can('create', Media::class) ?? false)
                ->schema([
                    FileUpload::make('files')
                        ->hiddenLabel()
                        ->multiple()
                        ->image()
                        ->acceptedFileTypes(MediaLibrary::IMAGE_TYPES)
                        ->maxSize(MediaLibrary::MAX_UPLOAD_KB)
                        ->disk(MediaLibrary::disk())
                        ->directory(MediaLibrary::directory())
                        ->storeFileNamesIn('original_names')
                        ->required(),
                ])
                ->action(function (array $data, MediaLibrary $library): void {
                    $names = (array) ($data['original_names'] ?? []);

                    foreach ((array) $data['files'] as $path) {
                        $library->register((string) $path, $names[$path] ?? null);
                    }

                    Notification::make()->success()->title(count((array) $data['files']).' file(s) uploaded.')->send();
                }),
        ];
    }
}
