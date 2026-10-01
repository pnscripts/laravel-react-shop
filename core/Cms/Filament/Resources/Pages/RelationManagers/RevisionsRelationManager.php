<?php

namespace PnShop\Cms\Filament\Resources\Pages\RelationManagers;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Cms\Filament\Resources\Pages\PageResource;
use PnShop\Cms\Models\PageRevision;
use PnShop\Cms\PageRevisions;

class RevisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'revisions';

    protected static ?string $title = 'Revisions';

    protected static bool $isLazy = false;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('admin'))
            ->columns([
                TextColumn::make('created_at')->label('Saved')->dateTime(),
                TextColumn::make('admin.name')->label('By')->placeholder('—'),
                TextColumn::make('title')->state(fn (PageRevision $record) => $record->snapshot['attributes']['title'] ?? '—'),
                TextColumn::make('status')->state(fn (PageRevision $record) => ucfirst((string) ($record->snapshot['attributes']['status'] ?? ''))),
                TextColumn::make('blocks')->label('Blocks')->state(fn (PageRevision $record) => collect($record->snapshot['blocks'] ?? [])->flatten(2)->count()),
            ])
            ->recordActions([
                Action::make('restore')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->requiresConfirmation()
                    ->modalDescription('The page goes back to this version. The current version is kept as a revision.')
                    ->action(function (PageRevision $record): void {
                        app(PageRevisions::class)->restore($record, auth('admin')->user());

                        Notification::make()->success()->title('Revision restored.')->send();
                        $this->redirect(PageResource::getUrl('edit', ['record' => $this->getOwnerRecord()]));
                    }),
            ]);
    }
}
