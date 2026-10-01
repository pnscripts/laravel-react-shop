<?php

namespace PnShop\Cms\Blocks;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;

/**
 * A kind of content block (rich text, hero, product grid, ...).
 *
 * Block types are registered with BlockRegistry by core and extensions. The admin edits
 * a block with fields(); the storefront renders it with the React component registered
 * under key() and the props from props(), which may load data (products, media).
 */
abstract class BlockType
{
    /** Stable identifier stored with each block and used to pick the React component. */
    abstract public function key(): string;

    abstract public function label(): string;

    /** A Heroicon name for the admin block picker, e.g. "heroicon-o-photo". */
    public function icon(): string
    {
        return 'heroicon-o-squares-2x2';
    }

    /**
     * Filament form components editing the block's data.
     *
     * @return list<Component|Field>
     */
    abstract public function fields(): array;

    /**
     * Prepare submitted data for storage (validate references, register uploads).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function store(array $data): array
    {
        return $data;
    }

    /**
     * Props for the storefront component. Return null to skip the block (e.g. empty).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function props(array $data): ?array
    {
        return $data;
    }

    /** A permission staff need to add or change blocks of this type, if any. */
    public function permission(): ?string
    {
        return null;
    }
}
