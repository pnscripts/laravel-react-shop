<?php

namespace PnShop\Cms\Blocks\Concerns;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use PnShop\Localization\Localization;
use PnShop\Media\MediaLibrary;
use PnShop\Media\MediaPresenter;
use PnShop\Media\Models\Media;

/**
 * Images and links in block data.
 */
trait BlockHelpers
{
    protected function imageField(string $name, string $label = 'Image'): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->acceptedFileTypes(MediaLibrary::IMAGE_TYPES)
            ->maxSize(MediaLibrary::MAX_UPLOAD_KB)
            ->disk(MediaLibrary::disk())
            ->directory(fn () => MediaLibrary::directory());
    }

    protected function linkField(string $name, string $label = 'Link'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->maxLength(2048)
            ->rules(['nullable', 'regex:#^(https?://|/)#i'])
            ->helperText('A page of this shop (/shop) or a full address (https://…).');
    }

    /**
     * Register an uploaded image in the media library and keep its id next to the path.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function storeImage(array $data, string $key): array
    {
        $path = $data[$key] ?? null;

        if (is_array($path)) {
            $path = array_values($path)[0] ?? null;
        }

        $data[$key] = is_string($path) && $path !== '' ? $path : null;
        $data["{$key}_id"] = $data[$key] === null ? null : app(MediaLibrary::class)->register($data[$key])->id;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    protected function presentImage(array $data, string $key, ?string $alt = null): ?array
    {
        $id = $data["{$key}_id"] ?? null;
        $media = is_numeric($id) ? Media::query()->find((int) $id) : null;

        return $media === null ? null : MediaPresenter::present($media, $alt);
    }

    /**
     * Shop-relative links get the current language prefix; full URLs are kept.
     */
    protected function localUrl(mixed $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        if (! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return null;
        }

        return app(Localization::class)->prefix(app()->getLocale()).($url === '/' && app(Localization::class)->prefix(app()->getLocale()) !== '' ? '' : $url);
    }

    protected function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
