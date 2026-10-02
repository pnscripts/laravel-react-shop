<?php

namespace PnShop\Extension;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ExtensionStatus: string implements HasColor, HasLabel
{
    /** Found on disk, never installed. Not stored. */
    case Available = 'available';
    case Installed = 'installed';
    case Enabled = 'enabled';
    case Disabled = 'disabled';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Enabled => 'success',
            self::Failed => 'danger',
            self::Disabled => 'warning',
            default => 'gray',
        };
    }
}
