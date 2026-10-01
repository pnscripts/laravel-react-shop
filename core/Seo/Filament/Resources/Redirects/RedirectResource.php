<?php

namespace PnShop\Seo\Filament\Resources\Redirects;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use PnShop\Seo\Filament\Resources\Redirects\Pages\ManageRedirects;
use PnShop\Seo\Models\Redirect;
use UnitEnum;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnRight;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'from_path';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('from_path')->label('From')->required()->maxLength(1024)
                ->rules(['regex:#^/#'])
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn (string $state) => Redirect::normalize($state))
                ->helperText('An address of this shop that no longer exists, e.g. /old-page or /bg/shop/old-product.'),
            TextInput::make('to_url')->label('To')->required()->maxLength(2048)
                ->rules(['regex:#^(https?://|/)#i', fn (Get $get) => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                    if (is_string($value) && str_starts_with($value, '/') && Redirect::normalize($value) === Redirect::normalize((string) $get('from_path'))) {
                        $fail(__('A redirect cannot point to itself.'));
                    }
                }])
                ->helperText('A path of this shop or a full URL.'),
            Select::make('status')->options([301 => '301 Moved permanently', 302 => '302 Found (temporary)'])->default(301)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('from_path')->label('From')->searchable(),
                TextColumn::make('to_url')->label('To')->searchable()->limit(60),
                TextColumn::make('status'),
                IconColumn::make('is_automatic')->label('Automatic')->boolean(),
                TextColumn::make('hits')->sortable(),
                TextColumn::make('last_hit_at')->label('Last used')->since()->placeholder('Never')->sortable(),
            ])
            ->filters([TernaryFilter::make('is_automatic')->label('Created automatically')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRedirects::route('/')];
    }
}
