<?php

namespace PnShop\Media\Filament\Resources\Media;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use PnShop\Localization\Filament\TranslationsSection;
use PnShop\Media\Filament\Resources\Media\Pages\EditMedia;
use PnShop\Media\Filament\Resources\Media\Pages\ListMedia;
use PnShop\Media\Models\Media;
use UnitEnum;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Media';

    protected static ?string $modelLabel = 'media item';

    protected static ?string $pluralModelLabel = 'media';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                ImageEntry::make('preview')
                    ->state(fn (Media $record) => $record->url('medium'))
                    ->imageHeight(240)
                    ->columnSpanFull(),
                TextInput::make('alt')
                    ->label('Alternative text')
                    ->helperText('Describes the image for screen readers and search engines.')
                    ->maxLength(255),
                TextInput::make('title')->maxLength(255),
            ]),
            TranslationsSection::make([
                'alt' => fn (string $name) => TextInput::make($name)->label('Alternative text')->maxLength(255),
                'title' => fn (string $name) => TextInput::make($name)->label('Title')->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('thumbnail')
                    ->state(fn (Media $record) => $record->url('thumb'))
                    ->imageHeight(56),
                TextColumn::make('original_name')->label('File')->searchable()->limit(40),
                TextColumn::make('dimensions')
                    ->state(fn (Media $record) => $record->width ? "{$record->width} × {$record->height}" : '—'),
                TextColumn::make('size')->formatStateUsing(fn (int $state) => Number::fileSize($state)),
                TextColumn::make('alt')->placeholder('Missing')->toggleable(),
                TextColumn::make('created_at')->label('Uploaded')->since()->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }
}
