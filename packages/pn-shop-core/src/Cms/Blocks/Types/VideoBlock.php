<?php

namespace PnShop\Cms\Blocks\Types;

use Filament\Forms\Components\TextInput;
use PnShop\Cms\Blocks\BlockType;
use PnShop\Cms\Blocks\Concerns\BlockHelpers;

/**
 * A YouTube or Vimeo video, embedded from privacy-friendly hosts.
 */
final class VideoBlock extends BlockType
{
    use BlockHelpers;

    public function key(): string
    {
        return 'video';
    }

    public function label(): string
    {
        return 'Video';
    }

    public function icon(): string
    {
        return 'heroicon-o-play-circle';
    }

    public function fields(): array
    {
        return [
            TextInput::make('url')->label('YouTube or Vimeo link')->url()->required()
                ->rules(['regex:#^https://(www\.)?(youtube\.com|youtu\.be|vimeo\.com)/#i']),
            TextInput::make('title')->maxLength(160)->helperText('Read by screen readers.'),
        ];
    }

    public function props(array $data): ?array
    {
        $embed = self::embedUrl((string) ($data['url'] ?? ''));

        return $embed === null ? null : ['embed' => $embed, 'title' => $this->text($data['title'] ?? null) ?? 'Video'];
    }

    public static function embedUrl(string $url): ?string
    {
        if (preg_match('#^https://(?:www\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,20})#', $url, $match)) {
            return 'https://www.youtube-nocookie.com/embed/'.$match[1];
        }

        if (preg_match('#^https://(?:www\.)?vimeo\.com/(\d{4,12})#', $url, $match)) {
            return 'https://player.vimeo.com/video/'.$match[1].'?dnt=1';
        }

        return null;
    }
}
