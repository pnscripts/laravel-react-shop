<?php

namespace PnShop\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One block in an owner's content area for one language.
 *
 * @property int $id
 * @property string $owner_type
 * @property int $owner_id
 * @property string $area
 * @property string $locale
 * @property int $position
 * @property string $type block type key, see BlockRegistry
 * @property array<string, mixed> $data
 */
class ContentBlock extends Model
{
    /** @var list<string> */
    protected $fillable = ['owner_type', 'owner_id', 'area', 'locale', 'position', 'type', 'data'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['data' => 'array', 'position' => 'integer'];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
