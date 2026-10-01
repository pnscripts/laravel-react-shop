<?php

namespace PnShop\Extension\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use PnShop\Extension\Exceptions\ExtensionException;
use PnShop\Extension\ExtensionManager;
use PnShop\Extension\ExtensionStatus;
use PnShop\Extension\Manifest;
use PnShop\Extension\Models\Extension;
use PnShop\Extension\PluginLoader;
use PnShop\Extension\ZipPackage;
use UnitEnum;

/**
 * Extensions found on disk, with their state and lifecycle actions.
 */
class ManageExtensions extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static string|UnitEnum|null $navigationGroup = 'Extensions';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Extensions';

    protected static ?string $slug = 'extensions';

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('system.extensions.manage') ?? false;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Text::make('Safe mode is on: no extension is running. Turn PNSHOP_SAFE_MODE off when the faulty extension is disabled or removed.')
                ->color('danger')
                ->visible(PluginLoader::safeMode()),
            Text::make('Extensions run with full access to the shop and its data. Install only extensions you trust.')
                ->color('gray'),
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->rows())
            ->columns([
                TextColumn::make('name')->description(fn (array $record) => $record['id']),
                TextColumn::make('version')->description(fn (array $record) => $record['installed'] !== null && $record['installed'] !== $record['version'] ? "installed: {$record['installed']}" : null),
                TextColumn::make('status')->badge()->formatStateUsing(fn (ExtensionStatus $state) => $state->getLabel())->color(fn (ExtensionStatus $state) => $state->getColor()),
                TextColumn::make('problems')->listWithLineBreaks()->color('danger')->placeholder('—')->wrap(),
            ])
            ->recordActions([
                ActionGroup::make([
                    $this->lifecycle('install', 'Install', 'Installed', Heroicon::OutlinedArrowDownTray, fn (array $r) => in_array($r['status'], [ExtensionStatus::Available, ExtensionStatus::Failed], true)),
                    $this->lifecycle('enable', 'Enable', 'Enabled', Heroicon::OutlinedPlay, fn (array $r) => in_array($r['status'], [ExtensionStatus::Installed, ExtensionStatus::Disabled], true) && ! $r['update']),
                    $this->lifecycle('update', 'Update', 'Updated', Heroicon::OutlinedArrowPath, fn (array $r) => $r['update']),
                    $this->lifecycle('disable', 'Disable', 'Disabled', Heroicon::OutlinedPause, fn (array $r) => $r['status'] === ExtensionStatus::Enabled),
                    Action::make('uninstall')
                        ->label('Uninstall')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->visible(fn (array $record) => in_array($record['status'], [ExtensionStatus::Installed, ExtensionStatus::Disabled, ExtensionStatus::Failed], true))
                        ->requiresConfirmation()
                        ->schema([Toggle::make('purge')->label('Also delete its data and settings')->helperText('Its tables are dropped. This cannot be undone.')])
                        ->action(fn (array $record, array $data) => $this->run(fn (ExtensionManager $manager) => $manager->uninstall($record['id'], keepData: ! ($data['purge'] ?? false), actor: auth('admin')->user()), "Uninstalled {$record['id']}.")),
                    Action::make('verify')
                        ->label('Check files')
                        ->icon(Heroicon::OutlinedShieldCheck)
                        ->visible(fn (array $record) => $record['status'] !== ExtensionStatus::Available)
                        ->action(function (array $record): void {
                            $changes = app(ExtensionManager::class)->verify($record['id']);
                            $files = array_merge(...array_values($changes));

                            $files === []
                                ? Notification::make()->success()->title('No files changed since installation.')->send()
                                : Notification::make()->danger()->title(count($files).' file(s) changed since installation')->body(implode("\n", array_slice($files, 0, 10)))->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('No extensions found')
            ->emptyStateDescription('Put extensions in the "extensions" folder (vendor/name/pnshop.json) or install them with Composer.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Upload extension')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->visible((bool) config('pnshop.extensions.uploads'))
                ->schema([
                    FileUpload::make('archive')->label('Zip archive')->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])->maxSize(ZipPackage::MAX_BYTES / 1024)->disk('local')->directory('extension-uploads')->required(),
                ])
                ->action(function (array $data): void {
                    $path = Storage::disk('local')->path((string) $data['archive']);

                    $this->run(function () use ($path) {
                        $manifest = ZipPackage::extract($path, ExtensionManager::path());
                        activity('extensions')->causedBy(auth('admin')->user())->event('uploaded')->withProperties(['extension' => $manifest->id, 'version' => $manifest->version])->log("Uploaded {$manifest->id} {$manifest->version}");
                    }, 'Extension uploaded. Install it from the list.');

                    Storage::disk('local')->delete((string) $data['archive']);
                }),
        ];
    }

    /**
     * @param  \Closure(array<string, mixed>): bool  $visible
     */
    private function lifecycle(string $name, string $label, string $done, Heroicon $icon, \Closure $visible): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->visible(fn (array $record) => $visible($record))
            ->requiresConfirmation($name !== 'disable')
            ->action(fn (array $record) => $this->run(fn (ExtensionManager $manager) => $manager->{$name}($record['id'], actor: auth('admin')->user()), "{$done} {$record['id']}."));
    }

    private function run(\Closure $operation, string $success): void
    {
        try {
            $operation(app(ExtensionManager::class));
        } catch (ExtensionException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        }

        Notification::make()->success()->title($success)->send();
        $this->resetTable();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function rows(): array
    {
        $manager = app(ExtensionManager::class);
        $installed = Extension::query()->get()->keyBy('id');
        $rows = [];

        foreach ($manager->discover() as $manifest) {
            /** @var Manifest $manifest */
            $extension = $installed->get($manifest->id);
            $status = $extension->status ?? ExtensionStatus::Available;

            $rows[$manifest->id] = [
                'id' => $manifest->id,
                'name' => $manifest->name,
                'version' => $manifest->version,
                'installed' => $extension?->version,
                'status' => $status,
                'update' => $extension !== null && $status !== ExtensionStatus::Failed && version_compare($manifest->version, $extension->version, '>'),
                'problems' => array_values(array_filter([$extension?->error, ...($status === ExtensionStatus::Enabled ? [] : $manager->problems($manifest))])),
            ];
        }

        // Installed but no longer on disk.
        foreach ($installed as $id => $extension) {
            $rows[$id] ??= ['id' => $id, 'name' => $extension->name, 'version' => $extension->version, 'installed' => $extension->version, 'status' => $extension->status, 'update' => false, 'problems' => [__('The extension files are missing.')]];
        }

        return $rows;
    }
}
