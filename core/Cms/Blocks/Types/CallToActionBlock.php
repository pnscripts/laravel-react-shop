<?php

namespace PnShop\Cms\Blocks\Types;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use PnShop\Cms\Blocks\BlockType;
use PnShop\Cms\Blocks\Concerns\BlockHelpers;

final class CallToActionBlock extends BlockType
{
    use BlockHelpers;

    public function key(): string
    {
        return 'call_to_action';
    }

    public function label(): string
    {
        return 'Call to action';
    }

    public function icon(): string
    {
        return 'heroicon-o-megaphone';
    }

    public function fields(): array
    {
        return [
            TextInput::make('heading')->required()->maxLength(160),
            Textarea::make('text')->rows(2)->maxLength(500),
            Grid::make(2)->schema([
                TextInput::make('button_label')->required()->maxLength(60),
                $this->linkField('button_url', 'Button link')->required(),
            ]),
        ];
    }

    public function props(array $data): ?array
    {
        $url = $this->localUrl($data['button_url'] ?? null);
        $heading = $this->text($data['heading'] ?? null);

        return $heading === null || $url === null ? null : [
            'heading' => $heading,
            'text' => $this->text($data['text'] ?? null),
            'button' => ['label' => $this->text($data['button_label'] ?? null) ?? $heading, 'url' => $url],
        ];
    }
}
