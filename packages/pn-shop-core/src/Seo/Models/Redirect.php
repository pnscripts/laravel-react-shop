<?php

namespace PnShop\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $from_path path from the shop root, with any language prefix, e.g. /bg/shop/old
 * @property string $to_url a shop path or a full URL
 * @property int $status 301 or 302
 * @property bool $is_automatic created when a slug changed
 * @property int $hits
 * @property Carbon|null $last_hit_at
 */
class Redirect extends Model
{
    /** @var list<string> */
    protected $fillable = ['from_path', 'to_url', 'status', 'is_automatic'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => 'integer', 'is_automatic' => 'boolean', 'hits' => 'integer', 'last_hit_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect): void {
            $redirect->from_path = self::normalize($redirect->from_path);
        });
    }

    /** "/Old-Page/?x" → "/Old-Page" (paths keep their case; trailing slashes and queries go). */
    public static function normalize(string $path): string
    {
        $path = (string) parse_url(trim($path), PHP_URL_PATH);
        $path = '/'.trim($path, '/');

        return rawurldecode($path);
    }
}
