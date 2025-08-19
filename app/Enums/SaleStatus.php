<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum SaleStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Completed = 'completed';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Completed => 'success',
            self::Pending => 'warning',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Completed => 'heroicon-s-check-badge',
            self::Pending => 'heroicon-s-clock',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Completed => 'Order is awaiting processing.',
            self::Pending => 'Order has been fulfilled.',
        };
    }

    public static function default(): self
    {
        return self::Completed;
    }
}
