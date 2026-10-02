<?php

namespace PnShop\Cms\Blocks\Types;

use Filament\Forms\Components\TextInput;
use PnShop\Cms\Blocks\BlockType;
use PnShop\Cms\Blocks\Concerns\BlockHelpers;

final class ImageBlock extends BlockType
{
    use BlockHelpers;

    public function key(): string
    {
        return 'image';
    }

    public function label(): string
    {
        return 'Image';
    }

    public function icon(): string
    {
        return 'heroicon-o-photo';
    }

    public function fields(): array
    {
        return [
            $this->imageField('image')->required(),
            TextInput::make('alt')->label('Alternative text')->maxLength(255)->helperText('Describes the image for screen readers and search engines.'),
            TextInput::make('caption')->maxLength(255),
            $this->linkField('url'),
        ];
    }

    public function store(array $data): array
    {
        return $this->storeImage($data, 'image');
    }

    public function props(array $data): ?array
    {
        $image = $this->presentImage($data, 'image', $this->text($data['alt'] ?? null));

        return $image === null ? null : [
            'image' => $image,
            'caption' => $this->text($data['caption'] ?? null),
            'url' => $this->localUrl($data['url'] ?? null),
        ];
    }
}
