<?php

namespace PnShop\Settings;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PnShop\Settings\Models\Setting;

/**
 * Typed, cached access to stored settings. Values fall back to the definition default.
 */
final class Settings
{
    private const CACHE_KEY = 'pnshop.settings';

    /** @var array<string, array<string, mixed>>|null */
    private ?array $values = null;

    public function __construct(
        private SettingsRegistry $registry,
        private Cache $cache,
    ) {}

    public function get(string $path): mixed
    {
        [$schema, $definition] = $this->registry->resolve($path);

        $stored = $this->values()[$schema->namespace][$definition->key] ?? null;

        return $definition->type->cast($stored ?? $definition->default);
    }

    /**
     * All values of one namespace, keyed by setting key.
     *
     * @return array<string, mixed>
     */
    public function namespace(string $namespace): array
    {
        $schema = $this->registry->schema($namespace);

        if ($schema === null) {
            return [];
        }

        $values = [];

        foreach ($schema->definitions() as $key => $definition) {
            $values[$key] = $this->get("{$namespace}.{$key}");
        }

        return $values;
    }

    /**
     * Validate and store one or more values of a namespace.
     *
     * @param  array<string, mixed>  $values
     *
     * @throws ValidationException
     */
    public function set(string $namespace, array $values): void
    {
        $rules = [];

        foreach (array_keys($values) as $key) {
            [, $definition] = $this->registry->resolve("{$namespace}.{$key}");
            $rules[$key] = $definition->validationRules();
        }

        $validated = Validator::make($values, $rules)->validate();

        foreach ($validated as $key => $value) {
            [, $definition] = $this->registry->resolve("{$namespace}.{$key}");

            Setting::query()->updateOrCreate(
                ['namespace' => $namespace, 'key' => $key],
                ['value' => $definition->type->cast($value)],
            );
        }

        $this->flush();
    }

    public function flush(): void
    {
        $this->values = null;
        $this->cache->forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function values(): array
    {
        return $this->values ??= $this->cache->rememberForever(self::CACHE_KEY, function () {
            $values = [];

            foreach (Setting::query()->get(['namespace', 'key', 'value']) as $setting) {
                $values[$setting->namespace][$setting->key] = $setting->value;
            }

            return $values;
        });
    }
}
