<?php

namespace PnShop\Settings;

use InvalidArgumentException;

final class SettingsRegistry
{
    /** @var array<string, SettingsSchema> */
    private array $schemas = [];

    public function register(SettingsSchema $schema): void
    {
        if (isset($this->schemas[$schema->namespace])) {
            throw new InvalidArgumentException("Settings namespace [{$schema->namespace}] is already registered.");
        }

        $this->schemas[$schema->namespace] = $schema;
    }

    public function schema(string $namespace): ?SettingsSchema
    {
        return $this->schemas[$namespace] ?? null;
    }

    /**
     * @return array<string, SettingsSchema>
     */
    public function all(): array
    {
        return $this->schemas;
    }

    /**
     * Resolve "store.email" or "plugin.acme.seo.enabled" to its schema and definition.
     *
     * @return array{SettingsSchema, SettingDefinition}
     */
    public function resolve(string $path): array
    {
        $namespace = str_contains($path, '.') ? substr($path, 0, (int) strrpos($path, '.')) : '';
        $key = substr($path, strlen($namespace) + 1);

        $schema = $this->schema($namespace);
        $definition = $schema?->get($key);

        if ($schema === null || $definition === null) {
            throw new InvalidArgumentException("Unknown setting [{$path}].");
        }

        return [$schema, $definition];
    }
}
