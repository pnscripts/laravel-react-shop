<?php

namespace PnShop\Settings;

use InvalidArgumentException;

/**
 * A group of settings owned by one namespace: "store", "seo", "theme.default", "plugin.acme.seo".
 */
final class SettingsSchema
{
    /** @var array<string, SettingDefinition> */
    private array $definitions = [];

    public function __construct(
        public readonly string $namespace,
        public readonly string $label,
        SettingDefinition ...$definitions,
    ) {
        if (! preg_match('/^[a-z0-9_]+(\.[a-z0-9_-]+)*$/', $namespace)) {
            throw new InvalidArgumentException("Invalid settings namespace [{$namespace}].");
        }

        foreach ($definitions as $definition) {
            $this->definitions[$definition->key] = $definition;
        }
    }

    public function get(string $key): ?SettingDefinition
    {
        return $this->definitions[$key] ?? null;
    }

    /**
     * @return array<string, SettingDefinition>
     */
    public function definitions(): array
    {
        return $this->definitions;
    }
}
