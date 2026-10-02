<?php

namespace PnShop\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use PnShop\Localization\Concerns\Translatable;
use PnShop\Localization\Contracts\TranslatableModel;
use PnShop\Media\MediaLibrary;

/**
 * An uploaded file (image) in the media library, attachable to any model via `mediables`.
 *
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property int|null $width
 * @property int|null $height
 * @property string $checksum
 * @property string|null $alt
 * @property string|null $title
 * @property list<string>|null $conversions names of generated conversions
 */
class Media extends Model implements TranslatableModel
{
    use Translatable;

    protected $table = 'media';

    /** @var list<string> */
    protected $guarded = ['id'];

    /** @var list<string> */
    protected array $translatable = ['alt', 'title'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['conversions' => 'array', 'size' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    protected static function booted(): void
    {
        static::deleted(fn (Media $media) => app(MediaLibrary::class)->deleteFiles($media));
    }

    /**
     * Public URL of the original or of a generated conversion (falls back to the original).
     */
    public function url(?string $conversion = null): string
    {
        $path = $conversion !== null && in_array($conversion, $this->conversions ?? [], true)
            ? MediaLibrary::conversionPath($this->path, $conversion)
            : $this->path;

        return Storage::disk($this->disk)->url($path);
    }

    /**
     * `srcset` value built from the generated conversions.
     */
    public function srcset(): string
    {
        $sizes = MediaLibrary::conversionSizes();

        return collect($this->conversions ?? [])
            ->filter(fn (string $name) => isset($sizes[$name]) && ($this->width === null || $sizes[$name] <= $this->width))
            ->map(fn (string $name) => $this->url($name).' '.$sizes[$name].'w')
            ->implode(', ');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
