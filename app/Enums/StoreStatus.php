<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum StoreStatus: string implements HasLabel, HasColor, HasIcon, HasDescription
{
    case Demo = 'demo';
    case Live = 'live';
    case Maintenance = 'maintenance';
    case Disabled = 'disabled';
    case Blocked = 'blocked';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Live => 'success',
            self::Demo => 'info',
            self::Maintenance => 'warning',
            self::Disabled => 'gray',
            self::Blocked => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Live => 'heroicon-s-check-badge',
            self::Demo => 'heroicon-s-shield-check',
            self::Maintenance => 'heroicon-s-exclamation-triangle',
            self::Disabled, self::Blocked => 'heroicon-s-no-symbol',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Demo => 'Store is in demo mode and not available for real transactions.',
            self::Live => 'Store is fully active and accessible to users.',
            self::Maintenance => 'Store is temporarily unavailable due to maintenance.',
            self::Disabled => 'Store is disabled and cannot be accessed.',
            self::Blocked => 'Store is blocked due to policy or compliance issues.',
        };
    }

    public static function default(): self
    {
        return self::Demo;
    }
}
