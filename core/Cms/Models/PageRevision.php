<?php

namespace PnShop\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use PnShop\Acl\Models\AdminUser;

/**
 * A full copy of a page (fields, translations, blocks) as it was saved.
 *
 * @property int $id
 * @property int $page_id
 * @property array{attributes: array<string, mixed>, translations: array<string, array<string, mixed>>, blocks: array<string, array<string, list<array{type: string, data: array<string, mixed>}>>>} $snapshot
 * @property int|null $admin_user_id
 * @property Carbon|null $created_at
 */
class PageRevision extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['page_id', 'snapshot', 'admin_user_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }
}
