<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum BrandStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Discontinued = 'discontinued';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Active => 'success',
            default => 'gray',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Draft => 'heroicon-s-clock',
            self::Active => 'heroicon-s-check-badge',
            default => 'heroicon-s-no-symbol',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Draft => 'Brand is being created or edited, not visible to customers.',
            self::Active => 'Brand is available for purchase.',
            self::Inactive => 'Brand is temporarily unavailable but not deleted.',
            self::Discontinued => 'Brand is permanently removed from sale.',
        };
    }

    public static function default(): self
    {
        return self::Active;
    }
}
