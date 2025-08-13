<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ProductStatus: string implements HasLabel, HasColor, HasIcon, HasDescription
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Discontinued = 'discontinued';
    case OutOfStock = 'out_of_stock';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Active => 'success',
            self::OutOfStock => 'danger',
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
            self::Draft => 'Product is being created or edited, not visible to customers.',
            self::Active => 'Product is available for purchase.',
            self::Inactive => 'Product is temporarily unavailable but not deleted.',
            self::Discontinued => 'Product is permanently removed from sale.',
            self::OutOfStock => 'Product is listed but currently out of inventory.',
        };
    }

    public static function default(): self
    {
        return self::Active;
    }
}
