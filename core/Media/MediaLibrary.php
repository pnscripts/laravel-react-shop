<?php

namespace PnShop\Media;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use PnShop\Media\Jobs\GenerateConversions;
use PnShop\Media\Models\Media;
use RuntimeException;

/**
 * Registers stored files as Media and produces their resized WebP conversions.
 */
final class MediaLibrary
{
    /** Image types accepted for upload. SVG is excluded because it can carry scripts. */
    public const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif'];

    public const MAX_UPLOAD_KB = 10240;

    public static function disk(): string
    {
        return (string) config('pnshop.media.disk', 'public');
    }

    /**
     * @return array<string, int> conversion name => max width in pixels
     */
    public static function conversionSizes(): array
    {
        /** @var array<string, int> $sizes */
        $sizes = config('pnshop.media.conversions', ['thumb' => 320, 'medium' => 800, 'large' => 1600]);

        return $sizes;
    }

    public static function directory(): string
    {
        return 'media/'.now()->format('Y/m');
    }

    public static function conversionPath(string $path, string $conversion): string
    {
        return dirname($path).'/conversions/'.pathinfo($path, PATHINFO_FILENAME)."-{$conversion}.webp";
    }

    /**
     * Register a file already stored on the media disk (e.g. by a Filament upload).
     * Returns the existing Media when the same path is already registered.
     */
    public function register(string $path, ?string $originalName = null, ?string $disk = null): Media
    {
        $disk ??= self::disk();
        $storage = Storage::disk($disk);

        $existing = Media::query()->where(['disk' => $disk, 'path' => $path])->first();

        if ($existing !== null) {
            return $existing;
        }

        if (! $storage->exists($path)) {
            throw new RuntimeException("Media file [{$path}] does not exist on disk [{$disk}].");
        }

        $mime = (string) $storage->mimeType($path);
        $dimensions = str_starts_with($mime, 'image/') ? @getimagesizefromstring((string) $storage->get($path)) : false;

        $media = Media::query()->create([
            'disk' => $disk,
            'path' => $path,
            'original_name' => $originalName ?? basename($path),
            'mime_type' => $mime,
            'size' => (int) $storage->size($path),
            'width' => $dimensions !== false ? $dimensions[0] : null,
            'height' => $dimensions !== false ? $dimensions[1] : null,
            'checksum' => hash('sha256', (string) $storage->get($path)),
        ]);

        if ($media->isImage()) {
            GenerateConversions::dispatch($media->id);
        }

        return $media;
    }

    /**
     * Create (or recreate) every conversion as WebP. Images are never enlarged, so a
     * conversion of a small original is a WebP copy at the original size.
     */
    public function generateConversions(Media $media): void
    {
        $storage = Storage::disk($media->disk);
        $binary = (string) $storage->get($media->path);
        $generated = [];

        foreach (self::conversionSizes() as $name => $width) {
            $encoded = $this->images()->decodeBinary($binary)->scaleDown(width: $width)->encode(new WebpEncoder(quality: 82));
            $storage->put(self::conversionPath($media->path, $name), (string) $encoded);
            $generated[] = $name;
        }

        $media->forceFill(['conversions' => $generated])->saveQuietly();
    }

    public function deleteFiles(Media $media): void
    {
        $storage = Storage::disk($media->disk);

        foreach (array_keys(self::conversionSizes()) as $name) {
            $storage->delete(self::conversionPath($media->path, $name));
        }

        $storage->delete($media->path);
    }

    private function images(): ImageManager
    {
        $manager = ImageManager::usingDriver(extension_loaded('imagick') ? ImagickDriver::class : GdDriver::class);

        if (! $manager instanceof ImageManager) {
            throw new RuntimeException('Unexpected image manager.');
        }

        return $manager;
    }
}
