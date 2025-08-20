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
    case Cancelled = 'cancelled';
    case Returned = 'returned';
    case Refunded = 'refunded';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
            self::Returned => 'info',
            self::Refunded => 'info',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Pending => 'heroicon-s-clock',
            self::Completed => 'heroicon-s-check-badge',
            self::Cancelled => 'heroicon-s-no-symbol',
            self::Returned => 'heroicon-s-arrow-uturn-left',
            self::Refunded => 'heroicon-s-banknotes',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Pending => 'Order is awaiting processing.',
            self::Completed => 'Order has been fulfilled.',
            self::Cancelled => 'Order has been cancelled.',
            self::Returned => 'Order was returned.',
            self::Refunded => 'Order was refunded.',
        };
    }

    public static function default(): self
    {
        return self::Completed;
    }
}
