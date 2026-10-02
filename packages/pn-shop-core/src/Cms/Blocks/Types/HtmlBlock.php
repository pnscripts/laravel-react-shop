<?php

namespace PnShop\Cms\Blocks\Types;

use Filament\Forms\Components\Textarea;
use PnShop\Cms\Blocks\BlockType;

/**
 * Raw HTML (embeds, widgets), shown as entered. Only staff with the
 * "cms.html_block" permission may add or change it.
 */
final class HtmlBlock extends BlockType
{
    public function key(): string
    {
        return 'html';
    }

    public function label(): string
    {
        return 'Custom HTML';
    }

    public function icon(): string
    {
        return 'heroicon-o-code-bracket';
    }

    public function permission(): string
    {
        return 'cms.html_block';
    }

    public function fields(): array
    {
        return [
            Textarea::make('html')->label('HTML')->rows(8)->required()->extraInputAttributes(['class' => 'font-mono'])
                ->helperText('Shown as entered, scripts included. Only paste code you trust.'),
        ];
    }

    public function props(array $data): ?array
    {
        $html = $data['html'] ?? '';

        return is_string($html) && trim($html) !== '' ? ['html' => $html] : null;
    }
}
