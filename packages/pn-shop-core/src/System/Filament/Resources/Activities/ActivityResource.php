<?php

namespace PnShop\System\Filament\Resources\Activities;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use PnShop\System\Filament\Resources\Activities\Pages\ListActivities;
use PnShop\System\Filament\Resources\Activities\Tables\ActivitiesTable;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Activity log';

    protected static ?int $navigationSort = 80;

    public static function table(Table $table): Table
    {
        return ActivitiesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
        ];
    }
}
