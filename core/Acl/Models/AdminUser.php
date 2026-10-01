<?php

namespace PnShop\Acl\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use PnShop\Acl\Factories\AdminUserFactory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

/**
 * A staff member who signs in to the admin panel. Separate from customer accounts.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 */
class AdminUser extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<AdminUserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    public const ADMINISTRATOR_ROLE = 'administrator';

    protected string $guard_name = 'admin';

    /** @var list<string> */
    protected $fillable = ['name', 'email', 'password', 'is_active'];

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole(self::ADMINISTRATOR_ROLE);
    }

    protected static function newFactory(): AdminUserFactory
    {
        return AdminUserFactory::new();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('system')
            ->logOnly(['name', 'email', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
