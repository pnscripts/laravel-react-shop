<?php

namespace PnShop\Cms\Blocks\Types;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use PnShop\Cms\Blocks\BlockType;
use PnShop\Cms\Blocks\SafeHtml;

final class RichTextBlock extends BlockType
{
    public function key(): string
    {
        return 'rich_text';
    }

    public function label(): string
    {
        return 'Text';
    }

    public function icon(): string
    {
        return 'heroicon-o-bars-3-bottom-left';
    }

    public function fields(): array
    {
        return [
            RichEditor::make('content')
                ->hiddenLabel()
                ->toolbarButtons([['bold', 'italic', 'underline', 'strike', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote', 'table'], ['undo', 'redo']])
                ->required(),
        ];
    }

    public function props(array $data): ?array
    {
        // The editor stores a TipTap document; older or imported data may be HTML.
        $content = $data['content'] ?? null;
        $html = SafeHtml::clean(is_string($content) || is_array($content) ? RichContentRenderer::make($content)->toUnsafeHtml() : '');

        return trim(strip_tags($html)) === '' ? null : ['html' => $html];
    }
}
