<?php

namespace PnShop\Media\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use PnShop\Media\Models\Media;

/**
 * Attach media to a model in named, ordered collections ("gallery", "logo", ...).
 *
 * @mixin Model
 */
trait HasMedia
{
    /**
     * @return MorphToMany<Media, $this>
     */
    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables')
            ->withPivot(['collection', 'position'])
            ->orderByPivot('position');
    }

    /**
     * Media of one collection, in order (uses the loaded relation when available).
     *
     * @return Collection<int, Media>
     */
    public function mediaIn(string $collection): Collection
    {
        $media = $this->relationLoaded('media') ? $this->getRelation('media') : $this->media()->get();

        return $media->filter(fn (Media $item) => $item->getRelationValue('pivot')?->getAttribute('collection') === $collection)->values();
    }

    public function firstMediaIn(string $collection): ?Media
    {
        return $this->mediaIn($collection)->first();
    }

    /**
     * Replace a collection with the given media, in this order.
     *
     * @param  list<int>  $mediaIds
     */
    public function syncMediaCollection(string $collection, array $mediaIds): void
    {
        $this->media()->wherePivot('collection', $collection)->detach();

        foreach (array_values(array_unique($mediaIds)) as $position => $id) {
            $this->media()->attach($id, ['collection' => $collection, 'position' => $position]);
        }

        $this->unsetRelation('media');
    }
}
