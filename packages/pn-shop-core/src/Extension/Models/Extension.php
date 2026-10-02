<?php

namespace PnShop\Extension\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use PnShop\Extension\ExtensionStatus;

/**
 * @property string $id vendor/name
 * @property string $name
 * @property string $version installed version
 * @property ExtensionStatus $status
 * @property string|null $error why the last action failed
 * @property array<string, string>|null $checksums relative path => sha256 at install
 * @property Carbon|null $installed_at
 * @property Carbon|null $enabled_at
 */
class Extension extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = ['id', 'name', 'version', 'status', 'error', 'checksums', 'installed_at', 'enabled_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ExtensionStatus::class,
            'checksums' => 'array',
            'installed_at' => 'datetime',
            'enabled_at' => 'datetime',
        ];
    }
}
