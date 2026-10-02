<?php

namespace PnShop\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use PnShop\Media\MediaLibrary;
use PnShop\Media\Models\Media;

class GenerateConversions implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $mediaId) {}

    public function handle(MediaLibrary $library): void
    {
        $media = Media::query()->find($this->mediaId);

        if ($media !== null && $media->isImage()) {
            $library->generateConversions($media);
        }
    }
}
