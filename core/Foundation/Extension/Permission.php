<?php

namespace PnShop\Foundation\Extension;

/**
 * A permission that core modules and extensions can register, e.g. "catalog.products.update".
 */
final readonly class Permission
{
    public function __construct(
        public string $key,
        public string $label,
        public string $group,
    ) {}
}
