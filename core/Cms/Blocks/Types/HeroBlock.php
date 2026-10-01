<?php

namespace PnShop\Cms\Blocks\Types;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use PnShop\Cms\Blocks\BlockType;
use PnShop\Cms\Blocks\Concerns\BlockHelpers;

final class HeroBlock extends BlockType
{
    use BlockHelpers;

    public function key(): string
    {
        return 'hero';
    }

    public function label(): string
    {
        return 'Hero banner';
    }

    public function icon(): string
    {
        return 'heroicon-o-sparkles';
    }

    public function fields(): array
    {
        return [
            TextInput::make('heading')->required()->maxLength(160),
            Textarea::make('text')->rows(2)->maxLength(500),
            $this->imageField('image', 'Background image'),
            Grid::make(3)->schema([
                TextInput::make('button_label')->maxLength(60),
                $this->linkField('button_url', 'Button link'),
                Select::make('align')->options(['left' => 'Left', 'center' => 'Centre'])->default('left')->selectablePlaceholder(false),
            ]),
        ];
    }

    public function store(array $data): array
    {
        return $this->storeImage($data, 'image');
    }

    public function props(array $data): ?array
    {
        $heading = $this->text($data['heading'] ?? null);

        if ($heading === null) {
            return null;
        }

        return [
            'heading' => $heading,
            'text' => $this->text($data['text'] ?? null),
            'image' => $this->presentImage($data, 'image', $heading),
            'button' => $this->text($data['button_label'] ?? null) !== null && $this->localUrl($data['button_url'] ?? null) !== null
                ? ['label' => $this->text($data['button_label']), 'url' => $this->localUrl($data['button_url'])]
                : null,
            'align' => ($data['align'] ?? 'left') === 'center' ? 'center' : 'left',
        ];
    }
}
