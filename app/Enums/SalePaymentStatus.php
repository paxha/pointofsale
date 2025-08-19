<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum SalePaymentStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Credit = 'credit';
    case Paid = 'paid';
    case Refunded = 'refunded';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Credit => 'info',
            self::Paid => 'success',
            self::Refunded => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Pending => 'heroicon-s-clock',
            self::Credit => 'heroicon-s-banknotes',
            self::Paid => 'heroicon-s-check-badge',
            self::Refunded => 'heroicon-s-no-symbol',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Pending => 'Payment is pending and not yet received.',
            self::Credit => 'Sale recorded on credit; payment is outstanding.',
            self::Paid => 'Payment received in full.',
            self::Refunded => 'Payment has been refunded to the customer.',
        };
    }

    public static function default(): self
    {
        return self::Paid;
    }
}
