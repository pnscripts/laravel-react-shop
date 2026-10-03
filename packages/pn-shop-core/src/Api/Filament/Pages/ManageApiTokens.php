<?php

namespace PnShop\Api\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use PnShop\Acl\Models\AdminUser;
use PnShop\Api\StaffTokens;
use PnShop\Foundation\Extension\PermissionRegistry;
use UnitEnum;

/**
 * The signed-in staff member's Admin API tokens: create one with a chosen set of
 * permissions (shown once), see when it was last used, revoke it.
 */
class ManageApiTokens extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 60;

    protected static ?string $title = 'API tokens';

    protected static ?string $slug = 'api-tokens';

    /** The token just created, shown once. */
    public ?string $plainTextToken = null;

    public static function canAccess(): bool
    {
        return auth('admin')->user() instanceof AdminUser;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Text::make('Tokens let other systems (ERP, accounting, a mobile app backend) use the Admin API at /api/admin/v1 as you, limited to the permissions you choose. Treat a token like a password.')->color('gray'),
            Section::make('Your new token')
                ->description('Copy it now: it is not shown again.')
                ->visible(fn () => $this->plainTextToken !== null)
                ->schema([
                    TextInput::make('plainTextToken')->hiddenLabel()->readOnly()->copyable(),
                ]),
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => PersonalAccessToken::query()
                ->where('tokenable_type', (new AdminUser)->getMorphClass())
                ->where('tokenable_id', $this->admin()->id)
                ->latest())
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('abilities')
                    ->label('Permissions')
                    ->formatStateUsing(fn (mixed $state) => $state === StaffTokens::ALL ? 'All of yours' : $state)
                    ->badge()
                    ->limitList(4)
                    ->expandableLimitedList(),
                TextColumn::make('last_used_at')->label('Last used')->since()->placeholder('Never'),
                TextColumn::make('expires_at')->label('Expires')->date()->placeholder('Never'),
                TextColumn::make('created_at')->label('Created')->date(),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Create token')
                    ->icon(Heroicon::OutlinedPlus)
                    ->schema([
                        TextInput::make('name')->required()->maxLength(100)->placeholder('ERP sync'),
                        Select::make('expires_in')
                            ->label('Expires')
                            // Tokens always expire, so a forgotten integration cannot keep access for ever.
                            ->options(['30' => 'In 30 days', '90' => 'In 90 days', '365' => 'In a year'])
                            ->default('90')
                            ->required(),
                        Toggle::make('all')
                            ->label('All of my permissions')
                            ->helperText('Also permissions you get later. Prefer choosing only what the integration needs.')
                            ->live(),
                        CheckboxList::make('abilities')
                            ->label('Permissions')
                            ->options(fn () => $this->grantableOptions())
                            ->columns(2)
                            ->searchable()
                            ->hidden(fn (Get $get) => (bool) $get('all'))
                            ->required(fn (Get $get) => ! $get('all')),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $token = app(StaffTokens::class)->issue(
                                $this->admin(),
                                $data['name'],
                                $data['all'] ? [StaffTokens::ALL] : array_values($data['abilities'] ?? []),
                                $data['expires_in'] !== '' && $data['expires_in'] !== null ? now()->addDays((int) $data['expires_in']) : null,
                            );
                        } catch (ValidationException $e) {
                            Notification::make()->danger()->title(collect($e->errors())->flatten()->first())->send();

                            return;
                        }

                        $this->plainTextToken = $token->plainTextToken;
                        Notification::make()->success()->title('Token created. Copy it now.')->send();
                    }),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Integrations using this token stop working immediately.')
                    ->action(function (PersonalAccessToken $record): void {
                        $record->delete();

                        activity('system')->causedBy($this->admin())->performedOn($this->admin())
                            ->withProperties(['token' => $record->getAttribute('name')])
                            ->log('API token revoked');

                        Notification::make()->success()->title('Token revoked.')->send();
                    }),
            ])
            ->emptyStateHeading('No API tokens')
            ->emptyStateDescription('Create one for each system that should use the Admin API.');
    }

    /**
     * @return array<string, string>
     */
    private function grantableOptions(): array
    {
        $permissions = app(PermissionRegistry::class)->all();
        $options = [];

        foreach (app(StaffTokens::class)->grantable($this->admin()) as $key) {
            $options[$key] = $permissions[$key]->group.': '.$permissions[$key]->label;
        }

        asort($options);

        return $options;
    }

    private function admin(): AdminUser
    {
        $admin = auth('admin')->user();

        return $admin instanceof AdminUser ? $admin : abort(403);
    }
}
