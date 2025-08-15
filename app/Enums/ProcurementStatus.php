<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ProcurementStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Open = 'open';
    case Closed = 'closed';
    case Rejected = 'rejected';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Open => 'info',
            self::Closed => 'success',
            self::Rejected => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Pending => 'heroicon-s-clock',
            self::Open => 'heroicon-s-arrow-path',
            self::Closed => 'heroicon-s-check-badge',
            self::Rejected => 'heroicon-s-no-symbol',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Pending => 'Procurement request awaiting review or approval.',
            self::Open => 'Procurement is approved and currently in progress.',
            self::Closed => 'Procurement has been completed and closed.',
            self::Rejected => 'Procurement request was rejected.',
        };
    }

    public static function default(): self
    {
        return self::Pending;
    }
}
